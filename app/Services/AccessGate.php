<?php

namespace App\Services;

use App\Models\AccessPass;
use App\Models\AccessPlan;
use App\Models\SearchUnlock;
use App\Models\User;
use App\Services\Payments\PaystackClient;
use App\Support\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Who may see details (facility contacts, directions and hours; specialists' names, days and prices;
 * test prices), how search credits are spent, and how Paystack payments become passes. Each pass
 * opens only the areas (AccessPlan::AREAS) its plan includes. Search counts are always public.
 * Visitors without an account can pay for a single search, kept open by GuestCookie.
 * Only enforced while payments are on.
 */
class AccessGate
{
    /** @var array<string, array{until: array<string, ?Carbon>, facilities: array<string, array<string, true>>, signatures: array<string, array<string, Carbon>>, searches_left: int}> */
    private array $grants = [];

    public function __construct(private PaystackClient $paystack, private DirectorySearch $search) {}

    public static function enabled(): bool
    {
        return PaymentSettings::enabled();
    }

    public static function viewer(Request $request): ?User
    {
        return $request->user() ?? $request->user('sanctum');
    }

    /** Whether this user has to pay at all. */
    public static function required(?User $user): bool
    {
        return self::enabled() && ! $user?->isAdmin();
    }

    /** The access area that covers a service listing ("specialist" or "test"). */
    public static function areaForService(?string $type): string
    {
        return $type === 'test' ? 'tests' : 'specialists';
    }

    /**
     * Areas whose details a search shows: facility searches show facilities, the rest show services.
     *
     * @return list<string>
     */
    public static function areasForSearch(SearchFilters $filters): array
    {
        return match ($filters->type) {
            'facility' => ['facilities'],
            'specialist' => ['specialists'],
            'test' => ['tests'],
            default => ['specialists', 'tests'],
        };
    }

    public function allows(Request $request, ?string $facilityId, string $area = 'facilities'): bool
    {
        $user = self::viewer($request);
        if (! self::required($user)) {
            return true;
        }
        $grants = $this->viewerGrants($request, $user);
        if (! $grants) {
            return false;
        }

        return $grants['until'][$area] !== null || isset($grants['facilities'][$area][$facilityId]);
    }

    /** Whether every facility's details are open to this viewer (no paywall, or unlimited facility access). */
    public function opensAllFacilities(Request $request): bool
    {
        $user = self::viewer($request);

        return ! self::required($user) || ($user && $this->grantsFor($user->id)['until']['facilities'] !== null);
    }

    /**
     * Everything currently in force for a user, per area: unlimited access end time, opened
     * facilities and opened searches; plus search credits left.
     *
     * @return array{until: array<string, ?Carbon>, facilities: array<string, array<string, true>>, signatures: array<string, array<string, Carbon>>, searches_left: int}
     */
    public function grantsFor(int $userId): array
    {
        return $this->grants["user:{$userId}"] ??= $this->loadGrants('user_id', $userId);
    }

    /**
     * What a visitor without an account opened with single searches (by their GuestCookie).
     *
     * @return array{until: array<string, ?Carbon>, facilities: array<string, array<string, true>>, signatures: array<string, array<string, Carbon>>, searches_left: int}
     */
    public function grantsForGuest(string $token): array
    {
        return $this->grants["guest:{$token}"] ??= $this->loadGrants('guest_token', $token);
    }

    /** The signed-in user's grants, a guest's by cookie, or null for a visitor who never paid. */
    private function viewerGrants(Request $request, ?User $user): ?array
    {
        if ($user) {
            return $this->grantsFor($user->id);
        }
        $token = GuestCookie::read($request);

        return $token ? $this->grantsForGuest($token) : null;
    }

