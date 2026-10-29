@php
    $currency = \App\Support\PaymentSettings::currency();
    $fullUntil = $grants ? \App\Services\AccessGate::fullAccessUntil($grants) : null;
@endphp
<x-layouts.app title="Plans" description="CareSenda plans: see contacts, specialists, test prices and locations for facilities in Accra and Tema.">
    <div class="container-page py-10">
        <div class="max-w-2xl">
            <p class="section-label">Plans</p>
            <h1 class="mt-1.5 text-[28px] font-bold sm:text-4xl">See the details behind every search</h1>
            <p class="mt-3 text-base text-muted">Anyone can see how many places match a search. A plan shows you who they are, where they are, how to reach them, and the doctors, days and prices we’ve found.</p>
        </div>

        @unless ($required)
            <p class="mt-8 flex items-start gap-3 rounded-2xl bg-accent-soft px-4 py-3 text-sm"><x-icon name="info" class="mt-0.5 size-4 shrink-0 text-accent" />Everything is open for you right now, so you don’t need a plan.</p>
        @endunless

        @guest
            @if ($freeTrial = \App\Services\AccessGate::freeTrialOffer())
                <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-accent-soft px-4 py-3 text-sm">
                    <p><span class="font-semibold text-accent">Free trial.</span> Create an account and get {{ $freeTrial }} before you choose a plan.</p>
                    <a href="{{ route('register', ['back' => '/search']) }}" class="btn-primary">Sign up free</a>
                </div>
            @endif
        @endguest

        @auth
            @if ($required)
                <section class="card mt-8 p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold">Your access</h2>
                        <a href="{{ route('account') }}" class="text-sm font-semibold text-brand underline">Your account</a>
                    </div>
                    @if ($passes->isEmpty())
                        <p class="mt-2 text-sm text-muted">You don’t have a plan yet. Choose one below.</p>
                    @else
                        <x-access-passes class="mt-4" :passes="$passes" :full-until="$fullUntil" />
                    @endif
                </section>
            @endif
        @endauth

        @if ($plans->isEmpty())
            <p class="card mt-8 p-8 text-center text-sm text-muted">There are no plans on sale right now. Please check back soon.</p>
        @else
            @if ($currentPlan && $required)
                <x-plan-replaced-notice :plan="$currentPlan" class="mt-8" />
            @endif
            <div data-stagger class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($plans as $plan)
                    <article data-reveal class="card flex flex-col gap-4 p-6">
                        <div>
                            <p class="eyebrow">{{ $plan->billingLabel() }}</p>
                            <h2 class="mt-1.5 text-xl font-bold">{{ $plan->name }}</h2>
                            @if ($plan->description)<p class="mt-1 text-sm text-muted">{{ $plan->description }}</p>@endif
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold tabular-nums">{{ $currency }} {{ number_format($plan->price / 100, 2) }}</p>
                        </div>
                        <ul class="grid gap-1.5 text-sm">
                            @foreach (\App\Models\AccessPlan::AREAS as $area => $label)
                                <li @class(['flex items-center gap-2', 'text-muted line-through' => ! in_array($area, $plan->coveredAreas(), true)])>
                                    <x-icon :name="in_array($area, $plan->coveredAreas(), true) ? 'check' : 'x'" class="size-4 shrink-0" />{{ $label }}
                                </li>
                            @endforeach
                            <li class="flex items-center gap-2"><x-icon name="clock" class="size-4 shrink-0" />
                                {{ $plan->isUnlimited() ? 'Unlimited searching for '.$plan->lengthLabel() : ($plan->lengthLabel() ? 'Use within '.$plan->lengthLabel() : 'Searches never run out') }}
                            </li>
                        </ul>
                        <div class="mt-auto pt-2">
                            @guest
                                <a href="{{ route('register', ['back' => '/plans']) }}" class="btn-primary w-full justify-center">Create an account to buy</a>
                            @else
                                @if ($required && $canPay)
                                    <form method="POST" action="{{ route('plans.checkout', $plan) }}">
                                        @csrf
                                        <button class="btn-primary w-full justify-center">Pay with Paystack</button>
                                    </form>
                                @elseif ($required)
                                    <button type="button" class="btn-secondary w-full justify-center" disabled>Payments open soon</button>
                                @endif
                            @endguest
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="mt-6 text-sm text-muted">You pay securely through Paystack. Your plan works on this website and in the CareSenda app.</p>
        @endif
    </div>
</x-layouts.app>
