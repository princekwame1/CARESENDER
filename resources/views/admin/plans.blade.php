@php
    $currency = \App\Support\PaymentSettings::currency();
    $durationParts = fn (?int $h) => $h && $h % 24 === 0 ? [$h / 24, 'days'] : [$h, 'hours'];
@endphp
<x-layouts.admin title="Plans">
    <div class="space-y-6">
        @unless (\App\Support\PaymentSettings::enabled())
            <p class="rounded-2xl bg-surface-2 px-4 py-3 text-sm text-muted">Payments are off. Turn them on in <a href="{{ route('admin.settings.edit', 'payments') }}" class="text-brand underline">Settings</a>.</p>
        @endunless

        <x-admin.table-card title="Plans" :count="$plans->count()">
            <x-slot:actions>
                <button type="button" class="btn-primary btn-sm" data-modal-open="add-plan">Add a plan</button>
            </x-slot:actions>
            <x-slot:toolbar>
                <input type="search" data-table-search placeholder="Search plans" class="field max-w-xs" aria-label="Search plans">
                <select data-table-filter="status" class="field sm:w-auto" aria-label="In the app">
                    <option value="">Shown and hidden</option>
                    <option value="shown">Shown</option>
                    <option value="hidden">Hidden</option>
                </select>
            </x-slot:toolbar>
            <table class="admin-table min-w-[820px]">
                <thead><tr><th>Plan</th><th>What people get</th><th>Opens</th><th>Searches</th><th>Lasts for</th><th>Price</th><th>In the app</th><th class="text-right"></th></tr></thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr data-row data-status="{{ $plan->is_active ? 'shown' : 'hidden' }}">
                            <td class="font-medium">{{ $plan->name }}<span class="block text-xs font-normal text-muted">{{ $plan->billingLabel() }}</span></td>
                            <td class="max-w-72 text-sm text-muted">{{ $plan->description ?: '—' }}</td>
                            <td class="max-w-48 text-sm">{{ \App\Models\AccessPlan::areasLabel($plan->areas) }}</td>
                            <td class="tabular-nums">{{ $plan->isUnlimited() ? 'Unlimited' : $plan->searches }}</td>
                            <td>{{ $plan->lengthLabel() ?? ($plan->isUnlimited() ? '—' : 'No limit') }}</td>
                            <td class="tabular-nums">{{ $currency }} {{ number_format($plan->price / 100, 2) }}</td>
                            <td>{{ $plan->is_active ? 'Shown' : 'Hidden' }}</td>
                            <td class="whitespace-nowrap text-right">
                                <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-plan-{{ $plan->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="inline" onsubmit="return confirm('Delete the {{ addslashes($plan->name) }} plan? People who bought it keep what they paid for.')">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost !px-3 !py-1.5 text-xs !text-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No plans yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-admin.table-card>
    </div>

    @include('admin.partials.plan-modal', ['id' => 'add-plan', 'title' => 'Add a plan', 'plan' => null, 'action' => route('admin.plans.store'), 'method' => 'POST'])
    @foreach ($plans as $plan)
        @include('admin.partials.plan-modal', ['id' => 'edit-plan-'.$plan->id, 'title' => 'Edit '.$plan->name, 'plan' => $plan, 'action' => route('admin.plans.update', $plan), 'method' => 'PUT'])
    @endforeach
</x-layouts.admin>
