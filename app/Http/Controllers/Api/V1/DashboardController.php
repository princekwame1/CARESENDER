<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FacilityResource;
use App\Services\DirectorySearch;
use App\Services\DirectoryStats;
use App\Services\GeoLocator;
use App\Support\SpecialtyPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** One round-trip home screen for the mobile app. */
    public function __invoke(Request $request, DirectoryStats $stats, GeoLocator $geo, DirectorySearch $search): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius' => ['nullable', 'numeric', 'min:0.5', 'max:50'],
        ]);

        $location = null;
        $nearest = [];
        if (isset($data['lat'])) {
            $location = $geo->resolve((float) $data['lat'], (float) $data['lng']);
            if ($location['in_coverage']) {
                $radius = (float) ($data['radius'] ?? 5);
                $location['nearby'] = $stats->around($location['lat'], $location['lng'], $radius);
                $nearest = FacilityResource::collection($search->nearby($location['lat'], $location['lng'], max($radius, 10), 8))->resolve($request);
            }
        }

        return response()->json(['data' => [
            'location' => $location,
            'totals' => $stats->overview(),
            'nearest_facilities' => $nearest,
            'top_specialties' => $specialties = $stats->topSpecialties(8),
            // Specialty name → card photo (uploaded, built-in or the general specialist photo).
            'specialty_images' => (object) collect($specialties)->keys()->mapWithKeys(fn ($name) => [$name => SpecialtyPhoto::cardUrl($name)])->all(),
            'common_tests' => config('caresenda.common_tests'),
        ]]);
    }
}
