<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api\V1\GeoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/nearby-panel', [HomeController::class, 'panel'])->name('nearby.panel');
Route::get('/search', SearchController::class)->name('search');
// The website's maps read markers through the session, so signed-in members see what their plan opens.
Route::get('/map/markers', [GeoController::class, 'markers'])->middleware('cache.headers:private;max_age=60')->name('map.markers');
Route::post('/search/unlock', [SearchController::class, 'unlock'])->middleware(['auth', 'throttle:login'])->name('search.unlock');
// Single searches can be bought without an account.
Route::post('/search/buy', [SearchController::class, 'buy'])->middleware('throttle:auth')->name('search.buy');
Route::get('/search/paid', [SearchController::class, 'paid'])->name('search.paid');
Route::get('/facilities/{facility}', [FacilityController::class, 'show'])->name('facilities.show');
Route::view('/how-we-verify', 'verify')->name('verify');
Route::view('/about', 'about')->name('about');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::redirect('/developers', '/admin/api');
// Common ways people type the dashboard address.
Route::redirect('/dashboard', '/admin');
Route::redirect('/Admin', '/admin');
Route::redirect('/ADMIN', '/admin');
Route::view('/credits', 'credits')->name('credits');
Route::view('/payments/done', 'payments-done')->name('payments.done');

Route::get('/plans', [PlanController::class, 'index'])->name('plans');
Route::middleware('auth')->group(function () {
    Route::post('/plans/{plan}/checkout', [PlanController::class, 'checkout'])->middleware('throttle:auth')->name('plans.checkout');
    Route::get('/plans/confirm', [PlanController::class, 'confirm'])->name('plans.confirm');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:auth');
    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->whereIn('provider', array_keys(SocialLoginController::PROVIDERS))->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->whereIn('provider', array_keys(SocialLoginController::PROVIDERS))->middleware('throttle:auth')->name('social.callback');
    Route::post('/auth/apple/callback', [SocialLoginController::class, 'applePost']);
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/facilities', [Admin\FacilityController::class, 'index'])->name('facilities.index');
    Route::get('/facilities/create', [Admin\FacilityController::class, 'create'])->name('facilities.create');
    Route::post('/facilities', [Admin\FacilityController::class, 'store'])->name('facilities.store');
    Route::get('/facilities/{facility}/edit', [Admin\FacilityController::class, 'edit'])->name('facilities.edit');
    Route::put('/facilities/{facility}', [Admin\FacilityController::class, 'update'])->name('facilities.update');
    Route::patch('/facilities/{facility}/toggle', [Admin\FacilityController::class, 'toggle'])->name('facilities.toggle');
    Route::delete('/facilities/{facility}', [Admin\FacilityController::class, 'destroy'])->name('facilities.destroy');
    Route::post('/facilities/{facility}/services', [Admin\FacilityController::class, 'storeService'])->name('facilities.services.store');
    Route::delete('/facilities/{facility}/services/{service}', [Admin\FacilityController::class, 'destroyService'])->name('facilities.services.destroy');
    Route::get('/services', [Admin\CatalogController::class, 'index'])->name('catalog.index');
    Route::post('/services', [Admin\CatalogController::class, 'store'])->name('catalog.store');
    Route::put('/services/{service}', [Admin\CatalogController::class, 'update'])->name('catalog.update');
    Route::delete('/services/{service}', [Admin\CatalogController::class, 'destroy'])->name('catalog.destroy');
    Route::get('/specialties', [Admin\SpecialtyController::class, 'index'])->name('specialties.index');
    Route::post('/specialties', [Admin\SpecialtyController::class, 'store'])->name('specialties.store');
    Route::put('/specialties/{specialty}', [Admin\SpecialtyController::class, 'update'])->name('specialties.update');
    Route::delete('/specialties/{specialty}', [Admin\SpecialtyController::class, 'destroy'])->name('specialties.destroy');
    Route::get('/content', [Admin\ContentController::class, 'index'])->name('content.index');
    Route::get('/content/{page}', [Admin\ContentController::class, 'edit'])->name('content.edit');
    Route::put('/content/{page}', [Admin\ContentController::class, 'update'])->name('content.update');
    Route::get('/export', [Admin\FacilityController::class, 'export'])->name('export');
    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [Admin\ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profile/avatar', [Admin\ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/settings/{tab?}', [Admin\SettingsController::class, 'edit'])->whereIn('tab', Admin\SettingsController::TABS)->name('settings.edit');
    Route::put('/settings/branding', [Admin\SettingsController::class, 'branding'])->name('settings.branding');
    Route::put('/settings/payments', [Admin\SettingsController::class, 'payments'])->name('settings.payments');
    Route::get('/plans', [Admin\PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [Admin\PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [Admin\PlanController::class, 'destroy'])->name('plans.destroy');
    Route::view('/api', 'admin.api')->name('api');
    Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
    Route::patch('/messages/{message}/read', [Admin\MessageController::class, 'toggleRead'])->name('messages.read');
});
