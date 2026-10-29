<?php

namespace App\Http\Controllers;

use App\Models\AccessPlan;
use App\Models\Facility;
use App\Services\AccessGate;
use App\Services\GeoLocator;
use App\Services\LocationCookie;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function show(Request $request, Facility $facility, GeoLocator $geo, AccessGate $gate): View
    {
        abort_unless($facility->is_active || $request->user()?->isStaff(), 404);

        $facility->load(['services' => fn ($q) => $q->where('available', true)->orderBy('service')]);

        if (($loc = LocationCookie::read($request)) && $facility->lat && $geo->resolve($loc['lat'], $loc['lng'])['in_coverage']) {
            $facility->distanceKm = round(GeoLocator::distanceKm($loc['lat'], $loc['lng'], $facility->lat, $facility->lng), 2);
        }

        return view('facility', [
            'facility' => $facility,
            'specialists' => $facility->services->where('type', 'specialist'),
            'tests' => $facility->services->where('type', 'test')->groupBy(fn ($s) => $s->lab_category ?: 'Other tests')->sortKeys(),
            // Counts always show; each area's details need a plan that opens it.
            'open' => collect(AccessPlan::AREAS)->map(fn ($label, $area) => $gate->allows($request, $facility->id, $area))->all(),
            'plans' => AccessPlan::available()->get(),
        ]);
    }
}
