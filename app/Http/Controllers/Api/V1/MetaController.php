<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Controller;
use App\Services\DirectoryCache;
use App\Services\DirectoryStats;
use App\Services\SearchFilters;
use App\Support\Branding;
use App\Support\PaymentSettings;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    /** Everything a mobile client needs to build its filters and home screen. */
    public function __invoke(DirectoryStats $stats, DirectoryCache $cache): JsonResponse
    {
        return response()->json(['data' => [
            'data_version' => $cache->version(),
            'branding' => Branding::toApi(),
            'cities' => array_keys(config('caresenda.cities')),
            'types' => SearchFilters::TYPES,
            'days' => SearchFilters::DAYS,
            'diagnostic_categories' => config('caresenda.diagnostic_categories'),
            'common_specialties' => config('caresenda.common_specialties'),
            'common_tests' => config('caresenda.common_tests'),
            'access_plans' => AccessController::planList(),
            'payments_enabled' => PaymentSettings::enabled(),
            // "Continue with …" buttons: open {site}/auth/{id}/redirect?app_challenge=… in the browser.
            'social_providers' => collect(SocialLoginController::enabledProviders())
                ->map(fn ($label, $id) => ['id' => $id, 'label' => $label])
                ->values()->all(),
            // Sign-up phone picker: Ghana first, then A to Z.
            'phone_countries' => collect(PhoneNumber::options())
                ->map(fn ($label, $code) => ['code' => $code, 'name' => PhoneNumber::COUNTRIES[$code][0], 'dial_code' => '+'.PhoneNumber::COUNTRIES[$code][1]])
                ->values()->all(),
            'totals' => $stats->overview(),
        ]]);
    }
}