    /** @return array{until: array<string, ?Carbon>, facilities: array<string, array<string, true>>, signatures: array<string, array<string, Carbon>>, searches_left: int} */
    private function loadGrants(string $owner, int|string $ownerId): array
    {
        $passes = AccessPass::where($owner, $ownerId)->active()->get(['kind', 'areas', 'searches_left', 'expires_at']);
        $unlocks = SearchUnlock::where($owner, $ownerId)->where('expires_at', '>', now())->get(['signature', 'facility_ids', 'areas', 'expires_at']);
        $unlimited = $passes->where('kind', AccessPlan::UNLIMITED);

        $until = [];
        $facilities = [];
        foreach (array_keys(AccessPlan::AREAS) as $area) {
            $until[$area] = $unlimited->filter(fn ($pass) => in_array($area, $pass->coveredAreas(), true))->max('expires_at');
            $facilities[$area] = [];
        }

        $signatures = [];
        foreach ($unlocks as $unlock) {
            foreach ($unlock->coveredAreas() as $area) {
                foreach ($unlock->facility_ids as $id) {
                    $facilities[$area][$id] = true;
                }
                $current = $signatures[$unlock->signature][$area] ?? null;
                $signatures[$unlock->signature][$area] = $current?->isAfter($unlock->expires_at) ? $current : $unlock->expires_at;
            }
        }

        return [
            'until' => $until,
            'facilities' => $facilities,
            'signatures' => $signatures,
            'searches_left' => (int) $passes->where('kind', AccessPlan::SEARCHES)->sum('searches_left'),
        ];
    }

    /** When unlimited access to every area ends; null unless all areas are open. */
    public static function fullAccessUntil(array $grants): ?Carbon
    {
        $until = collect($grants['until']);

        return $until->contains(null) ? null : $until->min();
    }

    /**
     * Access summary sent with search results, so clients know which details are open and
     * whether to offer "use 1 search".
     */
    public function forSearch(Request $request, SearchFilters $filters): array
    {
        $needed = self::areasForSearch($filters);
        $user = self::viewer($request);
        if (! self::required($user)) {
            return ['required' => false, 'unlocked' => true, 'areas' => array_fill_keys($needed, true), 'searches_left' => null, 'full_access_until' => null];
        }
        $grants = $this->viewerGrants($request, $user);

        $openUntil = [];
        foreach ($needed as $area) {
            $openUntil[$area] = $grants ? ($grants['until'][$area] ?? $grants['signatures'][$filters->signature()][$area] ?? null) : null;
        }
        $unlocked = ! in_array(null, $openUntil, true);

        return [
            'required' => true,
            'signed_in' => $user !== null,
            'unlocked' => $unlocked,
            'areas' => array_map(fn ($until) => $until !== null, $openUntil),
            'unlocked_until' => $unlocked ? collect($openUntil)->min()->toIso8601String() : null,
            'searches_left' => $grants['searches_left'] ?? 0,
            'full_access_until' => $grants ? self::fullAccessUntil($grants)?->toIso8601String() : null,
        ];
    }

    /**
     * Spends one search credit to open every facility in a search's results, for the areas
     * the credit's pass covers. Free when the search is already open.
     *
     * @return ?SearchUnlock null when the user has no searches left that cover this search
     */
    public function unlockSearch(User $user, SearchFilters $filters): ?SearchUnlock
    {
        $signature = $filters->signature();
        $needed = self::areasForSearch($filters);

        $unlock = DB::transaction(function () use ($user, $filters, $signature, $needed) {
            $existing = SearchUnlock::where('user_id', $user->id)->where('signature', $signature)->where('expires_at', '>', now())->get()
                ->first(fn ($unlock) => ! array_diff($needed, $unlock->coveredAreas()));
            if ($existing) {
                return $existing;
            }

            // Use the credits that expire soonest first, preferring a pack that covers the whole search.
            $passes = AccessPass::where('user_id', $user->id)->active()
                ->where('kind', AccessPlan::SEARCHES)->where('searches_left', '>', 0)
                ->orderByRaw('expires_at is null')->orderBy('expires_at')->orderBy('id')
                ->lockForUpdate()->get();
            $pass = $passes->first(fn ($pass) => ! array_diff($needed, $pass->coveredAreas()))
                ?? $passes->first(fn ($pass) => array_intersect($needed, $pass->coveredAreas()));
            if (! $pass) {
                return null;
            }
            $pass->decrement('searches_left');

            return $this->openSearch($pass, $filters);
        });

        $this->grants = [];

        return $unlock;
    }

    /** Opens a search's result facilities for the pass owner, for the areas the pass covers. */
    private function openSearch(AccessPass $pass, SearchFilters $filters): SearchUnlock
    {
        return SearchUnlock::create([
            'user_id' => $pass->user_id,
            'guest_token' => $pass->guest_token,
            'access_pass_id' => $pass->id,
            'signature' => $filters->signature(),
            'query' => $filters->q ? mb_substr($filters->q, 0, 120) : null,
            'facility_ids' => $this->search->facilityIds($filters),
            'areas' => $pass->coveredAreas(),
            'expires_at' => now()->addHours(PaymentSettings::unlockHours()),
        ]);
    }

