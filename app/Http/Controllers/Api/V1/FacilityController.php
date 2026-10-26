<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Services\AccessGate;
use App\Services\DirectorySearch;
use App\Services\GeoLocator;
use App\Services\SearchFilters;
use App\Services\SearchLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FacilityController extends Controller
{
    public function index(Request $request, DirectorySearch $search, SearchLogger $logger, AccessGate $gate): AnonymousResourceCollection
    {
        $filters = SearchFilters::fromRequest($request)->with(['type' => 'facility']);
        $results = $search->facilities($filters);

        if ($filters->q) {
            $logger->log($request, $filters, $results->total(), 'api');
        }

        return FacilityResource::collection($results)->additional(['access' => $gate->forSearch($request, $filters)]);
    }

    public function nearby(Request $request, DirectorySearch $search): AnonymousResourceCollection
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0.5', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'diagnostic' => ['nullable', 'in:'.implode(',', array_keys(config('caresenda.diagnostic_categories')))],
        ]);

        return FacilityResource::collection($search->nearby(
            (float) $data['lat'], (float) $data['lng'], (float) ($data['radius'] ?? 10), (int) ($data['limit'] ?? 10), $data['diagnostic'] ?? null,
        ));
    }

    public function show(Request $request, Facility $facility): FacilityResource
    {
        $origin = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ]);

        abort_unless($facility->is_active, 404);

        $facility->load(['services' => fn ($q) => $q->where('available', true)->orderBy('type')->orderBy('service')]);
        if (isset($origin['lat']) && $facility->lat) {
            $facility->distanceKm = round(GeoLocator::distanceKm((float) $origin['lat'], (float) $origin['lng'], $facility->lat, $facility->lng), 2);
        }

        return new FacilityResource($facility);
    }
}
