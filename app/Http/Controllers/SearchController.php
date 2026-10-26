<?php

namespace App\Http\Controllers;

use App\Models\AccessPass;
use App\Models\AccessPlan;
use App\Services\AccessGate;
use App\Services\DirectorySearch;
use App\Services\GeoLocator;
use App\Services\GuestCookie;
use App\Services\Payments\PaystackClient;
use App\Services\SearchFilters;
use App\Services\SearchLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class SearchController extends Controller
{
    public function __invoke(Request $request, DirectorySearch $search, SearchLogger $logger, GeoLocator $geo, AccessGate $gate, PaystackClient $paystack): View
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

        $unlock = $locked && $filters->q ? self::searchParams($filters, $tab) : null;

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
            'unlock' => $unlock,
            // …and so does paying for a single search, which needs no account.
            'singleSearch' => $unlock && AccessGate::enabled() && $paystack->configured() ? AccessPlan::singleSearchFor($neededAreas) : null,
            'location' => $filters->hasOrigin() ? $geo->resolve($filters->lat, $filters->lng) : null,
        ]);
    }

    /** Spend one search credit to open the results the viewer is looking at. */
    public function unlock(Request $request, AccessGate $gate): RedirectResponse
    {
        $filters = SearchFilters::fromRequest($request, useLocationCookie: true);
        $tab = $request->input('tab') === 'facilities' ? 'facilities' : 'services';
        $back = redirect()->route('search', self::searchParams($filters, $tab));

        if (! filled($filters->q)) {
            return $back->withErrors(['unlock' => 'Search for something first, then open the results.']);
        }
        if (! $gate->unlockSearch($request->user(), self::tabFilters($filters, $tab))) {
            return $back->withErrors(['unlock' => 'You have no searches left for this. Choose a plan to keep going.']);
        }

        return $back->with('status', 'Results opened.');
    }

    /** Pay for one search with Paystack and open its results. Visitors only need an email address, not an account. */
    public function buy(Request $request, AccessGate $gate, PaystackClient $paystack): RedirectResponse
    {
        $filters = SearchFilters::fromRequest($request, useLocationCookie: true);
        $tab = $request->input('tab') === 'facilities' ? 'facilities' : 'services';
        $back = redirect()->route('search', self::searchParams($filters, $tab));
        $user = $request->user();
        $email = $user ? $user->email : $request->validate(['email' => ['required', 'email', 'max:255']])['email'];

        $searched = self::tabFilters($filters, $tab);
        $plan = AccessPlan::singleSearchFor(AccessGate::areasForSearch($searched));
        if (! filled($filters->q)) {
            return $back->withErrors(['unlock' => 'Search for something first, then open the results.']);
        }
        if (! $plan || ! AccessGate::enabled() || ! $paystack->configured()) {
            return $back->withErrors(['unlock' => 'Payments are temporarily unavailable. Please try again later.']);
        }
        if ($gate->forSearch($request, $searched)['unlocked']) {
            return $back;
        }

        try {
            [, $url] = $gate->checkoutSearch($plan, $searched, $email, $user, $user ? null : GuestCookie::issue($request), route('search.paid'));
        } catch (RuntimeException $e) {
            report($e);
            $url = null;
        }

        return $url ? redirect()->away($url) : $back->withErrors(['unlock' => 'We could not start the payment. Please try again.']);
    }

    /** Paystack sends the payer here with ?reference=…; check the payment and go back to the search, now open. */
    public function paid(Request $request, AccessGate $gate): RedirectResponse
    {
        $reference = (string) ($request->query('reference') ?? $request->query('trxref'));
        $user = $request->user();
        $guestToken = GuestCookie::read($request);
        $pass = AccessPass::where('reference', $reference)->whereNotNull('search')
            ->where(fn ($query) => $user ? $query->where('user_id', $user->id) : $query->whereNull('user_id')->where('guest_token', $guestToken ?? ''))
            ->first();
        if (! $pass) {
            return redirect()->route('home')->withErrors(['unlock' => 'We couldn’t find that payment.']);
        }

        try {
            $pass = $gate->refresh($pass);
        } catch (RuntimeException $e) {
            report($e); // the webhook will still open it
        }

        $filters = new SearchFilters(...$pass->search);
        $back = redirect()->route('search', self::searchParams($filters, $filters->type === 'facility' ? 'facilities' : 'services'));

        return match ($pass->status) {
            'paid' => $back->with('status', 'Payment received. The results are open.'),
            'failed' => $back->withErrors(['unlock' => 'The payment didn’t go through, so you haven’t been charged.']),
            default => $back->with('status', 'We’re still waiting for Paystack to confirm your payment. Refresh this page in a minute.'),
        };
    }

    /**
     * The query string that brings the viewer back to this search on this tab.
     *
     * @return array<string, string|int|float>
     */
    private static function searchParams(SearchFilters $filters, string $tab): array
    {
        return array_filter([
            'q' => $filters->q, 'type' => $filters->type !== 'all' ? $filters->type : null, 'city' => $filters->city,
            'diagnostic' => $filters->diagnostic, 'day' => $filters->day, 'radius' => $filters->radius,
            'confirmed' => $filters->confirmedOnly ? 1 : null, 'tab' => $tab,
        ]);
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
