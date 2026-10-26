<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        self::rememberWayBack($request);

        return view('auth.login');
    }

    /** "?back=/search?q=…" sends people back to the page they were on after signing in or up. Same-site paths only. */
    public static function rememberWayBack(Request $request): void
    {
        $back = (string) $request->query('back');
        if (str_starts_with($back, '/') && ! str_starts_with($back, '//') && ! str_contains($back, '\\')) {
            redirect()->setIntendedUrl(url($back));
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:190'], 'password' => ['required', 'string']]);

        // Sign in with either a username (e.g. "admin") or an email address.
        $field = str_contains($data['login'], '@') ? 'email' : 'username';
        if (! Auth::attempt([$field => $data['login'], 'password' => $data['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }
        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
