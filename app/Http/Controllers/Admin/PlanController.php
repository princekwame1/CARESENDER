<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The plans sold in the mobile app: search packs and unlimited day/week/month access. */
class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans', ['plans' => AccessPlan::orderBy('sort_order')->orderBy('price')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = AccessPlan::create($this->validated($request));

        return back()->with('status', "Added the {$plan->name} plan.");
    }

    public function update(Request $request, AccessPlan $plan): RedirectResponse
    {
        $plan->update($this->validated($request));

        return back()->with('status', "Saved {$plan->name}. People who already bought it keep what they paid for.");
    }

    public function destroy(AccessPlan $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('status', "Deleted {$plan->name}. Existing purchases are kept.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'billing' => ['required', Rule::in(array_keys(AccessPlan::BILLING))],
            'areas' => ['required', 'array', 'min:1'],
            'areas.*' => [Rule::in(array_keys(AccessPlan::AREAS))],
            'searches' => ['nullable', 'required_if:billing,search', 'integer', 'min:1', 'max:1000'],
            'length' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'valid_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'price' => ['required', 'numeric', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'searches.required_if' => 'Say how many searches the plan includes.',
            'areas.required' => 'Pick at least one thing the plan opens.',
        ]);

        // Per search: a pack of searches, optionally valid for some days. Otherwise unlimited for N hours … years.
        $perSearch = $data['billing'] === 'search';
        [$period, $count] = $perSearch
            ? [isset($data['valid_days']) ? 'day' : null, $data['valid_days'] ?? null]
            : [$data['billing'], (int) ($data['length'] ?? 1)];

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'kind' => $perSearch ? AccessPlan::SEARCHES : AccessPlan::UNLIMITED,
            'period' => $period,
            'period_count' => $count,
            'areas' => AccessPlan::normalizeAreas($data['areas']),
            'searches' => $perSearch ? (int) $data['searches'] : null,
            'duration_hours' => $period ? $count * AccessPlan::PERIOD_HOURS[$period] : null,
            'price' => (int) round($data['price'] * 100),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
