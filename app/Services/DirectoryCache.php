<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Facility;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned, memoised cache for directory lookups. Any write to the directory
 * calls flush(), which bumps the version so every derived key goes stale at once.
 */
class DirectoryCache
{
    private const VERSION_KEY = 'caresenda:version';

    public function version(): int
    {
        return (int) Cache::memo()->rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public function flush(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
        Cache::memo()->forget(self::VERSION_KEY);
    }

    public function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        return Cache::memo()->remember(
            "caresenda:v{$this->version()}:{$key}",
            $ttl ?? config('caresenda.cache_ttl'),
            $callback,
        );
    }

    /**
     * Compact facility index used for distance maths and map markers.
     *
     * @return array<string, array{0: float, 1: float, 2: string, 3: string, 4: string|null, 5: bool}>
     *                                                                                                 id => [lat, lng, city, name, facility_type, has_lab]
     */
    public function facilityIndex(): array
    {
        return $this->remember('facility-index', fn () => Facility::query()
            ->active()
            ->whereNotNull('lat')
            ->get(['id', 'lat', 'lng', 'city', 'name', 'facility_type', 'has_laboratory'])
            ->mapWithKeys(fn (Facility $f) => [$f->id => [$f->lat, $f->lng, $f->city, $f->name, $f->facility_type, $f->has_laboratory]])
            ->all());
    }

    /** @return list<array{id: int, name: string, city: string, lat: float, lng: float}> */
    public function areas(): array
    {
        return $this->remember('areas', fn () => Area::query()
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'lat', 'lng'])
            ->toArray());
    }
}
