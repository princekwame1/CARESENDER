<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
| CareSenda mobile API (v1). Public endpoints work without a token; sending a
| Sanctum bearer token links searches to the user's history.
*/

Route::post('v1/payments/paystack/webhook', [V1\AccessController::class, 'webhook'])->name('api.v1.payments.webhook');

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    // Declared before facilities/{facility} so "nearby" is not treated as an id.
    Route::get('facilities/nearby', [V1\FacilityController::class, 'nearby'])->name('facilities.nearby');

    // Short-lived HTTP caching for read-only, location-independent data.
    Route::middleware('cache.headers:public;max_age=300;etag')->group(function () {
        Route::get('meta', V1\MetaController::class)->name('meta');
        Route::get('areas', [V1\GeoController::class, 'areas'])->name('areas');
        Route::get('specialties', V1\SpecialtyController::class)->name('specialties');
    });

    // Detail responses depend on the caller's access pass, so they must not be shared by caches.
    Route::middleware('cache.headers:private;max_age=300;etag')->group(function () {
        Route::get('map/markers', [V1\GeoController::class, 'markers'])->name('map.markers');
        Route::get('facilities/{facility}', [V1\FacilityController::class, 'show'])->name('facilities.show');
        Route::get('services/{service}', [V1\ServiceController::class, 'show'])->name('services.show');
    });

    Route::get('dashboard', V1\DashboardController::class)->name('dashboard');
    Route::get('suggest', V1\SuggestController::class)->name('suggest');
    Route::match(['get', 'post'], 'geo/resolve', [V1\GeoController::class, 'resolve'])->name('geo.resolve');
    Route::get('facilities', [V1\FacilityController::class, 'index'])->name('facilities.index');
    Route::get('services', [V1\ServiceController::class, 'index'])->name('services.index');
    Route::get('plans', [V1\AccessController::class, 'plans'])->name('plans');

    Route::prefix('auth')->name('auth.')->middleware('throttle:auth')->group(function () {
        Route::post('register', [V1\AuthController::class, 'register'])->name('register');
        Route::post('login', [V1\AuthController::class, 'login'])->name('login');
        Route::post('social', [V1\AuthController::class, 'social'])->name('social');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [V1\AuthController::class, 'me'])->name('me');
        Route::get('me/searches', [V1\SearchHistoryController::class, 'index'])->name('me.searches');
        Route::delete('me/searches', [V1\SearchHistoryController::class, 'destroy'])->name('me.searches.clear');
        Route::get('me/access', [V1\AccessController::class, 'show'])->name('me.access');
        Route::post('me/passes', [V1\AccessController::class, 'store'])->middleware('throttle:auth')->name('me.passes.store');
        Route::get('me/passes/{reference}', [V1\AccessController::class, 'status'])->name('me.passes.show');
        Route::post('me/unlocks', [V1\AccessController::class, 'unlock'])->name('me.unlocks.store');

        Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
            Route::get('stats', [V1\Admin\AdminController::class, 'stats'])->name('stats');
            Route::patch('facilities/{facility}', [V1\Admin\AdminController::class, 'updateFacility'])->name('facilities.update');
        });
    });
});
