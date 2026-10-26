@php
    $on = \App\Support\PaymentSettings::enabled();
    $currency = \App\Support\PaymentSettings::currency();
@endphp
<x-layouts.admin title="Payments">
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3">
            <x-stat label="Payments" :value="$on ? 'On' : 'Off'" icon="shield" :hint="$on ? 'App users need a plan for full details' : 'Everything in the app is free. Change this in Settings'" />
            <x-stat label="Paid in the last 30 days" :value="$paidCount" icon="check" />
            <x-stat label="Received in the last 30 days" :value="$currency.' '.number_format($revenue, 2)" icon="chart" />
        </div>

        <x-admin.table-card title="Recent payments" :count="number_format($passes->total())">
            <x-slot:toolbar>
                <form method="GET" data-autosubmit class="flex flex-1 flex-wrap items-center gap-3">
                    <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, email or reference" class="field max-w-xs" aria-label="Search payments">
                    <select name="status" class="field sm:w-auto" aria-label="Status">
                        <option value="">Any status</option>
                        <option value="paid" @selected(($filters['status'] ?? null) === 'paid')>Paid</option>
                        <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Waiting</option>
                        <option value="failed" @selected(($filters['status'] ?? null) === 'failed')>Failed</option>
                    </select>
                    <button class="btn-secondary">Search</button>
                    @if (array_filter($filters))<a href="{{ route('admin.payments.index') }}" class="btn-ghost">Clear</a>@endif
                </form>
            </x-slot:toolbar>
            <table class="admin-table min-w-[800px]">
                <thead><tr><th>When</th><th>Who</th><th>Plan</th><th>Amount</th><th>Status</th><th>Searches left</th><th>Ends</th><th>Reference</th></tr></thead>
                <tbody>
                    @forelse ($passes as $pass)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $pass->created_at->diffForHumans() }}</td>
                            <td>{{ $pass->user?->name }}<span class="block text-xs text-muted">{{ $pass->user?->email }}</span></td>
                            <td>{{ $pass->plan_name }}</td>
                            <td class="tabular-nums">{{ $pass->currency }} {{ number_format($pass->amount / 100, 2) }}</td>
                            <td><span @class(['rounded-full px-2 py-0.5 text-xs font-medium', 'bg-brand-soft text-brand' => $pass->status === 'paid', 'bg-danger-soft text-danger' => $pass->status === 'failed', 'bg-surface-2 text-muted' => $pass->status === 'pending'])>{{ ['paid' => 'Paid', 'failed' => 'Failed', 'pending' => 'Waiting'][$pass->status] ?? $pass->status }}</span></td>
                            <td class="tabular-nums">{{ $pass->isUnlimited() ? 'Unlimited' : ($pass->searches_left ?? '—') }}</td>
                            <td class="whitespace-nowrap text-xs">{{ $pass->expires_at?->format('j M Y, H:i') ?? '—' }}</td>
                            <td class="text-xs text-muted">{{ $pass->reference }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-muted">{{ array_filter($filters) ? 'No payments match these filters.' : 'No payments yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-admin.table-card>
        <div>{{ $passes->links() }}</div>
    </div>
</x-layouts.admin>
