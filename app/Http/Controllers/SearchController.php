<?php

namespace App\Http\Controllers;

use App\Models\AccessPlan;
use App\Services\AccessGate;
use App\Services\DirectorySearch;
use App\Services\GeoLocator;
use App\Services\SearchFilters;
use App\Services\SearchLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, DirectorySearch $search, SearchLogger $logger, GeoLocator $geo, AccessGate $gate): View
    {
        $filters = SearchFilters::fromRequest($request, useLocationCookie: true);
        $summary = $search->summary($filters);

        $tab = $request->query('tab');
        if (! in_array($tab, ['services', 'facilities'], true)) {
            $tab = $filters->type === 'facility' || ($summary['services'] === 0 && $summary['facilities'] > 0) ? 'facilities' : 'services';
        }

        // Counts are always public; the list only shows when the viewer's plan opens part of it.
        $access = $gate->forSearch($request, self::tabFilters($filters, $tab));
        $locked = ! in_array(true, $access['areas'], true);
        $neededAreas = AccessGate::areasForSearch(self::tabFilters($filters, $tab));

        $results = null;
        if (! $locked) {
            $results = $tab === 'facilities' ? $search->facilities($filters) : $search->services($filters);
            $results->withQueryString();
        }

        $logger->log($request, $filters, $summary['services'] + $summary['facilities'], 'web');

        return view('search', [
            'filters' => $filters,
            'summary' => $summary,
            'tab' => $tab,
            'results' => $results,
            'access' => $access,
            'locked' => $locked,
            'plans' => $locked ? self::plansFor($neededAreas) : collect(),
            'lockedAreas' => implode(' or ', array_map(fn ($area) => mb_strtolower(AccessPlan::AREAS[$area]), $neededAreas)),
            // One search credit opens a search that has a query (otherwise it would open the whole directory).
            'unlock' => $locked && $filters->q ? array_filter([
                'q' => $filters->q, 'type' => $filters->type, 'city' => $filters->city, 'diagnostic' => $filters->diagnostic,
                'day' => $filters->day, 'radius' => $filters->radius, 'confirmed' => $filters->confirmedOnly ? 1 : null, 'tab' => $tab,
            ]) : null,
            'location' => $filters->hasOrigin() ? $geo->resolve($filters->lat, $filters->lng) : null,
        ]);
    }

    /** Spend one search credit to open the results the viewer is looking at. */
    public function unlock(Request $request, AccessGate $gate): RedirectResponse
    {
        $filters = SearchFilters::fromRequest($request, useLocationCookie: true);
        $tab = $request->input('tab') === 'facilities' ? 'facilities' : 'services';
        $back = redirect()->route('search', array_filter([
            'q' => $filters->q, 'type' => $filters->type !== 'all' ? $filters->type : null, 'city' => $filters->city,
            'diagnostic' => $filters->diagnostic, 'day' => $filters->day, 'radius' => $filters->radius,
            'confirmed' => $filters->confirmedOnly ? 1 : null, 'tab' => $tab,
        ]));

        if (! filled($filters->q)) {
            return $back->withErrors(['unlock' => 'Search for something first, then open the results.']);
        }
        if (! $gate->unlockSearch($request->user(), self::tabFilters($filters, $tab))) {
            return $back->withErrors(['unlock' => 'You have no searches left for this. Choose a plan to keep going.']);
        }

        return $back->with('status', 'Results opened.');
    }

    /** The facilities tab lists facility matches, so it is opened like a facility search. */
    private static function tabFilters(SearchFilters $filters, string $tab): SearchFilters
    {
        return $tab === 'facilities' ? $filters->with(['type' => 'facility']) : $filters;
    }

    /** Plans on sale that open at least one of the given areas. */
    private static function plansFor(array $areas): Collection
    {
        return AccessPlan::available()->get()->filter(fn ($plan) => array_intersect($areas, $plan->coveredAreas()))->values();
    }
}
