{{--
    Shown in place of details the viewer's plan doesn't open. $unlock holds the search to open: with one credit, by paying
    for a single search ($singleSearch, no account needed), or by buying one of $plans (needs an account, then Paystack).
--}}
@props(['title', 'plans', 'unlock' => null, 'searchesLeft' => 0, 'singleSearch' => null])
@php
    $currency = \App\Support\PaymentSettings::currency();
    $back = request()->getRequestUri();
    $multiplePlans = $plans->reject->sellsToGuests()->values();
    $hasOptions = $unlock && ($singleSearch || $multiplePlans->isNotEmpty());
    $money = fn (int $pesewas) => $currency.' '.number_format($pesewas / 100, 2);
@endphp
<div {{ $attributes->merge(['class' => 'card flex flex-col items-center gap-4 p-8 text-center sm:p-10']) }}>
    <span class="icon-tile"><x-icon name="list" /></span>
    <h2 class="text-xl font-bold">{{ $title }}</h2>
    <p class="max-w-md text-sm text-muted">{{ $slot }}</p>
    @error('unlock')<p class="text-sm text-danger">{{ $message }}</p>@enderror

    @if ($unlock && auth()->check() && $searchesLeft > 0)
        <form method="POST" action="{{ route('search.unlock') }}">
            @csrf
            @foreach ($unlock as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <button class="btn-primary">Use 1 of your {{ $searchesLeft }} {{ Str::plural('search', $searchesLeft) }} to open these results</button>
        </form>
    @endif

    @if ($hasOptions)
        <details class="group/findings w-full max-w-lg" @if ($errors->any()) open @endif>
            <summary class="btn-primary mx-auto w-fit cursor-pointer list-none justify-center group-open/findings:hidden! [&::-webkit-details-marker]:hidden">
                View findings <x-icon name="arrow" class="size-4" />
            </summary>

            <form method="POST" action="{{ route('search.access') }}" class="group mt-2 flex flex-col gap-5 text-left">
                @csrf
                @foreach ($unlock as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <div>
                    <p class="font-semibold">Choose how you want to see the results</p>
                    <p class="text-xs text-muted">Pay once for this search, or get a pass for as many searches as you like.</p>
                </div>

                <fieldset class="m-0 flex min-w-0 flex-col gap-3 border-0 p-0">
                    <legend class="sr-only">Access option</legend>
                    @if ($singleSearch)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-surface-2 px-4 py-3 text-sm ring-1 ring-transparent transition hover:ring-brand/40 has-[:checked]:bg-brand-soft has-[:checked]:ring-brand">
                            <input id="access-single" type="radio" name="plan" value="{{ $singleSearch->id }}" class="accent-brand" @checked(old('plan', $singleSearch->id) == $singleSearch->id)>
                            <x-icon name="search" class="size-5 shrink-0 text-brand" />
                            <span class="flex-1"><span class="block font-semibold">Single search access</span><span class="block text-xs text-muted">Just these results. No account needed.</span></span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ $money($singleSearch->price) }}</span>
                        </label>
                    @endif
                    @foreach ($multiplePlans as $plan)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-surface-2 px-4 py-3 text-sm ring-1 ring-transparent transition hover:ring-brand/40 has-[:checked]:bg-brand-soft has-[:checked]:ring-brand">
                            <input type="radio" name="plan" value="{{ $plan->id }}" class="accent-brand" @checked(old('plan', $singleSearch ? null : $multiplePlans->first()->id) == $plan->id)>
                            <x-icon name="clock" class="size-5 shrink-0 text-brand" />
                            <span class="flex-1">
                                <span class="block font-semibold">{{ $plan->name }}</span>
                                <span class="block text-xs text-muted">{{ $plan->isUnlimited() ? 'Multiple search access' : $plan->billingLabel() }}@if ($plan->lengthLabel()) for {{ $plan->lengthLabel() }}@endif · Opens: {{ \App\Models\AccessPlan::areasLabel($plan->areas) }}@guest · Needs an account @endguest</span>
                            </span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ $money($plan->price) }}</span>
                        </label>
                    @endforeach
                </fieldset>

                @if ($singleSearch)
                    @guest
                        <div class="hidden gap-1.5 group-has-[#access-single:checked]:grid">
                            <label for="single-search-email" class="text-xs text-muted">Paystack sends your receipt to this email.</label>
                            <input id="single-search-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" autocapitalize="none" maxlength="190" placeholder="you@example.com" class="field">
                            @error('email')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                    @endguest
                    <p class="hidden text-xs text-muted group-has-[#access-single:checked]:block">
                        You pay {{ $money($singleSearch->total()) }}@if ($singleSearch->fee() > 0), including a {{ $money($singleSearch->fee()) }} Paystack fee @endif.
                        The results stay open for {{ \App\Models\AccessPlan::durationLabel(\App\Support\PaymentSettings::unlockHours()) }}@guest in this browser @endguest.
                    </p>
                @endif

                <button class="btn-primary justify-center">
                    @if ($singleSearch)<span class="hidden group-has-[#access-single:checked]:inline">Pay with Paystack</span>@endif
                    <span @class(['group-has-[#access-single:checked]:hidden' => $singleSearch])>@guest Create an account to continue @else Continue to payment @endguest</span>
                </button>
                @guest
                    <p class="text-center text-xs text-muted">Already have an account? <a href="{{ route('login', ['back' => $back]) }}" class="font-semibold text-brand underline">Sign in</a></p>
                @endguest
            </form>
        </details>
    @else
        <div class="flex flex-wrap justify-center gap-3">
            @guest
                <a href="{{ route('register', ['back' => $back]) }}" class="btn-primary">Create an account</a>
                <a href="{{ route('login', ['back' => $back]) }}" class="btn-secondary">Sign in</a>
            @else
                <a href="{{ route('plans') }}" @class([$unlock && $searchesLeft > 0 ? 'btn-secondary' : 'btn-primary'])>{{ $unlock && $searchesLeft > 0 ? 'See plans' : 'Choose a plan' }}</a>
            @endguest
        </div>

        @if ($plans->isNotEmpty())
            <ul class="mt-2 grid w-full max-w-lg gap-2 text-left">
                @foreach ($plans as $plan)
                    <li>
                        <a href="{{ route('plans') }}" class="flex items-center justify-between gap-4 rounded-2xl bg-surface-2 px-4 py-3 text-sm transition hover:ring-1 hover:ring-brand/40">
                            <span><span class="font-semibold">{{ $plan->name }}</span><span class="block text-xs text-muted">Opens: {{ \App\Models\AccessPlan::areasLabel($plan->areas) }}</span></span>
                            <span class="shrink-0 text-right"><span class="block font-semibold tabular-nums">{{ $money($plan->price) }}</span><span class="block text-xs text-muted">{{ $plan->billingLabel() }}</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-muted">Buy here or in the CareSenda app. Your plan works in both.</p>
        @endif
    @endif
</div>
