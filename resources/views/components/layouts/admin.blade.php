@props(['title', 'heading' => null])
@php
    $unread = \App\Models\ContactMessage::whereNull('read_at')->count();
    // Sidebar sections: [route, active pattern, icon, label]
    $sections = [
        'Overview' => [['admin.dashboard', 'admin.dashboard', 'chart', 'Dashboard']],
        'Directory' => [
            ['admin.facilities.index', 'admin.facilities.*', 'building', 'Facilities'],
            ['admin.catalog.index', 'admin.catalog.*', 'list', 'Services'],
            ['admin.specialties.index', 'admin.specialties.*', 'doctor', 'Specialties'],
        ],
        'Website' => [
            ['admin.messages.index', 'admin.messages.*', 'chat', 'Messages'],
        ],
        'Business' => [
            ['admin.plans.index', 'admin.plans.*', 'sparkle', 'Plans'],
            ['admin.payments.index', 'admin.payments.*', 'shield', 'Payments'],
        ],
        'System' => [
            ['admin.settings.edit', 'admin.settings.*', 'filter', 'Settings'],
            ['admin.api', 'admin.api', 'code', 'API'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $title.' · Admin', 'description' => null])
</head>
<body class="admin-area min-h-dvh">
<script>try{if(localStorage.getItem('cs-sidebar')==='collapsed')document.documentElement.classList.add('sidebar-collapsed')}catch(e){}</script>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] btn-primary">Skip to content</a>

