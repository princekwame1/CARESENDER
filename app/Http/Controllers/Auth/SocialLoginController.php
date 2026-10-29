<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AccessGate;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * "Continue with Google / Facebook / Apple / Microsoft" on the sign-in and sign-up pages.
 *
 * The mobile app uses the same pages: it opens the redirect with an `app_challenge` (PKCE, S256)
 * and gets a one-time code back on its own URL scheme, which it trades for a token at
 * POST /api/v1/auth/social together with the secret behind the challenge.
 */
class SocialLoginController extends Controller
{
    public const PROVIDERS = ['google' => 'Google', 'facebook' => 'Facebook', 'apple' => 'Apple', 'microsoft' => 'Microsoft'];

    private const APP_CHALLENGE = 'social.app_challenge';

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

    /** The cache key holding an app sign-in code until the app claims it. */
    public static function appCodeKey(string $code): string
    {
        return 'social.app_code.'.hash('sha256', $code);
    }

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        abort_unless(array_key_exists($provider, self::enabledProviders()), 404);
        LoginController::rememberWayBack($request);

        $challenge = (string) $request->query('app_challenge');
        if (preg_match('/^[A-Za-z0-9_-]{43}$/', $challenge)) {
            $request->session()->put(self::APP_CHALLENGE, $challenge);
        } else {
            $request->session()->forget(self::APP_CHALLENGE);
        }

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
        $appChallenge = $request->session()->pull(self::APP_CHALLENGE);

        $user = $this->resolveUser($request, $provider, $label);
        if (is_string($user)) {
            return $appChallenge
                ? $this->backToApp(['error' => $user])
                : redirect()->route('login')->with('error', $user);
        }

        if ($appChallenge) {
            $code = Str::random(64);
            Cache::put(self::appCodeKey($code), ['user_id' => $user->id, 'challenge' => $appChallenge], now()->addMinutes(5));

            return $this->backToApp(['code' => $code]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->wasRecentlyCreated) {
            return redirect()->intended(route('plans'))->with('status', RegisterController::welcomeMessage());
        }

        return redirect()->intended($user->isStaff() ? route('admin.dashboard') : route('home'));
    }

    /** The member behind the provider's answer, signing them up if needed, or a message saying why not. */
    private function resolveUser(Request $request, string $provider, string $label): User|string
    {
        if ($request->filled('error')) {
            return "{$label} sign-in was cancelled.";
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return 'That sign-in took too long or was opened in another tab. Please try again.';
        } catch (Exception $exception) {
            report($exception);

            return "We couldn’t sign you in with {$label}. Please try again.";
        }

        $account = SocialAccount::with('user')->firstWhere(['provider' => $provider, 'provider_user_id' => (string) $providerUser->getId()]);
        if ($account) {
            return $account->user;
        }

        $email = $providerUser->getEmail();
        if (blank($email)) {
            return "{$label} didn’t share an email address with us. Sign up with your email instead.";
        }

        $existingUser = User::firstWhere('email', $email);
        if ($existingUser) {
            // Only link when the provider vouches for the email, so nobody can take over an account by
            // adding someone else's address to theirs. Team accounts always sign in with a password.
            if ($existingUser->isStaff() || ! $this->hasVerifiedEmail($provider, $providerUser)) {
                return 'There is already an account with this email. Sign in with your password instead.';
            }
            $existingUser->socialAccounts()->create(['provider' => $provider, 'provider_user_id' => (string) $providerUser->getId()]);

            return $existingUser;
        }

        return DB::transaction(function () use ($provider, $providerUser, $email) {
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
            AccessGate::startFreeTrial($user);

            return $user;
        });
    }

    /**
     * Hands the result to the mobile app through its URL scheme.
     *
     * @param  array{code?: string, error?: string}  $result
     */
    private function backToApp(array $result): RedirectResponse
    {
        return redirect()->away(config('caresenda.app_auth_callback').'?'.http_build_query($result));
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
