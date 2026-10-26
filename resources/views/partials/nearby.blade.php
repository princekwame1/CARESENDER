{{-- "Near you" dashboard panel. Rendered on the server when the location cookie exists, or fetched by JS after detection. --}}
@if ($location && $location['in_coverage'])
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="section-label">Near you</p>
            <h2 class="mt-1 text-[22px] font-bold sm:text-3xl">Care around {{ $location['area'] }}</h2>
            <p class="mt-1.5 text-sm text-muted">Within {{ $nearby['radius_km'] }} km of you. Distances are rough, as we measure to the centre of each neighbourhood.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('search', ['sort' => 'distance', 'radius' => 5]) }}" class="btn-primary"><x-icon name="list" />See everything within 5 km</a>
            <button type="button" data-locate data-force class="btn-secondary"><x-icon name="locate" />Refresh my location</button>
        </div>
    </div>

    <div data-stagger class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Places nearby" :value="$nearby['facilities']" icon="building" />
        <x-stat label="Laboratories" :value="$nearby['laboratories']" icon="test" />
        <x-stat label="Tests offered" :value="$nearby['tests']" icon="pulse" />
        <x-stat label="Specialists" :value="$nearby['specialists']" icon="doctor" />
    </div>

    <div class="mt-8 grid gap-5 lg:grid-cols-5">
        {{-- Compact list beside the map; both share one fixed height --}}
        <div class="card flex flex-col overflow-hidden lg:col-span-2 lg:h-[380px]">
            <div class="flex items-center justify-between border-b border-line/70 px-5 py-3.5">
                <p class="text-sm font-semibold">Closest to you</p>
                @unless (app(\App\Services\AccessGate::class)->opensAllFacilities(request()))
                    <a href="{{ route('plans') }}" class="inline-flex items-center gap-1 text-xs text-muted hover:text-brand"><x-icon name="lock" class="size-3.5" />Names and locations need a plan</a>
                @endunless
                <a href="{{ route('search', ['type' => 'facility', 'sort' => 'distance']) }}" class="text-xs font-medium text-brand hover:underline">See all</a>
            </div>
            <ul data-scroll-fade class="divide-y divide-line/70 overflow-y-auto overscroll-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [mask-image:linear-gradient(to_bottom,black_calc(100%-48px),transparent)] data-[end]:[mask-image:none]">
                @forelse ($nearest as $facility)
                    @if (app(\App\Services\AccessGate::class)->allows(request(), $facility->id))
                        <li class="group relative flex items-center gap-3 px-5 py-3 transition hover:bg-surface-2">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('facilities.show', $facility) }}" class="block truncate text-sm font-semibold text-ink after:absolute after:inset-0 group-hover:text-brand">{{ $facility->name }}</a>
                                <p class="truncate text-xs text-muted">{{ $facility->facility_type ?? 'Health facility' }} · {{ $facility->area ?: $facility->city }}</p>
                            </div>
                            <x-distance :km="$facility->distanceKm" class="shrink-0" />
                            @if ($facility->phone)
                                <a href="tel:{{ $facility->phone }}" class="relative z-10 grid size-8 shrink-0 place-items-center rounded-full bg-brand-soft text-brand transition hover:bg-brand hover:text-brand-ink" aria-label="Call {{ $facility->name }}"><x-icon name="phone" class="size-4" /></a>
                            @endif
                        </li>
                    @else
                        {{-- Locked: the real name, area and distance are never sent; the blurred text is a stand-in. --}}
                        <li class="flex items-center gap-3 px-5 py-3" aria-label="{{ $facility->facility_type ?? 'Health facility' }}, details need a plan">
                            <div class="min-w-0 flex-1" aria-hidden="true">
                                <p class="select-none truncate text-sm font-semibold text-ink blur-[5px]">{{ Str::of('Locked health facility name')->limit(14 + $loop->index % 3 * 6, '') }}</p>
                                <p class="truncate text-xs text-muted">{{ $facility->facility_type ?? 'Health facility' }} · <span class="select-none blur-[4px]">Area hidden</span></p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1 text-xs text-muted"><x-icon name="lock" class="size-3.5" /><span class="select-none blur-[4px]" aria-hidden="true">~0.0 km</span></span>
                        </li>
                    @endif
                @empty
                    <li class="px-5 py-6 text-sm text-muted">We don’t have anything listed within 15 km of you yet.</li>
                @endforelse
            </ul>
        </div>
        <div class="card relative h-[300px] overflow-hidden sm:h-[380px] lg:col-span-3">
            <div data-map data-lat="{{ $location['lat'] }}" data-lng="{{ $location['lng'] }}" data-zoom="13" class="absolute inset-0" aria-label="Map of nearby facilities"></div>
            @include('partials.map-lock')
        </div>
    </div>
@elseif ($location)
    <div class="card flex flex-col items-start gap-5 p-6 sm:flex-row sm:items-center">
        <span class="icon-tile !bg-accent-soft !text-accent"><x-icon name="alert" /></span>
        <div class="flex-1">
            @if ($location['in_country'])
                <h2 class="text-lg font-bold">Looks like you’re outside Accra and Tema</h2>
                <p class="text-sm text-muted">We only cover Accra and Tema for now. Pick a city to keep browsing.</p>
            @else
                <h2 class="text-lg font-bold">Your device says you’re outside Ghana</h2>
                <p class="text-sm text-muted">This often happens on a VPN or some office networks. Turn the VPN off and try again, or pick a city.</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            @unless ($location['in_country'])
                <button type="button" data-locate data-force class="btn-primary"><x-icon name="locate" />Try again</button>
            @endunless
            <a href="{{ route('search', ['city' => 'Accra']) }}" class="btn-secondary">Accra</a>
            <a href="{{ route('search', ['city' => 'Tema']) }}" class="btn-secondary">Tema</a>
        </div>
    </div>
@else
    <div class="grid gap-8 lg:grid-cols-5">
        <div class="card flex flex-col justify-center gap-5 p-6 sm:p-8 lg:col-span-2">
            <span class="icon-tile"><x-icon name="locate" /></span>
            <div>
                <p class="section-label">Near you</p>
                <h2 class="mt-1.5 text-2xl font-extrabold">Find care close to you</h2>
                <p class="mt-2.5 text-sm text-muted">Share your location and we’ll sort everything by distance. We only use it to work out your neighbourhood, and anything we keep is rounded to about a kilometre.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" data-locate data-force class="btn-primary"><x-icon name="locate" />Use my location</button>
                <a href="{{ route('search', ['city' => 'Accra']) }}" class="btn-secondary">Browse Accra</a>
                <a href="{{ route('search', ['city' => 'Tema']) }}" class="btn-secondary">Browse Tema</a>
            </div>
            <p data-locate-error class="hidden text-sm text-danger"></p>
        </div>
        <div class="card relative h-[300px] overflow-hidden sm:h-[340px] lg:col-span-3">
            <div data-map data-lat="5.62" data-lng="-0.15" data-zoom="11" class="absolute inset-0" aria-label="Map of all listed facilities"></div>
            @include('partials.map-lock')
        </div>
    </div>
@endif
