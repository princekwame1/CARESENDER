<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Member sign-up on the website; the same account works in the mobile app. */
class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        LoginController::rememberWayBack($request);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone_country' => ['required', Rule::in(array_keys(PhoneNumber::COUNTRIES))],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s()+\-.]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'There is already an account with this email. Sign in instead.',
            'phone.regex' => 'Use only numbers in the phone number.',
        ]);

        if (filled($data['phone'] ?? null)) {
            $data['phone'] = PhoneNumber::format($data['phone_country'], $data['phone'])
                ?? throw ValidationException::withMessages(['phone' => 'That phone number doesn’t look right. Check it and try again.']);
        }

        $user = User::create(Arr::except($data, 'phone_country'));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('plans'))->with('status', 'Welcome to CareSenda. Your account is ready.');
    }
}
