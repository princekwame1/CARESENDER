<?php

namespace App\Http\Controllers;

use App\Models\AccessPass;
use App\Models\AccessPlan;
use App\Services\AccessGate;
use App\Services\Payments\PaystackClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/** Plans on the website: see what's on sale, pay with Paystack, and come back with access open. */
class PlanController extends Controller
{
    public function index(Request $request, AccessGate $gate, PaystackClient $paystack): View
    {
        $user = $request->user();

        return view('plans', [
            'plans' => AccessPlan::available()->get(),
            'grants' => $user ? $gate->grantsFor($user->id) : null,
            'passes' => $user ? AccessPass::where('user_id', $user->id)->active()->latest('paid_at')->get() : collect(),
            // Buying another plan replaces this one, so the page warns before checkout.
            'currentPlan' => $user ? AccessPass::where('user_id', $user->id)->running()->latest('paid_at')->first() : null,
            'required' => AccessGate::required($user),
            'canPay' => AccessGate::enabled() && $paystack->configured(),
        ]);
    }

    public function checkout(Request $request, AccessPlan $plan, AccessGate $gate, PaystackClient $paystack): RedirectResponse
    {
        abort_unless($plan->is_active, 404);
        if (! AccessGate::enabled() || ! $paystack->configured()) {
            return back()->withErrors(['plan' => 'Payments are temporarily unavailable. Please try again later.']);
        }

        try {
            [, $url] = $gate->checkout($request->user(), $plan, route('plans.confirm'));
        } catch (RuntimeException $e) {
            report($e);
            $url = null;
        }

        return $url
            ? redirect()->away($url)
            : back()->withErrors(['plan' => 'We could not start the payment. Please try again.']);
    }

    /** Paystack sends the payer here with ?reference=…; check the payment and open access straight away. */
    public function confirm(Request $request, AccessGate $gate): RedirectResponse
    {
        $reference = (string) ($request->query('reference') ?? $request->query('trxref'));
        $pass = AccessPass::where('user_id', $request->user()->id)->where('reference', $reference)->first();
        if (! $pass) {
            return redirect()->route('plans')->withErrors(['plan' => 'We couldn’t find that payment.']);
        }

        try {
            $pass = $gate->refresh($pass);
        } catch (RuntimeException $e) {
            report($e); // the webhook will still activate it
        }

        return match ($pass->status) {
            'paid' => redirect()->route('plans')->with('status', "Payment received. Your {$pass->plan_name} plan is active."),
            'failed' => redirect()->route('plans')->withErrors(['plan' => 'The payment didn’t go through, so you haven’t been charged.']),
            default => redirect()->route('plans')->with('status', 'We’re still waiting for Paystack to confirm your payment. Refresh this page in a minute.'),
        };
    }
}
