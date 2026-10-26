<?php

namespace App\Services;

class GeoLocator
{
    public function __construct(private DirectoryCache $cache) {}

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Turn raw device coordinates into the nearest known CareSenda area.
     *
     * @return array{lat: float, lng: float, area: ?string, area_id: ?int, city: ?string, distance_to_area_km: ?float, in_coverage: bool, in_country: bool}
     */
    public function resolve(float $lat, float $lng): array
    {
        $nearest = null;
        $best = INF;
        foreach ($this->cache->areas() as $area) {
            $d = self::distanceKm($lat, $lng, $area['lat'], $area['lng']);
            if ($d < $best) {
                [$best, $nearest] = [$d, $area];
            }
        }

        $inBounds = self::withinBounds($lat, $lng, config('caresenda.coverage.bounds'));
        $inCoverage = $inBounds && $nearest && $best <= config('caresenda.coverage.max_match_km');

        return [
            'lat' => round($lat, 6),
            'lng' => round($lng, 6),
            'area' => $inCoverage ? $nearest['name'] : null,
            'area_id' => $inCoverage ? $nearest['id'] : null,
            'city' => $inCoverage ? $nearest['city'] : null,
            'distance_to_area_km' => $nearest ? round($best, 2) : null,
            'in_coverage' => (bool) $inCoverage,
            'in_country' => self::withinBounds($lat, $lng, config('caresenda.coverage.country_bounds')),
        ];
    }

    /** @param  array{south: float, north: float, west: float, east: float}  $b */
    private static function withinBounds(float $lat, float $lng, array $b): bool
    {
        return $lat >= $b['south'] && $lat <= $b['north'] && $lng >= $b['west'] && $lng <= $b['east'];
    }

    /**
     * Facility ids sorted by distance from the origin, optionally within a radius.
     *
     * @param  list<string>|null  $onlyIds  restrict to these facility ids
     * @return array<string, float> id => km, nearest first
     */
    public function rankFacilities(float $lat, float $lng, ?float $radiusKm = null, ?array $onlyIds = null): array
    {
        $index = $this->cache->facilityIndex();
        if ($onlyIds !== null) {
            $index = array_intersect_key($index, array_flip($onlyIds));
        }

        $distances = [];
        foreach ($index as $id => [$fLat, $fLng]) {
            $d = self::distanceKm($lat, $lng, $fLat, $fLng);
            if ($radiusKm === null || $d <= $radiusKm) {
                $distances[$id] = round($d, 2);
            }
        }
        asort($distances);

        return $distances;
    }
}
