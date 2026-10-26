<?php

use App\Http\Middleware\EnsureAdmin;
use App\Services\LocationCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EnsureAdmin::class]);
        // Lets a password change sign out the user's other browsers.
        $middleware->web(append: [AuthenticateSession::class]);
        // Location cookie is written by client JS, so it must stay unencrypted.
        $middleware->encryptCookies(except: [LocationCookie::NAME]);
        // Apple posts the sign-in result from its own site, so it cannot carry our CSRF token.
        $middleware->validateCsrfTokens(except: ['auth/apple/callback']);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
