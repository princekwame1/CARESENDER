@php($currency = \App\Support\PaymentSettings::currency())
<x-layouts.app title="Your account">
    <div class="container-page py-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="section-label">Your account</p>
                <h1 class="mt-1.5 text-[28px] font-bold sm:text-4xl">{{ $user->name }}</h1>
                <p class="mt-2 text-sm text-muted">{{ $user->email }}@if ($user->phone) · {{ $user->phone }}@endif</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-secondary"><x-icon name="logout" />Log out</button></form>
        </div>

        <section class="card mt-8 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Your plan</h2>
                @if ($required)<a href="{{ route('plans') }}" class="btn-secondary !py-2 text-sm">{{ $passes->isEmpty() ? 'Choose a plan' : 'Add more time' }}</a>@endif
            </div>
            @unless ($required)
                <p class="mt-2 text-sm text-muted">Everything is open for you right now, so you don’t need a plan.</p>
            @endunless
            @if ($required || $passes->isNotEmpty())
                <x-access-passes class="mt-4" :passes="$passes" :full-until="$fullUntil" />
            @endif
        </section>

        @if ($openSearches->isNotEmpty())
            <section class="card mt-6 p-6">
                <h2 class="text-lg font-bold">Searches you’ve opened</h2>
                <ul class="mt-4 grid gap-2">
                    @foreach ($openSearches as $unlock)
                        <li>
                            <a href="{{ route('search', ['q' => $unlock->query]) }}" class="flex items-center justify-between gap-4 rounded-2xl bg-surface-2 px-4 py-3 text-sm transition hover:ring-1 hover:ring-brand/40">
                                <span class="font-semibold">{{ $unlock->query }}</span>
                                <span class="shrink-0 text-xs text-muted">Open until {{ $unlock->expires_at->format('j M, g:i a') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($pastPasses->isNotEmpty())
            <section class="card mt-6 p-6">
                <h2 class="text-lg font-bold">Past plans</h2>
                <ul class="mt-4 grid gap-2">
                    @foreach ($pastPasses as $pass)
                        <li class="flex items-center justify-between gap-4 rounded-2xl bg-surface-2 px-4 py-3 text-sm">
                            <span><span class="font-semibold">{{ $pass->plan_name }}</span><span class="block text-xs text-muted">Bought {{ $pass->paid_at?->format('j M Y') }} · ended {{ $pass->expires_at->format('j M Y') }}</span></span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ $pass->currency ?? $currency }} {{ number_format($pass->total() / 100, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
