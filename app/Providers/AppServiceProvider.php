<?php

namespace App\Providers;

use App\Services\AccessGate;
use App\Services\DirectoryCache;
use App\Services\GeoLocator;
use App\Services\Socialite\AppleProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DirectoryCache::class);
        $this->app->singleton(GeoLocator::class);
        $this->app->scoped(AccessGate::class);
    }

    public function boot(): void
    {
        // Catch N+1 queries and silent attribute bugs during development.
        Model::shouldBeStrict(! $this->app->isProduction());

        JsonResource::wrap('data');
        Paginator::defaultView('pagination::caresenda');

        Vite::prefetch(concurrency: 3);

        Event::listen(SocialiteWasCalled::class, [MicrosoftExtendSocialite::class, 'handle']);
        Socialite::extend('apple', fn () => Socialite::buildProvider(AppleProvider::class, config('services.apple'))
            ->usingSigningKey((string) config('services.apple.team_id'), (string) config('services.apple.key_id'), (string) config('services.apple.private_key')));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
