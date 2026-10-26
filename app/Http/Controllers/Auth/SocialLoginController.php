<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/** "Continue with Google / Facebook / Apple / Microsoft" on the sign-in and sign-up pages. */
class SocialLoginController extends Controller
{
    public const PROVIDERS = ['google' => 'Google', 'facebook' => 'Facebook', 'apple' => 'Apple', 'microsoft' => 'Microsoft'];

    /**
     * Providers whose keys are set, so their buttons can show.
     *
     * @return array<string, string>
     */
    public static function enabledProviders(): array
    {
        return array_filter(self::PROVIDERS, fn (string $label, string $provider) => filled(config("services.{$provider}.client_id"))
            && ($provider !== 'apple' || filled(config('services.apple.team_id')) && filled(config('services.apple.key_id')) && filled(config('services.apple.private_key'))),
            ARRAY_FILTER_USE_BOTH);
    }

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        abort_unless(array_key_exists($provider, self::enabledProviders()), 404);
        LoginController::rememberWayBack($request);

        return Socialite::driver($provider)->redirect();
    }

    /** Apple posts its answer from appleid.apple.com, which leaves the session cookie behind; a GET brings it back. */
    public function applePost(Request $request): RedirectResponse
    {
        return redirect()->route('social.callback', ['provider' => 'apple', ...$request->only('code', 'state', 'user', 'error')], 303);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(array_key_exists($provider, self::enabledProviders()), 404);
        $label = self::PROVIDERS[$provider];

        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', "{$label} sign-in was cancelled.");
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->with('error', 'That sign-in took too long or was opened in another tab. Please try again.');
        } catch (Exception $exception) {
            report($exception);

            return redirect()->route('login')->with('error', "We couldn’t sign you in with {$label}. Please try again.");
        }

        $account = SocialAccount::with('user')->firstWhere(['provider' => $provider, 'provider_user_id' => (string) $providerUser->getId()]);
        if ($account) {
            return $this->signIn($request, $account->user);
        }

        $email = $providerUser->getEmail();
        if (blank($email)) {
            return redirect()->route('login')->with('error', "{$label} didn’t share an email address with us. Sign up with your email instead.");
        }

        $existingUser = User::firstWhere('email', $email);
        if ($existingUser) {
            // Only link when the provider vouches for the email, so nobody can take over an account by
            // adding someone else's address to theirs. Team accounts always sign in with a password.
            if ($existingUser->isAdmin() || ! $this->hasVerifiedEmail($provider, $providerUser)) {
                return redirect()->route('login')->with('error', 'There is already an account with this email. Sign in with your password instead.');
            }
            $existingUser->socialAccounts()->create(['provider' => $provider, 'provider_user_id' => (string) $providerUser->getId()]);

            return $this->signIn($request, $existingUser);
        }

        $user = DB::transaction(function () use ($provider, $providerUser, $email) {
            $user = User::create([
                'name' => Str::limit($providerUser->getName() ?: Str::before($email, '@'), 120, ''),
                'email' => $email,
                // They sign in through the provider; this just fills the column.
                'password' => Str::password(40),
            ]);
            if ($this->hasVerifiedEmail($provider, $providerUser)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            $user->socialAccounts()->create(['provider' => $provider, 'provider_user_id' => (string) $providerUser->getId()]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('plans'))->with('status', 'Welcome to CareSenda. Your account is ready.');
    }

    private function signIn(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    /** Google and Apple say whether they checked the address; Facebook only shares confirmed ones; Microsoft makes no promise. */
    private function hasVerifiedEmail(string $provider, ProviderUser $providerUser): bool
    {
        $raw = $providerUser->getRaw();

        return match ($provider) {
            'google', 'apple' => filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
            'facebook' => true,
            default => false,
        };
    }
}
