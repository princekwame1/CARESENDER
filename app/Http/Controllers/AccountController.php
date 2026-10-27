<?php

namespace App\Http\Controllers;

use App\Models\AccessPass;
use App\Models\SearchUnlock;
use App\Services\AccessGate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A member's own page: their details, the plans they have now, past purchases and the searches they've opened. */
class AccountController extends Controller
{
    public function show(Request $request, AccessGate $gate): View
    {
        $user = $request->user();

        return view('account', [
            'user' => $user,
            'passes' => AccessPass::where('user_id', $user->id)->active()->latest('paid_at')->get(),
            'pastPasses' => AccessPass::where('user_id', $user->id)->where('status', 'paid')->where('expires_at', '<=', now())->latest('paid_at')->limit(10)->get(),
            'openSearches' => SearchUnlock::where('user_id', $user->id)->open()->whereNotNull('query')->latest('expires_at')->get(),
            'fullUntil' => AccessGate::fullAccessUntil($gate->grantsFor($user->id)),
            'required' => AccessGate::required($user),
        ]);
    }
}
