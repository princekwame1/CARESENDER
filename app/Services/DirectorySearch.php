<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Service;
use App\Support\SearchNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DirectorySearch
{
    public function __construct(private GeoLocator $geo, private DirectoryCache $cache) {}

    /** Specialists and diagnostic tests matching the filters. */
    public function services(SearchFilters $f): LengthAwarePaginator
    {
        $query = $this->serviceQuery($f);
        if ($query === null) {
            return $this->paginate(collect(), 0, $f);
        }

        if ($f->sort === 'distance' && $f->hasOrigin()) {
            return $this->paginateByDistance($query, 'facility_id', Service::query()->with('facility'), $f);
        }

        $query->with('facility')
            ->orderByDesc('manager_confirmed')
            ->orderBy($f->sort === 'name' ? 'service' : 'type')
            ->orderBy('service');

        return $this->withDistances($query->paginate($f->perPage, ['*'], 'page', $f->page), $f, fn (Service $s) => $s->facility_id);
    }

    /** Facilities matching the filters (by name, area, type, district). */
    public function facilities(SearchFilters $f): LengthAwarePaginator
    {
        $query = $this->facilityQuery($f);
        if ($query === null) {
            return $this->paginate(collect(), 0, $f);
        }

        if ($f->sort === 'distance' && $f->hasOrigin()) {
            return $this->paginateByDistance($query, 'id', Facility::query(), $f);
        }

        $query->when($f->sort !== 'name', fn ($q) => $q->orderByDesc('services_count'))->orderBy('name');

        return $this->withDistances($query->paginate($f->perPage, ['*'], 'page', $f->page), $f, fn (Facility $x) => $x->id);
    }

    /** Every facility in the results (all pages), used to open a whole search after a search credit is spent. */
    public function facilityIds(SearchFilters $f): array
    {
        $query = $f->type === 'facility'
            ? $this->facilityQuery($f)?->select('id')
            : $this->serviceQuery($f)?->select('facility_id')->distinct();

        return $query ? $query->toBase()->pluck($f->type === 'facility' ? 'id' : 'facility_id')->map(fn ($id) => (string) $id)->all() : [];
    }

    /** Lightweight counts for the results header (cheap aggregate queries). */
    public function summary(SearchFilters $f): array
    {
        $services = $this->serviceQuery($f);
        $facilities = $this->facilityQuery($f);

        $serviceStats = $services?->toBase()
            ->selectRaw('count(*) as total, sum(manager_confirmed) as confirmed, count(distinct facility_id) as facilities')
            ->first();

        return [
            'services' => (int) ($serviceStats->total ?? 0),
            'confirmed_services' => (int) ($serviceStats->confirmed ?? 0),
            'service_facilities' => (int) ($serviceStats->facilities ?? 0),
            'facilities' => $facilities?->count() ?? 0,
        ];
    }

    public function nearby(float $lat, float $lng, float $radiusKm = 10, int $limit = 10, ?string $diagnostic = null): Collection
    {
        $ranked = $this->geo->rankFacilities($lat, $lng, $radiusKm);
        if ($diagnostic) {
            $ids = Facility::whereIn('id', array_keys($ranked))->whereJsonContains('diagnostic_categories', $diagnostic)->pluck('id')->all();
            $ranked = array_intersect_key($ranked, array_flip($ids));
        }
        $ranked = array_slice($ranked, 0, $limit, true);

        $models = Facility::query()->findMany(array_keys($ranked))->keyBy('id');

        return collect($ranked)->map(function ($km, $id) use ($models) {
            $facility = $models[$id] ?? null;
            if ($facility) {
                $facility->distanceKm = $km;
            }

            return $facility;
        })->filter()->values();
    }

    private function serviceQuery(SearchFilters $f): ?Builder
    {
        if ($f->type === 'facility') {
            return null;
        }

        $query = Service::query()
            ->where('available', true)
            ->whereHas('facility', fn ($q) => $q->where('is_active', true))
            ->when(in_array($f->type, ['test', 'specialist'], true),
                fn ($q) => $q->where('type', $f->type),
                fn ($q) => $q->whereIn('type', ['test', 'specialist']))
            ->when($f->diagnostic, fn ($q) => $q->whereJsonContains('diagnostic_categories', $f->diagnostic))
            ->when($f->day, fn ($q) => $q->whereJsonContains('days', $f->day))
            ->when($f->confirmedOnly, fn ($q) => $q->where('manager_confirmed', true));

        $this->applyWords($query, $f->q);

        return $this->applyFacilityScope($query, 'facility_id', $f);
    }

    private function facilityQuery(SearchFilters $f): ?Builder
    {
        if (in_array($f->type, ['test', 'specialist'], true) || $f->day) {
            return null;
        }

        $query = Facility::query()
            ->active()
            ->when($f->diagnostic, fn ($q) => $q->whereJsonContains('diagnostic_categories', $f->diagnostic));

        $this->applyWords($query, $f->q);

        return $this->applyFacilityScope($query, 'id', $f);
    }

    private function applyWords(Builder $query, ?string $q): void
    {
        foreach (SearchNormalizer::words($q) as $word) {
            $query->where('search_text', 'like', '%'.addcslashes($word, '%_\\').'%');
        }
    }

    /** City and radius filters are resolved against the cached facility index. */
    private function applyFacilityScope(Builder $query, string $column, SearchFilters $f): ?Builder
    {
        if (! $f->city && ! $f->radius) {
            return $query;
        }

        $index = $this->cache->facilityIndex();
        if ($f->city) {
            $index = array_filter($index, fn ($row) => $row[2] === $f->city);
        }
        $ids = array_keys($index);
        if ($f->radius && $f->hasOrigin()) {
            $ids = array_keys($this->geo->rankFacilities($f->lat, $f->lng, $f->radius, $ids));
        }

        return $ids ? $query->whereIn($column, $ids) : null;
    }

    /**
     * Distance ordering: pluck only (id, facility) pairs, rank in PHP against
     * the cached coordinates, then hydrate just the requested page.
     */
    private function paginateByDistance(Builder $query, string $facilityColumn, Builder $hydrate, SearchFilters $f): LengthAwarePaginator
    {
        $pairs = $query->toBase()->pluck($facilityColumn, $query->getModel()->getKeyName());
        $ranked = $this->geo->rankFacilities($f->lat, $f->lng, null, $pairs->unique()->values()->all());

        $sorted = $pairs->map(fn ($facilityId) => $ranked[$facilityId] ?? INF)->sort();
        $pageIds = $sorted->slice(($f->page - 1) * $f->perPage, $f->perPage);

        $models = $hydrate->findMany($pageIds->keys()->all())->keyBy(fn (Model $m) => $m->getKey());
        $items = $pageIds->map(function ($km, $id) use ($models) {
            $model = $models[$id] ?? null;
            if ($model) {
                $model->distanceKm = is_finite($km) ? $km : null;
                if ($model->relationLoaded('facility')) {
                    $model->facility->distanceKm = $model->distanceKm;
                }
            }

            return $model;
        })->filter()->values();

        return $this->paginate($items, $sorted->count(), $f);
    }

    private function withDistances(LengthAwarePaginator $page, SearchFilters $f, callable $facilityId): LengthAwarePaginator
    {
        if ($f->hasOrigin()) {
            $index = $this->cache->facilityIndex();
            $page->getCollection()->each(function ($m) use ($index, $f, $facilityId) {
                if ($row = $index[$facilityId($m)] ?? null) {
                    $m->distanceKm = round(GeoLocator::distanceKm($f->lat, $f->lng, $row[0], $row[1]), 2);
                    if ($m->relationLoaded('facility')) {
                        $m->facility->distanceKm = $m->distanceKm;
                    }
                }
            });
        }

        return $page;
    }

    private function paginate(Collection $items, int $total, SearchFilters $f): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, $total, $f->perPage, $f->page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }
}
