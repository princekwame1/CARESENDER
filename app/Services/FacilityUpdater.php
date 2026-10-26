<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CatalogService;
use App\Models\Facility;
use App\Models\Service;
use App\Support\MapLink;
use App\Support\Schedule;
use App\Support\SearchText;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * All dashboard writes to the directory go through here so that derived data
 * (search text, schedule summaries, map position, caches) always stays in sync.
 */
class FacilityUpdater
{
    private const DETAILS = ['name', 'facility_type', 'ownership', 'address', 'maps_url', 'district', 'phone', 'whatsapp', 'email', 'issue'];

    public function __construct(private DirectoryCache $cache, private CatalogSync $catalog) {}

    /** Save facility details, opening hours and per-service changes; optionally record a phone check. */
    public function apply(Facility $facility, array $data, bool $markChecked = true): Facility
    {
        DB::transaction(function () use ($facility, $data, $markChecked) {
            $now = now();
            $this->fillDetails($facility, $data);
            if ($markChecked) {
                $facility->verified_at = $now;
            }
            $facility->save();

            $services = $facility->services()->whereIn('id', array_keys($data['services'] ?? []))->get();
            foreach ($services as $service) {
                $this->fillService($service, $data['services'][$service->id]);
                if ($markChecked) {
                    $service->verified_at = $now;
                }
                $service->search_text = SearchText::service($service, $facility);
                $service->save();
            }

            // Names/areas may have changed: refresh every service's search text.
            if ($facility->wasChanged(['name', 'area', 'city'])) {
                $facility->services->each(fn (Service $s) => $s->forceFill(['search_text' => SearchText::service($s, $facility)])->save());
            }

            $facility->forceFill(['services_count' => $facility->services()->count()])->save();
        });

        $this->cache->flush();

        return $facility->refresh();
    }

    public function create(array $data): Facility
    {
        $facility = new Facility(['id' => 'cs-'.Str::slug(Str::limit($data['name'], 40, '')).'-'.Str::lower(Str::random(5))]);
        $facility->is_active = (bool) ($data['is_active'] ?? true);
        $facility->collected_at = now();
        $facility->search_text = '';

        return $this->apply($facility, $data, markChecked: false);
    }

    /**
     * Add services picked from the catalogue. Services the facility already offers are skipped.
     *
     * @param  list<int>  $catalogIds
     * @return int number added
     */
    public function addFromCatalog(Facility $facility, array $catalogIds): int
    {
        $existing = $facility->services()->whereNotNull('catalog_service_id')->pluck('catalog_service_id')->all();
        $items = CatalogService::with('specialty')->whereIn('id', array_diff($catalogIds, $existing))->get();

        DB::transaction(function () use ($facility, $items) {
            foreach ($items as $item) {
                $service = new Service([
                    'id' => 'cs-'.Str::lower(Str::ulid()),
                    'facility_id' => $facility->id,
                    'available' => true,
                    'service_evidence' => 'dashboard',
                    'verified_at' => now(),
                    ...$this->catalog->attributesFor($item),
                ]);
                $service->search_text = SearchText::service($service, $facility);
                $service->save();
            }
            $facility->forceFill(['services_count' => $facility->services()->count()])->save();
        });
        $this->cache->flush();

        return $items->count();
    }

    public function removeService(Service $service): void
    {
        $facility = $service->facility;
        $service->delete();
        $facility->forceFill(['services_count' => $facility->services()->count()])->save();
        $this->cache->flush();
    }

    public function setActive(Facility $facility, bool $active): void
    {
        $facility->forceFill(['is_active' => $active])->save();
        $this->cache->flush();
    }

    public function delete(Facility $facility): void
    {
        $facility->delete(); // services cascade
        $this->cache->flush();
    }

    private function fillDetails(Facility $facility, array $data): void
    {
        $facility->fill(Arr::only($data, self::DETAILS));

        if (array_key_exists('diagnostic_categories', $data)) {
            $cats = array_values($data['diagnostic_categories'] ?? []);
            $facility->diagnostic_categories = $cats;
            $facility->has_laboratory = in_array('laboratory', $cats, true);
            $facility->has_imaging = in_array('imaging', $cats, true);
        }

        if (! empty($data['area_id'])) {
            $area = Area::findOrFail($data['area_id']);
            $facility->forceFill([
                'area_id' => $area->id, 'area' => $area->name, 'city' => $area->city,
                'lat' => $area->lat, 'lng' => $area->lng, 'geo_precision' => 'area',
            ]);
        }

        // Exact location: typed coordinates win, otherwise read them from the Google Maps link.
        $exact = MapLink::coordinates($data['coordinates'] ?? null)
            ?? (! empty($data['maps_url']) && $facility->isDirty('maps_url') ? MapLink::coordinates($data['maps_url']) : null);
        if ($exact) {
            $facility->forceFill(['lat' => $exact[0], 'lng' => $exact[1], 'geo_precision' => 'exact']);
        } elseif (array_key_exists('coordinates', $data) && blank($data['coordinates']) && $facility->geo_precision === 'exact') {
            // Cleared: fall back to the centre of the facility's area.
            $area = $facility->area_id ? Area::find($facility->area_id) : null;
            $facility->forceFill($area
                ? ['lat' => $area->lat, 'lng' => $area->lng, 'geo_precision' => 'area']
                : ['geo_precision' => 'city']);
        }

        if (array_key_exists('opening_hours', $data)) {
            $hours = [];
            foreach ($data['opening_hours'] ?? [] as $day => $h) {
                if (! empty($h['on']) && ! empty($h['open']) && ! empty($h['close'])) {
                    $hours[$day] = ['open' => $h['open'], 'close' => $h['close']];
                }
            }
            $facility->opening_hours = $hours ?: null;
            $facility->hours = Schedule::openingHours($hours);
        }

        $facility->search_text = SearchText::facility($facility);
    }

    private function fillService(Service $service, array $changes): void
    {
        $service->fill(Arr::only($changes, ['available', 'specialist_name', 'gender', 'price']));

        // Older API clients send a plain list of days plus free-text schedule.
        if (! array_key_exists('slots', $changes)) {
            $service->fill(Arr::only($changes, ['days', 'schedule']));
        }

        if (array_key_exists('slots', $changes)) {
            $slots = [];
            $days = [];
            foreach ($changes['slots'] ?? [] as $day => $s) {
                if (empty($s['on'])) {
                    continue;
                }
                $days[] = $day; // a ticked day counts even before its times are known
                if (! empty($s['start']) && ! empty($s['end'])) {
                    $slots[] = ['day' => $day, 'start' => $s['start'], 'end' => $s['end']];
                }
            }
            $service->slots = $slots ?: null;
            $service->days = $days;
            $service->schedule = Schedule::summarise($slots) ?? Schedule::daysOnly($days);
        }
    }
}
