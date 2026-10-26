{{-- Add or edit one access plan. $plan is null when adding. --}}
@php
    $mine = request()->hasSession() && old('_modal') === $id;
    $billing = $mine ? old('billing', $plan?->billing() ?? 'month') : ($plan?->billing() ?? 'month');
    $length = $plan?->isUnlimited() ? ($plan->period_count ?? 1) : 1;
    $validDays = $plan && ! $plan->isUnlimited() && $plan->duration_hours ? (int) ceil($plan->duration_hours / 24) : null;
    $active = $mine ? (bool) old('is_active') : ($plan?->is_active ?? true);
    $areas = $mine ? (array) old('areas', []) : ($plan?->coveredAreas() ?? array_keys(\App\Models\AccessPlan::AREAS));
@endphp
<x-admin.modal :id="$id" :title="$title" size="md">
    <form id="{{ $id }}-form" method="POST" action="{{ $action }}" class="grid gap-4">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <input type="hidden" name="_modal" value="{{ $id }}">
        <x-admin.field label="Plan name" name="name" :value="$plan?->name" :modal="$id" required maxlength="80" placeholder="Monthly" />
        <x-admin.field label="What people get" name="description" :value="$plan?->description" :modal="$id" maxlength="255" placeholder="Unlimited searches for a month" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.field label="How it's sold" name="billing" type="select" :modal="$id">
                @foreach (\App\Models\AccessPlan::BILLING as $value => $label)
                    <option value="{{ $value }}" @selected($billing === $value)>{{ $label }}</option>
                @endforeach
            </x-admin.field>
            <x-admin.field label="Price ({{ \App\Support\PaymentSettings::currency() }})" name="price" type="number" :value="$plan ? $plan->price / 100 : null" :modal="$id" min="1" step="0.01" required placeholder="50" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.field label="How many periods" name="length" type="number" :value="$length" :modal="$id" min="1" placeholder="1" />
            <x-admin.field label="Searches (per search)" name="searches" type="number" :value="$plan?->searches ?? 1" :modal="$id" min="1" placeholder="1" />
            <x-admin.field label="Searches valid for (days)" name="valid_days" type="number" :value="$validDays" :modal="$id" min="1" placeholder="Never expire" />
        </div>
        <fieldset>
            <legend class="label">What this plan opens</legend>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach (\App\Models\AccessPlan::AREAS as $value => $label)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="areas[]" value="{{ $value }}" @checked(in_array($value, $areas, true))> {{ $label }}</label>
                @endforeach
            </div>
            @if ($mine)@error('areas')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror @endif
        </fieldset>
        <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
            <x-admin.field label="Position in the list" name="sort_order" type="number" :value="$plan?->sort_order ?? 0" :modal="$id" min="0" />
            <label class="flex items-center gap-2 pb-3 text-sm"><input type="checkbox" name="is_active" value="1" @checked($active)> Show this plan in the app</label>
        </div>
    </form>
    <x-slot:footer>
        <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
        <button form="{{ $id }}-form" @class(['btn-primary', 'btn-sm' => ! $plan])>{{ $plan ? 'Save changes' : 'Add plan' }}</button>
    </x-slot:footer>
</x-admin.modal>
