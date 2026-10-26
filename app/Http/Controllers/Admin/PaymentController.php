<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPass;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Payment history and takings from the mobile app (settings live in SettingsController). */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:paid,pending,failed'],
        ]);

        $passes = AccessPass::with('user:id,name,email')
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q->where('reference', 'like', $like)->orWhere('plan_name', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments', [
            'passes' => $passes,
            'filters' => $filters,
            'revenue' => AccessPass::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->sum('amount') / 100,
            'paidCount' => AccessPass::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->count(),
        ]);
    }
}
