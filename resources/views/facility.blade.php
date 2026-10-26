<x-layouts.app :title="$facility->name" :description="$facility->name.' in '.($facility->area ?: $facility->city).': services, contacts and availability.'">
    <div class="container-page py-8">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('search') }}" class="btn-ghost -ml-3 !px-3"><x-icon name="back" />Back</a>

        <div class="mt-4 grid gap-8 lg:grid-cols-3">
            <section class="lg:col-span-2">
                <p class="eyebrow">{{ $facility->facility_type ?? 'Health facility' }}@if ($facility->ownership) · {{ $facility->ownership }}@endif</p>
                <h1 class="mt-2.5 text-[26px] font-bold sm:text-4xl">{{ $facility->name }}</h1>
                <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-muted">
                    <span class="inline-flex items-center gap-2"><x-icon name="pin" class="size-4" />{{ $open['facilities'] ? ($facility->address ?: $facility->city) : ($facility->area ?: $facility->city) }}</span>
                    <x-distance :km="$facility->distanceKm" />
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($open['facilities'])
                        @if ($facility->phone)<a href="tel:{{ $facility->phone }}" class="btn-primary"><x-icon name="phone" />Call {{ $facility->phone }}</a>@endif
                        @if ($facility->whatsappUrl())<a href="{{ $facility->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secondary"><x-icon name="chat" />WhatsApp</a>@endif
                        <a href="{{ $facility->directionsUrl() }}" target="_blank" rel="noopener" class="btn-secondary"><x-icon name="route" />Directions</a>
                    @else
                        <p class="inline-flex items-center gap-2 rounded-2xl bg-surface-2 px-4 py-3 text-sm text-muted"><x-icon name="lock" class="size-4" />The address, phone, WhatsApp, directions and opening hours are for members with a plan that includes facility details. <a href="{{ auth()->check() ? route('plans') : route('register', ['back' => request()->getRequestUri()]) }}" class="text-brand underline">{{ auth()->check() ? 'Choose a plan' : 'Create an account' }}</a></p>
                    @endif
                </div>
                @if ($facility->issue)
                    <p class="mt-6 flex items-start gap-3 rounded-2xl bg-danger-soft px-4 py-3 text-sm text-danger"><x-icon name="alert" class="mt-1 size-4 shrink-0" />{{ $facility->issue }}</p>
                @endif
            </section>
            <aside class="card relative min-h-[240px] overflow-hidden">
                @if (! $open['facilities'])
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-surface-2 p-6 text-center text-sm text-muted"><x-icon name="lock" class="size-5" />The location is for members with a plan that includes facility details.</div>
                @else
                <div data-map data-single="{{ $facility->id }}" data-lat="{{ $facility->lat }}" data-lng="{{ $facility->lng }}" data-zoom="{{ ['exact' => 16, 'area' => 14][$facility->geo_precision] ?? 12 }}" data-precision="{{ $facility->geo_precision }}" class="absolute inset-0"></div>
                <p class="absolute inset-x-3 bottom-3 z-[500] rounded-lg bg-surface/90 px-3 py-1.5 text-xs text-muted backdrop-blur">@if ($facility->geo_precision === 'exact') Exact location @else Shows the {{ $facility->geo_precision === 'area' ? 'neighbourhood' : 'city' }}, not the exact building @endif</p>
                @endif
            </aside>
        </div>

        <div data-stagger class="mt-10 grid gap-4 sm:grid-cols-3">
            <x-stat label="Specialist listings" :value="$specialists->count()" icon="doctor" />
            <x-stat label="Diagnostic tests" :value="$tests->flatten()->count()" icon="test" />
            <x-stat label="Last checked by us" :value="$facility->verified_at?->diffForHumans() ?? 'Not yet'" icon="shield" :hint="! $open['facilities'] ? 'Opening hours need a plan' : ($facility->hours ? 'Hours: '.$facility->hours : 'Opening hours not confirmed yet')" />
        </div>

        @if ($specialists->isNotEmpty() && ! $open['specialists'])
            <section class="mt-12">
                <h2 class="text-xl font-bold">Specialists</h2>
                <x-access-locked class="mt-5" :title="$specialists->count().' specialist '.Str::plural('listing', $specialists->count()).' here'" :plans="$plans->filter(fn ($p) => in_array('specialists', $p->coveredAreas(), true))">
                    Which specialists, the doctors’ names, days and prices are for members with a plan that includes specialists.
                </x-access-locked>
            </section>
        @elseif ($specialists->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-xl font-bold">Specialists</h2>
                <div data-stagger class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($specialists as $s)
                        <div data-reveal class="card flex flex-col gap-4 p-6">
                            <div class="flex items-start gap-4">
                                <span class="icon-tile"><x-icon name="doctor" /></span>
                                <div>
                                    <h3 class="font-bold">{{ $s->service }}</h3>
                                    <p class="text-sm text-muted">{{ $s->specialist_name ?: 'Doctor’s name not recorded yet' }}@if ($s->gender) · {{ $s->gender }}@endif</p>
                                </div>
                            </div>
                            <p class="flex items-center gap-3 text-sm"><x-icon name="calendar" class="size-4 text-muted" />{{ $s->schedule ?: 'Days not confirmed yet' }}</p>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <x-verification-badge :service="$s" />
                                <span class="text-sm font-semibold">{{ $s->price ?: 'Price not confirmed yet' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($tests->isNotEmpty() && ! $open['tests'])
            <section class="mt-12">
                <h2 class="text-xl font-bold">Diagnostic tests</h2>
                <x-access-locked class="mt-5" :title="$tests->flatten()->count().' diagnostic '.Str::plural('test', $tests->flatten()->count()).' here'" :plans="$plans->filter(fn ($p) => in_array('tests', $p->coveredAreas(), true))">
                    Which tests and their prices are for members with a plan that includes diagnostic tests.
                </x-access-locked>
            </section>
        @elseif ($tests->isNotEmpty())
            <section class="mt-12">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-xl font-bold">Diagnostic tests</h2>
                    <input type="search" data-filter-list="#test-groups" placeholder="Find a test…" class="field max-w-xs" aria-label="Filter tests">
                </div>
                <div id="test-groups" class="mt-5 grid gap-4">
                    @foreach ($tests as $category => $group)
                        <details data-reveal class="card group p-0" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-6">
                                <span class="font-bold">{{ $category }}</span>
                                <span class="badge-pending">{{ $group->count() }}</span>
                            </summary>
                            <ul class="divide-y divide-line border-t border-line">
                                @foreach ($group as $t)
                                    <li data-filter-item class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                                        <span class="font-medium">{{ $t->service }}</span>
                                        <span class="flex items-center gap-4"><span class="text-muted">{{ $t->price ?: '' }}</span><x-verification-badge :service="$t" /></span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($open['facilities'])
        <section class="mt-12">
            <details class="card p-6">
                <summary class="cursor-pointer font-bold">Where this information comes from</summary>
                <div class="mt-4 space-y-3 text-sm text-muted">
                    @if ($facility->source)<p><a class="text-brand underline" href="{{ $facility->source }}" target="_blank" rel="noopener">Original listing</a></p>@endif
                    @if ($facility->directory_evidence)
                        <p>Services they list: {{ $facility->directory_evidence['publishedServices'] ?? 'Not listed' }}</p>
                        <p><a class="text-brand underline" href="{{ $facility->directory_evidence['source'] }}" target="_blank" rel="noopener">Insurer directory listing</a></p>
                    @endif
                    @foreach ($facility->register_evidence ?? [] as $e)
                        <p><a class="text-brand underline" href="{{ $e['source'] }}" target="_blank" rel="noopener">HeFRA register, entry {{ $e['row'] }}</a> · licence listed until {{ $e['listedLicenceExpiry'] }} (we haven’t checked if it was renewed)</p>
                    @endforeach
                </div>
            </details>
        </section>
        @endif
    </div>
</x-layouts.app>
