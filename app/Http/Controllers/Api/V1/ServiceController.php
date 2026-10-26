<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\AccessGate;
use App\Services\DirectorySearch;
use App\Services\SearchFilters;
use App\Services\SearchLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(Request $request, DirectorySearch $search, SearchLogger $logger, AccessGate $gate): AnonymousResourceCollection
    {
        $filters = SearchFilters::fromRequest($request);
        $results = $search->services($filters);
        $logger->log($request, $filters, $results->total(), 'api');

        return ServiceResource::collection($results)->additional([
            'summary' => $search->summary($filters),
            'access' => $gate->forSearch($request, $filters),
        ]);
    }

    public function show(Service $service): ServiceResource
    {
        $service->load('facility');
        abort_unless($service->facility?->is_active, 404);

        return new ServiceResource($service);
    }
}
