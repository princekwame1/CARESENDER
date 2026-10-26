<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccessPass;
use App\Models\AccessPlan;
use App\Services\AccessGate;
use App\Services\Payments\PaystackClient;
use App\Services\SearchFilters;
use App\Support\PaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AccessController extends Controller
{
    /** Plans on sale (public, so the app can show prices before sign-in). */
    public function plans(): JsonResponse
    {
        return response()->json(['data' => self::planList()]);
    }

    /** What the signed-in user has right now. */
    public function show(Request $request, AccessGate $gate): JsonResponse
    {
        $user = $request->user();
        $grants = $gate->grantsFor($user->id);

        return response()->json(['data' => [
            'required' => AccessGate::required($user),
            'full_access_until' => AccessGate::fullAccessUntil($grants)?->toIso8601String(),
            'access_until' => array_map(fn ($until) => $until?->toIso8601String(), $grants['until']),
            'searches_left' => $grants['searches_left'],
            'unlock_hours' => PaymentSettings::unlockHours(),
            'passes' => AccessPass::where('user_id', $user->id)->active()->latest('paid_at')->get()->map->toApi(),
            'plans' => self::planList(),
        ]]);
    }

    /** Start a Paystack checkout for a plan; the app opens `authorization_url` in the browser. */
    public function store(Request $request, AccessGate $gate, PaystackClient $paystack): JsonResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer']]);

        abort_unless(AccessGate::enabled(), 409, 'Payments are not switched on yet.');
        abort_unless($paystack->configured(), 503, 'Payments are temporarily unavailable.');
        $plan = AccessPlan::available()->findOrFail($data['plan_id']);

        try {
            [$pass, $url] = $gate->checkout($request->user(), $plan);
        } catch (RuntimeException $e) {
            report($e);
            abort(502, 'We could not start the payment. Please try again.');
        }

        return response()->json(['data' => $pass->toApi() + ['authorization_url' => $url]], 201);
    }

    /** Payment status; checks with Paystack while still pending (e.g. right after the user returns from checkout). */
    public function status(Request $request, AccessGate $gate, string $reference): JsonResponse
    {
        $pass = AccessPass::where('user_id', $request->user()->id)->where('reference', $reference)->firstOrFail();

        try {
            $pass = $gate->refresh($pass);
        } catch (RuntimeException $e) {
            report($e); // keep the last known status
        }

        return response()->json(['data' => $pass->toApi()]);
    }

    /** Spend one search credit to open a search's results. Send the same filters as the search itself. */
    public function unlock(Request $request, AccessGate $gate): JsonResponse
    {
        $filters = SearchFilters::fromRequest($request);
        $user = $request->user();

        if (! AccessGate::required($user)) {
            return response()->json(['data' => $gate->forSearch($request, $filters)]);
        }
        // A query is required, otherwise one credit would open the whole directory.
        abort_unless(filled($filters->q), 422, 'Search for something first, then open the results.');

        if (! $gate->forSearch($request, $filters)['unlocked'] && ! $gate->unlockSearch($user, $filters)) {
            abort(402, 'You have no searches left. Choose a plan to keep going.');
        }

        return response()->json(['data' => $gate->forSearch($request, $filters)]);
    }

    /** Paystack webhook: activates passes even if the user never comes back to the app. */
    public function webhook(Request $request, AccessGate $gate, PaystackClient $paystack): JsonResponse
    {
        abort_unless($paystack->validSignature($request->getContent(), $request->header('x-paystack-signature')), 401);

        $event = $request->input('event');
        $reference = $request->input('data.reference');
        if ($reference && in_array($event, ['charge.success', 'charge.failed'], true)) {
            $gate->apply($reference, (array) $request->input('data'));
        } else {
            Log::info('Paystack webhook ignored', ['event' => $event]);
        }

        return response()->json(['received' => true]);
    }

    public static function planList(): array
    {
        $currency = PaymentSettings::currency();

        return AccessPlan::available()->get()->map->toApi($currency)->all();
    }
}
