<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $title ?? null, 'description' => $description ?? null])
</head>
<body class="min-h-dvh pb-24 lg:pb-0">
@include('partials.preloader')
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 btn-primary">Skip to content</a>

@if (cms('site.notice'))
    <div class="bg-[#07689f] px-4 py-2.5 text-center text-sm font-medium text-white" role="note">{{ cms('site.notice') }}</div>
@endif
<header data-header class="sticky top-0 z-40 px-3 pt-3 transition-[padding] duration-300 sm:px-5">
    <div data-header-bar class="container-page flex h-16 items-center gap-4 rounded-2xl border border-line/60 bg-surface/75 !px-3 shadow-[0_2px_20px_-12px_rgb(7_104_159/.25)] backdrop-blur-xl transition-[height,box-shadow] duration-300 sm:!px-5 lg:h-[72px]">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ \App\Support\Branding::name() }} home">
            <x-brand-mark class="shadow-glow" />
        </a>
        <nav class="relative ml-6 hidden items-center rounded-full bg-surface-2/80 p-1 lg:flex" aria-label="Main" data-pill-nav>
            <span data-pill class="pointer-events-none absolute inset-y-1 left-0 rounded-full bg-surface shadow-card transition-all duration-300 ease-out"></span>
            @foreach (array_filter([['home', 'Home'], ['search', 'Browse'], \App\Services\AccessGate::enabled() ? ['plans', 'Plans'] : null, ['verify', 'How we check'], ['about', 'About us'], ['contact', 'Contact us']]) as [$route, $label])
                <a href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif
                   @class(['relative z-10 whitespace-nowrap rounded-full px-4 py-2 text-[15px] font-medium transition-colors xl:px-5', 'text-brand' => request()->routeIs($route), 'text-muted hover:text-ink' => ! request()->routeIs($route)])>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('search') }}" data-open-search class="hidden items-center gap-3 whitespace-nowrap rounded-full border border-line bg-surface px-4 py-2 text-sm text-muted transition hover:border-brand/40 2xl:flex">
                <x-icon name="search" class="size-4" />Search care<kbd class="rounded-md border border-line bg-surface-2 px-1.5 font-sans text-[11px]">/</kbd>
            </a>
            <button type="button" data-locate class="chip max-w-[11rem] sm:max-w-xs" aria-live="polite" title="Use my location"><span class="sr-only sm:hidden">Use my location</span>
                <x-icon name="locate" />
                <span data-location-label class="hidden truncate whitespace-nowrap sm:inline lg:hidden xl:inline">Use my location</span>
            </button>
            <button type="button" data-theme-toggle class="btn-ghost !p-2.5" aria-label="Toggle dark mode">
                <x-icon name="moon" class="size-5" />
            </button>
            @auth
                @if (auth()->user()->isStaff())
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary hidden whitespace-nowrap !py-2 sm:inline-flex" title="Team dashboard"><x-icon name="chart" /><span class="hidden 2xl:inline">{{ auth()->user()->isAdmin() ? 'Admin' : 'Team' }}</span></a>
                @endif
                <a href="{{ route('account') }}" @class(['btn-ghost !p-2.5', '!text-brand' => request()->routeIs('account')]) aria-label="Your account" title="Your account"><x-icon name="user" /></a>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">@csrf<button class="btn-ghost !p-2.5" aria-label="Log out"><x-icon name="logout" /></button></form>
            @else
                <a href="{{ route('login') }}" class="btn-secondary whitespace-nowrap !px-3.5 !py-2 text-sm">Sign in</a>            @endauth
        </div>
    </div>
</header>

<main id="main">
    {{ $slot }}
</main>

<footer class="mt-24 bg-[#000839] text-white/70 lg:mt-36">
    <div class="container-page grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.4fr]">
        <div>
            <div class="flex items-center gap-2.5"><x-brand-mark tile="bg-[#07689f] text-white" text="text-white" inverse /></div>
            <div class="cms-text mt-5 max-w-xs !text-white/70">{{ cms_html('footer.tagline') }}</div>
        </div>
        <div>
            <p class="font-semibold text-white">Find</p>
            <ul class="mt-5 space-y-3 text-sm">
                <li><a class="transition hover:text-white" href="{{ route('search', ['type' => 'specialist']) }}">Specialists</a></li>
                <li><a class="transition hover:text-white" href="{{ route('search', ['type' => 'test']) }}">Tests and scans</a></li>
                <li><a class="transition hover:text-white" href="{{ route('search', ['type' => 'facility']) }}">Health facilities</a></li>
            </ul>
        </div>
        <div>
            <p class="font-semibold text-white">CareSenda</p>
            <ul class="mt-5 space-y-3 text-sm">
                <li><a class="transition hover:text-white" href="{{ route('verify') }}">How we check</a></li>
                <li><a class="transition hover:text-white" href="{{ route('about') }}">About us</a></li>
                <li><a class="transition hover:text-white" href="{{ route('contact') }}">Contact us</a></li>
                <li><a class="transition hover:text-white" href="{{ route('admin.dashboard') }}">Team sign-in</a></li>
            </ul>
        </div>
        <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
            <p class="font-semibold text-white">Good to know</p>
            <div class="cms-text mt-3 text-sm !text-white/70">{{ cms_html('footer.note') }}</div>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-page flex flex-wrap items-center justify-between gap-3 py-6 text-xs">
            <span>© {{ date('Y') }} CareSenda Ghana · <a href="{{ route('credits') }}" class="hover:text-white">Photo credits</a></span><span>Find. Check. Connect.</span>
        </div>
    </div>
</footer>

<nav class="fixed inset-x-3 bottom-3 z-40 grid grid-flow-col auto-cols-fr rounded-2xl border border-line/60 bg-surface/90 p-1.5 shadow-[0_10px_40px_-12px_rgb(7_104_159/.35)] backdrop-blur-xl lg:hidden" style="margin-bottom: env(safe-area-inset-bottom)" aria-label="Mobile">
    @foreach (array_filter([['home', 'home', 'Home'], ['search', 'search', 'Browse'], \App\Services\AccessGate::enabled() ? ['plans', 'shield', 'Plans'] : null, ['about', 'heart', 'About us'], ['contact', 'chat', 'Contact us']]) as [$route, $icon, $label])
        <a href="{{ route($route) }}" @class(['flex flex-col items-center gap-1 rounded-xl py-2 text-[11px] font-semibold transition', 'bg-brand-soft text-brand' => request()->routeIs($route), 'text-muted' => ! request()->routeIs($route)])>
            <x-icon :name="$icon" class="size-5" />{{ $label }}
        </a>
    @endforeach
</nav>

@include('partials.toasts')
@stack('scripts')
</body>
</html>
