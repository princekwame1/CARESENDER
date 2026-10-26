<?php

namespace App\Services;

use App\Models\Area;
use App\Support\SearchNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Loads the CareSenda directory export (facilities + service records) into the
 * database and assigns approximate coordinates from the area gazetteer.
 */
class DirectoryImporter
{
    private const CHUNK = 500;

    /** @var list<array{id: int, city: string, lat: float, lng: float, needles: list<string>}> */
    private array $gazetteer = [];

    public function __construct(private DirectoryCache $cache) {}

    /** @return array{areas: int, facilities: int, services: int, geocoded: int} */
    public function import(string $path): array
    {
        $data = $this->read($path);

        return DB::transaction(function () use ($data) {
            $areas = $this->importAreas();
            [$facilities, $geocoded, $facilityMeta] = $this->importFacilities($data['facilities'] ?? []);
            $services = $this->importServices($data['records'] ?? [], $facilityMeta);

            $this->linkCatalog();
            DB::statement('UPDATE facilities SET services_count = (SELECT count(*) FROM services WHERE services.facility_id = facilities.id)');
            $this->cache->flush();

            return compact('areas', 'facilities', 'services', 'geocoded');
        });
    }

    /**
     * Make sure every listing points at the service list: specialists at their specialty's
     * consultation entry, tests at an entry in their test group (created when new).
     */
    private function linkCatalog(): void
    {
        if (! Schema::hasTable('catalog_services')) {
            return;
        }
        $now = now();
        $group = function (string $name, string $kind, ?string $diagnostic = null) use ($now): int {
            return DB::table('specialties')->where('name', $name)->value('id')
                ?? DB::table('specialties')->insertGetId([
                    'name' => $name, 'slug' => Str::slug($name), 'kind' => $kind, 'diagnostic' => $diagnostic,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
        };
        $item = function (int $groupId, string $name, string $type) use ($now): int {
            return DB::table('catalog_services')->where('type', $type)->where('name', $name)->value('id')
                ?? DB::table('catalog_services')->insertGetId([
                    'specialty_id' => $groupId, 'name' => $name, 'type' => $type, 'created_at' => $now, 'updated_at' => $now,
                ]);
        };

        foreach (DB::table('services')->where('type', 'specialist')->whereNull('catalog_service_id')->distinct()->pluck('service') as $name) {
            $groupId = $group($name, 'clinical');
            DB::table('services')->where('type', 'specialist')->whereNull('catalog_service_id')->where('service', $name)
                ->update(['specialty_id' => $groupId, 'catalog_service_id' => $item($groupId, $name, 'specialist')]);
        }

        $tests = DB::table('services')->where('type', 'test')->whereNull('catalog_service_id')
            ->select('service', 'lab_category', DB::raw('min(diagnostic_categories) as cats'))
            ->groupBy('service', 'lab_category')->get();
        foreach ($tests as $t) {
            $existing = DB::table('catalog_services')->where('type', 'test')->where('name', $t->service)->first();
            if ($existing) {
                $groupId = $existing->specialty_id;
                $groupName = DB::table('specialties')->where('id', $groupId)->value('name');
            } else {
                $cat = json_decode($t->cats ?? '[]', true)[0] ?? 'laboratory';
                [$groupName, $diag] = match (true) {
                    (bool) $t->lab_category => [$t->lab_category, 'laboratory'],
                    $cat === 'imaging' => ['Radiology & imaging', 'imaging'],
                    $cat === 'other' => ['Other diagnostics', 'other'],
                    default => ['General laboratory tests', 'laboratory'],
                };
                $groupId = $group($groupName, 'diagnostic', $diag);
            }
            DB::table('services')->where('type', 'test')->whereNull('catalog_service_id')->where('service', $t->service)
                ->update(['specialty_id' => $groupId, 'lab_category' => $groupName, 'catalog_service_id' => $item($groupId, $t->service, 'test')]);
        }
    }

    private function read(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Dataset not found: {$path}");
        }
        $raw = file_get_contents($path);
        if (str_ends_with($path, '.gz')) {
            $raw = gzdecode($raw);
        }

        return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    }

    private function importAreas(): int
    {
        $rows = collect(config('caresenda.areas'))->map(fn ($a) => [
            'name' => $a[0], 'slug' => Str::slug($a[0]), 'city' => $a[1], 'lat' => $a[2], 'lng' => $a[3],
        ]);
        Area::upsert($rows->all(), ['slug'], ['name', 'city', 'lat', 'lng']);

        $ids = Area::pluck('id', 'slug');
        foreach (config('caresenda.areas') as [$name, $city, $lat, $lng, $aliases]) {
            $needles = array_unique(array_merge([mb_strtolower($name)], $aliases));
            usort($needles, fn ($a, $b) => strlen($b) <=> strlen($a));
            $this->gazetteer[] = ['id' => $ids[Str::slug($name)], 'city' => $city, 'lat' => $lat, 'lng' => $lng, 'needles' => $needles];
        }
        // Longest needles first so "east legon" wins over "legon".
        usort($this->gazetteer, fn ($a, $b) => strlen($b['needles'][0]) <=> strlen($a['needles'][0]));

        return $rows->count();
    }

    /** @return array{0: int, 1: int, 2: array<string, array{name: string, area: string, city: string}>} */
    private function importFacilities(array $facilities): array
    {
        $typeCase = [];
        $geocoded = 0;
        $meta = [];
        $now = now();

        foreach (array_chunk($facilities, self::CHUNK) as $chunk) {
            $rows = [];
            foreach ($chunk as $f) {
                $type = $f['facilityType'] ?? null;
                if ($type) {
                    $type = $typeCase[mb_strtolower($type)] ??= $type; // merge "Dental clinic" / "Dental Clinic"
                }
                $categories = $f['diagnosticCategories'] ?? [];
                $area = $this->locate($f['area'] ?? '', $f['address'] ?? '', $f['city']);
                $geocoded += $area['precision'] === 'area' ? 1 : 0;
                $meta[$f['id']] = ['name' => $f['name'], 'area' => $f['area'] ?? '', 'city' => $f['city']];

                $rows[] = [
                    'id' => $f['id'],
                    'name' => $f['name'],
                    'city' => $f['city'],
                    'area' => $f['area'] ?: null,
                    'area_id' => $area['id'],
                    'district' => $f['district'] ?? null,
                    'address' => $f['address'] ?: null,
                    'phone' => $f['phone'] ?: null,
                    'whatsapp' => $f['whatsapp'] ?: null,
                    'facility_type' => $type,
                    'ownership' => $f['ownership'] ?? null,
                    'diagnostic_categories' => json_encode(array_values($categories)),
                    'has_laboratory' => in_array('laboratory', $categories, true),
                    'has_imaging' => in_array('imaging', $categories, true),
                    'lat' => $area['lat'],
                    'lng' => $area['lng'],
                    'geo_precision' => $area['precision'],
                    'hours' => $f['hours'] ?: null,
                    'issue' => $f['issue'] ?: null,
                    'source' => $f['source'] ?? null,
                    'contact_source' => $f['contactSource'] ?? null,
                    'directory_evidence' => isset($f['directoryEvidence']) ? json_encode($f['directoryEvidence']) : null,
                    'register_evidence' => isset($f['registerEvidence']) ? json_encode($f['registerEvidence']) : null,
                    'search_text' => SearchNormalizer::normalise(implode(' ', [
                        $f['name'], $type, $f['area'] ?? '', $f['address'] ?? '', $f['district'] ?? '', $f['city'],
                        implode(' ', array_map(fn ($c) => config("caresenda.diagnostic_categories.$c", $c), $categories)),
                    ])),
                    'verified_at' => $f['verifiedAt'] ?? null,
                    'collected_at' => $f['collectedAt'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            // Facilities the team has checked, or pinned on the map, keep their details.
            $protected = DB::table('facilities')->whereIn('id', array_column($rows, 'id'))
                ->where(fn ($q) => $q->whereNotNull('verified_at')->orWhere('geo_precision', 'exact'))
                ->pluck('id')->flip();
            $rows = array_values(array_filter($rows, fn ($r) => ! isset($protected[$r['id']])));
            if ($rows) {
                DB::table('facilities')->upsert($rows, ['id'], array_diff(array_keys($rows[0]), ['id', 'created_at', 'verified_at']));
            }
        }

        return [count($facilities), $geocoded, $meta];
    }

    private function importServices(array $records, array $facilityMeta): int
    {
        $now = now();
        $count = 0;
        $records = array_filter($records, fn ($r) => in_array($r['type'], ['test', 'specialist'], true) && isset($facilityMeta[$r['facilityId']]));

        foreach (array_chunk($records, self::CHUNK) as $chunk) {
            $rows = array_map(function ($r) use ($facilityMeta, $now) {
                $f = $facilityMeta[$r['facilityId']];

                return [
                    'id' => $r['id'],
                    'facility_id' => $r['facilityId'],
                    'type' => $r['type'],
                    'service' => $r['service'],
                    'specialist_name' => $r['name'] ?: null,
                    'gender' => $r['gender'] ?: null,
                    'lab_category' => $r['labCategory'] ?? null,
                    'panel_details' => $r['panelDetails'] ?? null,
                    'schedule' => $r['schedule'] ?: null,
                    'days' => json_encode($r['days'] ?? []),
                    'price' => $r['price'] ?: null,
                    'diagnostic_categories' => json_encode($r['diagnosticCategories'] ?? []),
                    'available' => true,
                    'manager_confirmed' => (bool) ($r['managerConfirmed'] ?? false),
                    'confirmation_reported_at' => $r['confirmationReportedAt'] ?? null,
                    'service_evidence' => $r['serviceEvidence'] ?? null,
                    'service_note' => $r['serviceNote'] ?? null,
                    'source' => $r['source'] ?? null,
                    'search_text' => SearchNormalizer::normalise(implode(' ', [
                        $r['service'], $r['labCategory'] ?? '', $r['name'] ?? '', $f['name'], $f['area'], $f['city'], $r['type'] === 'specialist' ? 'specialist' : '',
                    ])),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $chunk);

            // Admin-edited fields (price, schedule, availability, verified_at) are preserved on re-import.
            DB::table('services')->upsert($rows, ['id'], [
                'facility_id', 'type', 'service', 'panel_details',
                'manager_confirmed', 'confirmation_reported_at', 'service_evidence', 'service_note', 'source', 'search_text', 'updated_at',
            ]);
            $count += count($rows);
        }

        return $count;
    }

    /** @return array{id: ?int, lat: float, lng: float, precision: string} */
    private function locate(string $area, string $address, string $city): array
    {
        $haystacks = [' '.$this->clean($area).' ', ' '.$this->clean($address).' '];

        foreach ($haystacks as $hay) {
            foreach ([true, false] as $sameCityOnly) {
                foreach ($this->gazetteer as $g) {
                    if ($sameCityOnly && $g['city'] !== $city) {
                        continue;
                    }
                    if ($g['needles'][0] === 'tema' && ! $sameCityOnly) {
                        continue;
                    }
                    foreach ($g['needles'] as $needle) {
                        if (str_contains($hay, ' '.$this->clean($needle).' ')) {
                            return ['id' => $g['id'], 'lat' => $g['lat'], 'lng' => $g['lng'], 'precision' => $g['needles'][0] === 'tema' ? 'city' : 'area'];
                        }
                    }
                }
            }
        }

        $c = config("caresenda.cities.$city") ?? config('caresenda.cities.Accra');

        return ['id' => null, 'lat' => $c['lat'], 'lng' => $c['lng'], 'precision' => 'city'];
    }

    private function clean(string $s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($s)));
    }
}
