<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFacilityServicesRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Services\DirectoryStats;
use App\Services\FacilityUpdater;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function stats(DirectoryStats $stats): JsonResponse
    {
        return response()->json(['data' => [
            'totals' => $stats->overview(),
            'facility_types' => $stats->facilityTypes(),
            'top_specialties' => $stats->topSpecialties(),
            'searches' => $stats->searchActivity(),
        ]]);
    }

    public function updateFacility(UpdateFacilityServicesRequest $request, Facility $facility, FacilityUpdater $updater): FacilityResource
    {
        return new FacilityResource($updater->apply($facility, $request->validated())->load('services'));
    }
}
