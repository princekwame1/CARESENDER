<?php

namespace App\Http\Controllers;

use App\Services\DirectorySearch;
use App\Services\DirectoryStats;
use App\Services\GeoLocator;
use App\Services\LocationCookie;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private DirectoryStats $stats, private GeoLocator $geo, private DirectorySearch $search) {}

    public function __invoke(Request $request, DirectoryStats $stats): View
    {
        return view('home', [
            'overview' => $stats->overview(),
            'specialties' => $stats->homeSpecialties(8),
            ...$this->nearbyData($request),
        ]);
    }

    /** HTML fragment swapped in by the client right after location detection. */
    public function panel(Request $request): View
    {
        return view('partials.nearby', $this->nearbyData($request));
    }

    private function nearbyData(Request $request): array
    {
        $location = null;
        $nearby = null;
        $nearest = collect();

        // When the browser has already shared its location, render the panel server-side (no layout shift).
        if ($cookie = LocationCookie::read($request)) {
            $location = $this->geo->resolve($cookie['lat'], $cookie['lng']);
            if ($location['in_coverage']) {
                $nearby = $this->stats->around($location['lat'], $location['lng']);
                $nearest = $this->search->nearby($location['lat'], $location['lng'], 15, 12);
            }
        }

        return compact('location', 'nearby', 'nearest');
    }
}
