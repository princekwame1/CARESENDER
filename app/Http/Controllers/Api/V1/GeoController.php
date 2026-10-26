<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccessGate;
use App\Services\DirectoryCache;
use App\Services\DirectoryStats;
use App\Services\GeoLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    /** Resolve device coordinates to the nearest CareSenda area, plus what is around it. */
    public function resolve(Request $request, GeoLocator $geo, DirectoryStats $stats): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0.5', 'max:50'],
        ]);

        $location = $geo->resolve((float) $data['lat'], (float) $data['lng']);
        $location['nearby'] = $location['in_coverage']
            ? $stats->around($location['lat'], $location['lng'], (float) ($data['radius'] ?? 5))
            : null;

        return response()->json(['data' => $location]);
    }

    public function areas(DirectoryCache $cache): JsonResponse
    {
        return response()->json(['data' => $cache->areas()]);
    }

    /**
     * Compact marker list for map views: [id, lat, lng, name, type, has_lab]. Without facility access
     * (when payments are on) a marker is only a dot rounded to about 1 km: no id, name or type.
     */
    public function markers(Request $request, DirectoryCache $cache, AccessGate $gate): JsonResponse
    {
        $city = $request->validate(['city' => ['nullable', 'in:'.implode(',', array_keys(config('caresenda.cities')))]])['city'] ?? null;
        $openAll = $gate->opensAllFacilities($request);

        $markers = [];
        foreach ($cache->facilityIndex() as $id => [$lat, $lng, $fCity, $name, $type, $lab]) {
            if ($city && $city !== $fCity) {
                continue;
            }
            $markers[] = $openAll || $gate->allows($request, (string) $id)
                ? [$id, $lat, $lng, $name, $type, $lab]
                : [null, round($lat, 2), round($lng, 2), null, null, $lab];
        }

        return response()->json([
            'fields' => ['id', 'lat', 'lng', 'name', 'facility_type', 'has_laboratory'],
            'locked' => ! $openAll,
            'data' => $markers,
        ]);
    }
}
