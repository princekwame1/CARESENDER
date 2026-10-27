{{-- Warns a member that buying a new plan replaces the one they have now ($plan, a running AccessPass). --}}
@props(['plan'])
<p {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-2xl bg-accent-soft px-4 py-3 text-left text-sm']) }}>
    <x-icon name="info" class="mt-0.5 size-4 shrink-0 text-accent" />
    <span>You have an active plan: <strong>{{ $plan->plan_name }}</strong>@unless ($plan->isUnlimited()) ({{ $plan->searches_left }} {{ Str::plural('search', $plan->searches_left) }} left)@endunless. Buying a new plan will replace it, and anything left on it will end when the new plan starts.</span>
</p>