    /** Creates a pending pass and a Paystack checkout. Returns [pass, authorization_url]. */
    public function checkout(User $user, AccessPlan $plan, ?string $callbackUrl = null): array
    {
        return $this->startPayment($plan, $user->email, ['user_id' => $user->id], $callbackUrl);
    }

    /**
     * Checkout for one search that opens as soon as Paystack confirms, for a member or a guest
     * (by GuestCookie token, no account). $filters are the search as the gate checks it. Returns [pass, authorization_url].
     */
    public function checkoutSearch(AccessPlan $plan, SearchFilters $filters, string $email, ?User $user, ?string $guestToken, string $callbackUrl): array
    {
        $search = array_diff_key(get_object_vars($filters), array_flip(['sort', 'page', 'perPage']));

        return $this->startPayment($plan, $email, [
            'user_id' => $user?->id,
            'guest_token' => $user ? null : $guestToken,
            'search' => $search,
        ], $callbackUrl);
    }

    /** @param  array<string, mixed>  $owner  user_id, or guest_token and the search to open */
    private function startPayment(AccessPlan $plan, string $email, array $owner, ?string $callbackUrl): array
    {
        $pass = AccessPass::create($owner + [
            'email' => $email,
            'access_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'kind' => $plan->kind,
            'period' => $plan->period,
            'period_count' => $plan->period_count,
            'areas' => $plan->coveredAreas(),
            'searches_total' => $plan->isUnlimited() ? null : $plan->searches,
            'duration_hours' => $plan->duration_hours,
            'amount' => $plan->price,
            'fee' => $plan->fee(),
            'currency' => PaymentSettings::currency(),
            'reference' => 'CS-'.Str::upper(Str::random(16)),
        ]);

        $checkout = $this->paystack->initialize($email, $pass->total(), $pass->currency, $pass->reference, array_filter([
            'plan' => $plan->name,
            'user_id' => $pass->user_id,
            'guest' => $pass->isGuest() ?: null,
        ]), $callbackUrl);

        return [$pass, $checkout['authorization_url'] ?? null];
    }

    /** Asks Paystack for the latest state of a pending pass. */
    public function refresh(AccessPass $pass): AccessPass
    {
        if ($pass->status === 'pending') {
            $this->apply($pass->reference, $this->paystack->verify($pass->reference));
        }

        return $pass->refresh();
    }

    /**
     * Records a Paystack transaction result (from verify or the webhook). Idempotent: a pass
     * is activated once, and only when the amount (price plus fee) and currency match what was charged.
     */
    public function apply(string $reference, array $transaction): void
    {
        DB::transaction(function () use ($reference, $transaction) {
            $pass = AccessPass::where('reference', $reference)->lockForUpdate()->first();
            if (! $pass || $pass->isPaid()) {
                return;
            }

            $status = $transaction['status'] ?? null;
            if ($status === 'success'
                && (int) ($transaction['amount'] ?? 0) >= $pass->total()
                && strtoupper((string) ($transaction['currency'] ?? '')) === $pass->currency) {
                $paidAt = now();
                $start = $paidAt;
                if ($pass->isUnlimited()) {
                    // Buying while unlimited access to the same areas is still running adds the time on the end.
                    $until = collect($pass->coveredAreas())->map(fn ($area) => $this->grantsFor($pass->user_id)['until'][$area]);
                    $current = $until->contains(null) ? null : $until->min();
                    $start = $current?->isAfter($paidAt) ? $current : $paidAt;
                }

                $pass->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'channel' => $transaction['channel'] ?? null,
                    'searches_left' => $pass->isUnlimited() ? null : $pass->searches_total,
                    'expires_at' => AccessPlan::endsAt($start, $pass->period, $pass->period_count, $pass->duration_hours),
                ]);
                // A single search bought on a results page opens that search straight away.
                if ($pass->search && $pass->searches_left > 0) {
                    $pass->decrement('searches_left');
                    $this->openSearch($pass, new SearchFilters(...$pass->search));
                }
                $this->grants = [];
            } elseif (in_array($status, ['failed', 'reversed'], true)) {
                $pass->update(['status' => 'failed']);
            }
        });
    }
}
