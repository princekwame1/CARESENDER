{{-- Shown in place of details the viewer's plan doesn't open. $unlock holds the search to open with one credit, or to buy as a single search ($singleSearch, no account needed). --}}
@props(['title', 'plans', 'unlock' => null, 'searchesLeft' => 0, 'singleSearch' => null])
@php($currency = \App\Support\PaymentSettings::currency())
@php($back = request()->getRequestUri())
<div {{ $attributes->merge(['class' => 'card flex flex-col items-center gap-4 p-8 text-center sm:p-10']) }}>
    <span class="icon-tile"><x-icon name="lock" /></span>
    <h2 class="text-xl font-bold">{{ $title }}</h2>
    <p class="max-w-md text-sm text-muted">{{ $slot }}</p>
    @error('unlock')<p class="text-sm text-danger">{{ $message }}</p>@enderror

    @if ($unlock && $singleSearch && ! (auth()->check() && $searchesLeft > 0))
        <form method="POST" action="{{ route('search.buy') }}" class="grid w-full max-w-md gap-2 rounded-2xl bg-surface-2 p-4 text-left">
            @csrf
            @foreach ($unlock as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <p class="text-sm font-semibold">Just this search? Open these results for {{ $currency }} {{ number_format($singleSearch->price / 100, 2) }}</p>
            @guest
                <p class="text-xs text-muted">No account needed. Paystack sends your receipt to this email.</p>
                <label for="single-search-email" class="sr-only">Email</label>
                <input id="single-search-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autocapitalize="none" maxlength="190" placeholder="you@example.com" class="field">
                @error('email')<p class="text-xs text-danger">{{ $message }}</p>@enderror
            @endguest
            <button class="btn-primary justify-center">Pay {{ $currency }} {{ number_format($singleSearch->total() / 100, 2) }}</button>
            <p class="text-xs text-muted">
                @if ($singleSearch->fee() > 0)Includes a {{ $currency }} {{ number_format($singleSearch->fee() / 100, 2) }} Paystack fee. @endif
                The results stay open for {{ \App\Models\AccessPlan::durationLabel(\App\Support\PaymentSettings::unlockHours()) }}@guest in this browser @endguest.
            </p>
        </form>
    @endif

    <div class="flex flex-wrap justify-center gap-3">
        @guest
            <a href="{{ route('register', ['back' => $back]) }}" class="btn-primary">Create an account</a>
            <a href="{{ route('login', ['back' => $back]) }}" class="btn-secondary">Sign in</a>
        @else
            @if ($unlock && $searchesLeft > 0)
                <form method="POST" action="{{ route('search.unlock') }}">
                    @csrf
                    @foreach ($unlock as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <button class="btn-primary">Use 1 of your {{ $searchesLeft }} {{ Str::plural('search', $searchesLeft) }} to open these results</button>
                </form>
                <a href="{{ route('plans') }}" class="btn-secondary">See plans</a>
            @else
                <a href="{{ route('plans') }}" class="btn-primary">Choose a plan</a>
            @endif
        @endguest
    </div>

    @if ($plans->isNotEmpty())
        <ul class="mt-2 grid w-full max-w-lg gap-2 text-left">
            @foreach ($plans as $plan)
                <li>
                    <a href="{{ route('plans') }}" class="flex items-center justify-between gap-4 rounded-2xl bg-surface-2 px-4 py-3 text-sm transition hover:ring-1 hover:ring-brand/40">
                        <span><span class="font-semibold">{{ $plan->name }}</span><span class="block text-xs text-muted">Opens: {{ \App\Models\AccessPlan::areasLabel($plan->areas) }}</span></span>
                        <span class="shrink-0 text-right"><span class="block font-semibold tabular-nums">{{ $currency }} {{ number_format($plan->price / 100, 2) }}</span><span class="block text-xs text-muted">{{ $plan->billingLabel() }}</span></span>
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="text-xs text-muted">Buy here or in the CareSenda app. Your plan works in both.</p>
    @endif
</div>