<div data-admin-shell class="admin-shell lg:grid lg:grid-cols-[16rem_1fr] lg:transition-[grid-template-columns] lg:duration-300">
    {{-- Sidebar: fixed column on desktop, slide-out drawer on phones --}}
    <div data-admin-backdrop class="fixed inset-0 z-40 hidden bg-ink/40 backdrop-blur-sm lg:hidden"></div>
    <aside data-admin-sidebar class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-line bg-surface transition-[transform,width] duration-300 lg:sticky lg:top-0 lg:h-dvh lg:w-auto lg:translate-x-0" aria-label="Dashboard">
        <div class="flex h-16 items-center justify-between gap-3 px-5 lg:h-20">
            <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3">
                @if ($logo = \App\Support\Branding::logoUrl())<img src="{{ $logo }}" alt="" class="size-9 shrink-0 rounded-md object-contain">@else<img src="/images/brand/icon-128.webp" alt="" width="36" height="36" class="size-9 shrink-0 object-contain">@endif
                <span class="sidebar-text min-w-0 leading-tight"><span class="block truncate font-display text-[15px] font-semibold text-ink">{{ \App\Support\Branding::name() }}</span><span class="block text-xs text-muted">Team dashboard</span></span>
            </a>
            <button type="button" data-admin-close class="btn-ghost !p-2 lg:hidden" aria-label="Close menu"><x-icon name="x" /></button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-4">
            @foreach ($sections as $section => $links)
                <p class="sidebar-text px-3 pb-1.5 pt-5 text-[11px] font-semibold uppercase tracking-wider text-muted/80 first:pt-2">{{ $section }}</p>
                @foreach ($links as [$route, $pattern, $icon, $label])
                    @php($active = request()->routeIs($pattern))
                    <a href="{{ route($route) }}" title="{{ $label }}" @if ($active) aria-current="page" @endif
                       @class(['sidebar-link group relative flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition', 'bg-brand-soft text-brand' => $active, 'text-muted hover:bg-surface-2 hover:text-ink' => ! $active])>
                        @if ($active)<span class="absolute inset-y-1.5 left-0 w-[3px] rounded-full bg-brand"></span>@endif
                        <x-icon :name="$icon" class="size-[18px] shrink-0" />
                        <span class="sidebar-text flex-1 truncate">{{ $label }}</span>
                        @if ($route === 'admin.messages.index' && $unread)<span class="sidebar-badge rounded-full bg-danger px-1.5 py-px text-[11px] font-bold text-white">{{ $unread }}</span>@endif
                    </a>
                @endforeach

                @if ($section === 'Website')
                    @php($inContent = request()->routeIs('admin.content.*'))
                    <details class="group/cms" @if ($inContent) open @endif>
                        <summary title="Page content" @class(['sidebar-link relative flex cursor-pointer list-none items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition [&::-webkit-details-marker]:hidden', 'text-brand' => $inContent, 'text-muted hover:bg-surface-2 hover:text-ink' => ! $inContent])>
                            <x-icon name="home" class="size-[18px] shrink-0" />
                            <span class="sidebar-text flex-1">Page content</span>
                            <svg class="sidebar-text size-4 transition-transform group-open/cms:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="sidebar-text ml-5 mt-0.5 space-y-0.5 border-l border-line pl-3">
                            @foreach (\App\Support\Cms::pages() as $slug => [$pageName])
                                @php($on = request()->routeIs('admin.content.edit') && request()->route('page') === $slug)
                                <a href="{{ route('admin.content.edit', $slug) }}" @if ($on) aria-current="page" @endif
                                   @class(['block rounded-md px-3 py-1.5 text-sm transition', 'bg-brand-soft font-medium text-brand' => $on, 'text-muted hover:bg-surface-2 hover:text-ink' => ! $on])>{{ $pageName }}</a>
                            @endforeach
                        </div>
                    </details>
                @endif

                @if ($section === 'System')
                    <a href="{{ route('admin.export') }}" title="Download data" class="sidebar-link flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted transition hover:bg-surface-2 hover:text-ink">
                        <x-icon name="download" class="size-[18px] shrink-0" /><span class="sidebar-text">Download data</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-line p-3">
            <a href="{{ route('home') }}" target="_blank" title="View the website" class="sidebar-link flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted transition hover:bg-surface-2 hover:text-ink">
                <x-icon name="arrow" class="size-[18px] shrink-0 -rotate-45" /><span class="sidebar-text">View the website</span>
            </a>
            <button type="button" data-sidebar-collapse class="sidebar-link hidden w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted transition hover:bg-surface-2 hover:text-ink lg:flex" aria-label="Collapse sidebar">
                <svg class="size-[18px] shrink-0 transition-transform" data-collapse-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                <span class="sidebar-text">Collapse</span>
            </button>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="min-w-0">
        {{-- Preloader covers only this content side; the sidebar stays usable. --}}
        @include('partials.preloader', ['scope' => 'content'])
        <header class="sticky top-0 z-30 border-b border-line bg-surface/85 backdrop-blur-xl">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-8 lg:h-20">
                <button type="button" data-admin-open class="btn-ghost -ml-2 !p-2 lg:hidden" aria-label="Open menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <div class="min-w-0">
                    <p class="hidden text-xs text-muted sm:block">Team dashboard</p>
                    <h1 class="truncate text-lg font-bold sm:text-xl">{{ $heading ?? $title }}</h1>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    @isset($actions){{ $actions }}@endisset
                    <button type="button" data-theme-toggle class="btn-ghost !p-2.5" aria-label="Toggle dark mode"><x-icon name="moon" class="size-5" /></button>

                    {{-- Account menu --}}
                    <details class="relative" data-dropdown>
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-full py-1 pl-1 pr-2 transition hover:bg-surface-2 [&::-webkit-details-marker]:hidden" aria-label="Account menu">
                            <x-admin.avatar :user="auth()->user()" class="text-sm" />
                            <span class="hidden min-w-0 text-left leading-tight sm:block">
                                <span class="block max-w-40 truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</span>
                                <span class="block max-w-40 truncate text-xs text-muted">{{ auth()->user()->username ?? auth()->user()->email }}</span>
                            </span>
                            <svg class="hidden size-4 text-muted sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="absolute right-0 top-full z-50 mt-2 w-60 rounded-2xl border border-line bg-surface p-1.5 shadow-[0_18px_50px_-16px_rgb(7_104_159/.35)]" role="menu">
                            <div class="flex items-center gap-3 border-b border-line px-3 pb-2.5 pt-1.5">
                                <x-admin.avatar :user="auth()->user()" size="size-10" class="text-base" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-muted">{{ auth()->user()->email }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.profile.edit') }}" role="menuitem" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-ink transition hover:bg-brand-soft hover:text-brand">
                                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button role="menuitem" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm text-danger transition hover:bg-danger-soft"><x-icon name="logout" class="size-[18px]" />Sign out</button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main id="main" class="px-4 py-6 sm:px-8 sm:py-8">
            {{ $slot }}
        </main>
    </div>
</div>

@include('partials.toasts')
@stack('scripts')
</body>
</html>
