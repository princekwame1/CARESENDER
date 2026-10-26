<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPass;
use App\Models\Facility;
use App\Models\SearchLog;
use App\Models\User;
use App\Services\DirectoryStats;
use App\Support\PaymentSettings;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DirectoryStats $stats): View
    {
        $activity = $stats->searchActivity(14);
        // The 14-day series split into the previous and the current week.
        [$lastWeek, $thisWeek] = array_chunk(array_values($activity['daily']), 7);

        return view('admin.dashboard', [
            'overview' => $stats->overview(),
            'types' => $stats->facilityTypes(8),
            'specialties' => $stats->topSpecialties(8),
            'labCategories' => $stats->labCategories(8),
            'activity' => $activity,
            'week' => ['searches' => array_sum($thisWeek), 'previous' => array_sum($lastWeek), 'trend' => array_values($activity['daily'])],
            'members' => [
                'total' => User::where('role', 'member')->count(),
                'new' => User::where('role', 'member')->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'revenue' => [
                'amount' => AccessPass::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->sum('amount') / 100,
                'count' => AccessPass::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->count(),
                'currency' => PaymentSettings::currency(),
                'enabled' => PaymentSettings::enabled(),
            ],
            'verifiedFacilities' => Facility::active()->whereNotNull('verified_at')->count(),
            'recentSearches' => SearchLog::latest('created_at')->limit(8)->get(),
            'recentlyVerified' => Facility::whereNotNull('verified_at')->latest('verified_at')->limit(5)->get(['id', 'name', 'area', 'city', 'verified_at']),
            'recentPayments' => AccessPass::with('user:id,name')->where('status', 'paid')->latest('paid_at')->limit(5)->get(),
            'unverifiedWithPhone' => Facility::whereNull('verified_at')->whereNotNull('phone')->count(),
        ]);
    }
}
