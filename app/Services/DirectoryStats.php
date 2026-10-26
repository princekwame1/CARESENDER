<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\SearchLog;
use App\Models\Service;
use App\Models\Specialty;
use Illuminate\Support\Facades\DB;

/** Aggregates for the public and admin dashboards; all results are cached. */
class DirectoryStats
{
    public function __construct(private DirectoryCache $cache, private GeoLocator $geo) {}

    public function overview(): array
    {
        return $this->cache->remember('stats:overview', function () {
            $services = Service::whereHas('facility', fn ($q) => $q->where('is_active', true))->toBase()
                ->selectRaw("sum(type = 'test') as tests, sum(type = 'specialist') as specialists, sum(manager_confirmed) as confirmed, count(*) as total")
                ->first();

            return [
                'facilities' => Facility::active()->count(),
                'with_phone' => Facility::active()->whereNotNull('phone')->count(),
                'laboratories' => Facility::active()->where('has_laboratory', true)->count(),
                'imaging_centres' => Facility::active()->where('has_imaging', true)->count(),
                'tests' => (int) $services->tests,
                'specialists' => (int) $services->specialists,
                'confirmed_services' => (int) $services->confirmed,
                'services' => (int) $services->total,
                'areas' => Facility::distinct()->count('area_id'),
                'by_city' => Facility::groupBy('city')->orderByDesc('n')->pluck(DB::raw('count(*) as n'), 'city')->all(),
            ];
        });
    }

    /** Summary of what is around a point: used by the "near you" dashboard panel. */
    public function around(float $lat, float $lng, float $radiusKm = 5): array
    {
        $key = sprintf('stats:around:%.3f,%.3f,%s', $lat, $lng, $radiusKm);

        return $this->cache->remember($key, function () use ($lat, $lng, $radiusKm) {
            $ranked = $this->geo->rankFacilities($lat, $lng, $radiusKm);
            $ids = array_keys($ranked);

            $services = $ids ? Service::whereIn('facility_id', $ids)->where('available', true)->toBase()
                ->selectRaw("sum(type = 'test') as tests, sum(type = 'specialist') as specialists")->first() : null;

            return [
                'radius_km' => $radiusKm,
                'facilities' => count($ids),
                'laboratories' => $ids ? Facility::whereIn('id', $ids)->where('has_laboratory', true)->count() : 0,
                'tests' => (int) ($services->tests ?? 0),
                'specialists' => (int) ($services->specialists ?? 0),
                'nearest_km' => $ranked ? reset($ranked) : null,
            ];
        }, 600);
    }

    public function facilityTypes(int $limit = 8): array
    {
        return $this->cache->remember("stats:types:$limit", fn () => Facility::whereNotNull('facility_type')
            ->groupBy('facility_type')->orderByDesc('n')->limit($limit)
            ->pluck(DB::raw('count(*) as n'), 'facility_type')->all());
    }

    /** Specialties chosen for the home page in the dashboard (falls back to the most listed). */
    public function homeSpecialties(int $limit = 10): array
    {
        $chosen = $this->cache->remember("stats:home-specialties:$limit", fn () => Specialty::active()->clinical()
            ->where('show_on_home', true)
            ->withCount(['services' => fn ($q) => $q->where('available', true)->whereHas('facility', fn ($f) => $f->where('is_active', true))])
            ->orderBy('sort_order')->orderBy('name')->limit($limit)
            ->get()->pluck('services_count', 'name')->all());

        return $chosen ?: $this->topSpecialties($limit);
    }

    public function topSpecialties(int $limit = 10): array
    {
        return $this->cache->remember("stats:specialties:$limit", fn () => Service::where('type', 'specialist')
            ->groupBy('service')->orderByDesc('n')->limit($limit)
            ->pluck(DB::raw('count(*) as n'), 'service')->all());
    }

    public function labCategories(int $limit = 8): array
    {
        return $this->cache->remember("stats:labcats:$limit", fn () => Service::whereNotNull('lab_category')
            ->groupBy('lab_category')->orderByDesc('n')->limit($limit)
            ->pluck(DB::raw('count(*) as n'), 'lab_category')->all());
    }

    /** Admin-only; not cached so the team sees live search demand. */
    public function searchActivity(int $days = 14): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $daily = SearchLog::where('created_at', '>=', $since)
            ->groupBy('d')->pluck(DB::raw('count(*) as n'), DB::raw('date(created_at) as d'));

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $since->copy()->addDays($i)->toDateString();
            $series[$d] = (int) ($daily[$d] ?? 0);
        }

        return [
            'daily' => $series,
            'total' => array_sum($series),
            'located' => SearchLog::where('created_at', '>=', $since)->whereNotNull('lat')->count(),
            'zero_results' => SearchLog::where('created_at', '>=', $since)->where('results_count', 0)->whereNotNull('query')->count(),
            'top_queries' => SearchLog::where('created_at', '>=', $since)->whereNotNull('query')->where('query', '!=', '')
                ->groupBy(DB::raw('lower(query)'))->orderByDesc('n')->limit(8)
                ->pluck(DB::raw('count(*) as n'), DB::raw('lower(query) as q'))
                ->mapWithKeys(fn ($n, $q) => [ucfirst($q) => $n])->all(),
            'top_areas' => SearchLog::where('created_at', '>=', $since)->whereNotNull('area')
                ->groupBy('area')->orderByDesc('n')->limit(8)->pluck(DB::raw('count(*) as n'), 'area')->all(),
            'channels' => SearchLog::where('created_at', '>=', $since)->groupBy('channel')->pluck(DB::raw('count(*) as n'), 'channel')->all(),
        ];
    }
}
