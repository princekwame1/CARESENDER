{{-- A member's active plans, with when each runs out. $fullUntil is when unlimited access to everything ends, if it does. --}}
@props(['passes', 'fullUntil' => null])
@if ($passes->isEmpty())
    <p {{ $attributes->merge(['class' => 'text-sm text-muted']) }}>You don’t have a plan yet. <a href="{{ route('plans') }}" class="font-semibold text-brand underline">Choose one</a> to see the details behind your searches.</p>
@else
    <div {{ $attributes }}>
        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach ($passes as $pass)
                <li class="rounded-2xl bg-surface-2 px-4 py-3 text-sm">
                    <span class="font-semibold">{{ $pass->plan_name }}</span>
                    <span class="block text-xs text-muted">Opens: {{ \App\Models\AccessPlan::areasLabel($pass->areas) }}</span>
                    <span class="mt-1 block text-xs">
                        @if (! $pass->isUnlimited()){{ $pass->searches_left }} of {{ $pass->searches_total }} searches left · @endif
                        {{ $pass->expires_at ? 'Until '.$pass->expires_at->format('j M Y, g:i a') : 'Never runs out' }}
                    </span>
                </li>
            @endforeach
        </ul>
        @if ($fullUntil)
            <p class="mt-3 text-sm text-muted">Everything is open until {{ $fullUntil->format('j M Y, g:i a') }}.</p>
        @endif
    </div>
@endif
