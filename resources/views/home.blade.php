<x-layouts.app>
    @php($hero = \App\Support\Cms::image('home.hero_image'))
    @push('head')
        <link rel="preload" as="image" type="image/webp" imagesrcset="{{ $hero['srcset'] }}" imagesizes="(min-width: 1024px) 480px, 80vw">
    @endpush

    {{-- Hero: rounded panel, photo in a circle, floating badges, segmented search pill --}}
    <section class="container-page pt-4 sm:pt-6">
        <div class="relative flex items-center overflow-hidden rounded-[2rem] bg-panel px-5 py-12 sm:px-10 lg:min-h-[760px] lg:px-[90px] lg:py-20">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgb(7_104_159/.13)_1px,transparent_0)] [background-size:22px_22px] [mask-image:linear-gradient(110deg,black,transparent_65%)]"></div>
            <div class="animate-blob pointer-events-none absolute -right-10 -top-24 size-[30rem] rounded-full bg-[#17b07d]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 -top-32 size-[34rem] rounded-full bg-surface/60 max-lg:hidden"></div>
            <div class="animate-blob pointer-events-none absolute -bottom-20 -left-16 size-56 rounded-full bg-brand-gradient opacity-10 blur-2xl"></div>

            <div class="relative grid w-full items-center gap-14 lg:grid-cols-[1.15fr_.85fr]">
                <div class="reveal">
                    <p class="section-label flex items-center gap-2 sm:gap-3 sm:!text-[22px]"><x-icon name="shield" class="size-5" />{{ cms('home.hero_label') }}</p>
                    <h1 class="mt-5 text-[30px] font-bold leading-[1.12] !text-brand sm:text-4xl lg:text-5xl">
                        {!! nl2br(e(cms('home.hero_title'))) !!}
                    </h1>
                    <div class="cms-text mt-5 max-w-xl text-base font-medium sm:mt-7 sm:text-lg [&_em]:font-bold [&_em]:not-italic [&_em]:text-brand [&_em]:underline [&_em]:decoration-brand/30 [&_em]:decoration-4 [&_em]:underline-offset-4">{{ cms_html('home.hero_text') }}</div>

                    <form action="{{ route('search') }}" method="GET" role="search" data-suggest-root class="relative mt-10 flex flex-col gap-1.5 rounded-3xl bg-surface p-2 shadow-card ring-1 ring-line focus-within:ring-brand/40 sm:flex-row sm:items-center">
                        <label class="flex min-w-0 flex-1 items-center gap-3 px-4">
                            <x-icon name="search" class="size-5 shrink-0 text-brand" />
                            <span class="sr-only">What care are you looking for?</span>
                            <input name="q" type="search" autocomplete="off" data-suggest placeholder="e.g. paediatrician, FBC, ultrasound" class="w-full min-w-0 bg-transparent py-3 outline-none placeholder:text-muted/70">
                        </label>
                        <span class="hidden h-8 w-px bg-line sm:block"></span>
                        <label class="px-4 sm:px-2"><span class="sr-only">Category</span>
                            <select name="type" class="w-full cursor-pointer bg-transparent py-3 text-sm font-medium outline-none sm:w-auto">
                                <option value="all">Anything</option><option value="specialist">Specialists</option><option value="test">Tests</option><option value="facility">Facilities</option>
                            </select>
                        </label>
                        <button class="btn-primary !py-4 sm:!px-10">Search<x-icon name="arrow" /></button>
                    </form>

                    <div class="mt-6 flex flex-wrap items-center gap-3" aria-label="Popular searches">
                        <span class="text-sm font-medium text-muted">People often search:</span>
                        @foreach (['Gynaecologist', 'Full blood count', 'Ultrasound', 'Paediatrician', 'Malaria'] as $q)
                            <a href="{{ route('search', ['q' => $q]) }}" class="chip !bg-surface/70 !py-1.5 text-xs">{{ $q }}</a>
                        @endforeach
                    </div>
                </div>

                <figure class="relative mx-auto w-full max-w-[22rem] sm:max-w-[26rem] lg:max-w-[32rem] 2xl:max-w-[36rem] reveal [animation-delay:.15s]">
                    <div class="absolute inset-0 translate-x-4 translate-y-4 rounded-full bg-brand"></div>
                    <img src="{{ $hero['src'] }}" srcset="{{ $hero['srcset'] }}" sizes="(min-width: 1024px) 480px, 80vw"
                         width="{{ $hero['width'] }}" height="{{ $hero['height'] }}" fetchpriority="high" decoding="async"
                         alt="{{ $hero['alt'] }}"
                         class="relative aspect-square w-full rounded-full object-cover object-[55%_center] ring-8 ring-surface">
                    @unless ($hero['custom'])<figcaption class="sr-only">AI-generated concept image</figcaption>@endunless

                    <div class="card float-slow absolute -left-4 top-6 flex items-center gap-4 !rounded-2xl p-3 sm:-left-10">
                        <span class="grid size-11 place-items-center rounded-2xl bg-brand text-brand-ink"><x-icon name="pin" class="size-5" /></span>
                        <div><p class="text-base font-semibold" data-location-label-hero>Near you</p><p class="text-xs text-muted">Closest places first</p></div>
                    </div>
                    <div class="card float-slow absolute -right-2 bottom-10 flex items-center gap-4 !rounded-2xl p-3 [animation-delay:1.5s] sm:-right-8">
                        <span class="grid size-11 place-items-center rounded-2xl bg-brand-soft text-brand"><x-icon name="shield" class="size-5" /></span>
                        <div><p class="text-base font-semibold">{{ number_format($overview['laboratories']) }} labs</p><p class="text-xs text-muted">Tests confirmed by our team</p></div>
                    </div>
                    <div class="card absolute -bottom-3 left-8 flex items-center gap-3 !rounded-full px-4 py-2 text-sm font-semibold">
                        <span class="size-2 rounded-full bg-emerald-500"></span>{{ number_format($overview['facilities']) }} places listed
                    </div>
                </figure>
            </div>
        </div>
    </section>

    {{-- Easy steps --}}
    <section class="container-page mt-24 lg:mt-36">
        <x-section-heading :label="cms('home.steps_label')" title="{{ cms('home.steps_title') }}" center />
        <ol data-stagger class="mt-12 grid grid-cols-2 gap-3 sm:gap-5 lg:mt-20 lg:grid-cols-4 lg:gap-6">
            @foreach ([
                ['search', cms('home.step1_title'), cms_html('home.step1_text')],
                ['locate', cms('home.step2_title'), cms_html('home.step2_text')],
                ['shield', cms('home.step3_title'), cms_html('home.step3_text')],
                ['phone', cms('home.step4_title'), cms_html('home.step4_text')],
            ] as [$icon, $title, $text])
                <li data-reveal class="card group p-5 transition hover:-translate-y-1 hover:shadow-glow sm:p-7">
                    <span class="grid size-12 place-items-center rounded-xl lg:size-14 lg:rounded-2xl bg-brand-soft text-muted transition group-hover:bg-brand group-hover:text-brand-ink"><x-icon :name="$icon" class="size-5 lg:size-6" stroke-width="1.75" /></span>
                    <h3 class="mt-4 text-base font-semibold sm:text-lg lg:mt-5 lg:text-xl">{{ $title }}</h3>
                    <div class="cms-text mt-1.5 text-sm sm:text-base lg:mt-3 lg:text-lg">{{ $text }}</div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Bento grid: ways in + live numbers --}}
    <section class="container-page mt-24 lg:mt-36">
        <x-section-heading :label="cms('home.tiles_label')" title="{{ cms('home.tiles_title') }}" center>
            {{ cms_html('home.tiles_text') }}
        </x-section-heading>

        <div data-stagger class="mt-12 grid auto-rows-[minmax(190px,auto)] gap-5 md:grid-cols-2 lg:mt-20 lg:grid-cols-4">
            {{-- Large tile --}}
            <a data-reveal href="{{ route('search', ['type' => 'test']) }}" class="group relative flex flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-[#07689f] to-[#04496f] p-8 text-white shadow-glow transition hover:-translate-y-1 md:col-span-2 lg:row-span-2 lg:p-10">
                <x-photo cms="home.tile_tests_image" :credit="false" class="!absolute inset-0" img-class="size-full object-cover transition duration-700 group-hover:scale-105" sizes="(min-width: 1024px) 50vw, 100vw" />
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#04496f] via-[#07689f]/85 to-[#07689f]/40"></div>
                <span class="relative grid size-12 place-items-center rounded-xl bg-white/15 backdrop-blur"><x-icon name="test" class="size-6" stroke-width="1.75" /></span>
                <div class="relative mt-10">
                    <p class="font-display text-4xl font-bold !text-white sm:text-5xl lg:text-6xl" data-count="{{ $overview['tests'] }}">{{ number_format($overview['tests']) }}</p>
                    <h3 class="mt-3 text-xl font-semibold !text-white sm:text-2xl">{{ cms('home.tile_tests_title') }}</h3>
                    <div class="cms-text mt-2 max-w-md text-base !text-white/80 sm:text-lg">{{ cms_html('home.tile_tests_text') }}</div>
                    <span class="mt-6 inline-flex items-center gap-2 font-semibold">Find a test<x-icon name="arrow" class="size-5 transition group-hover:translate-x-1" /></span>
                </div>
            </a>

            <a data-reveal href="{{ route('search', ['type' => 'specialist']) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-glow">
                <x-photo cms="home.tile_specialists_image" :link-credit="false" class="h-36 shrink-0" img-class="size-full object-cover transition duration-700 group-hover:scale-105" sizes="(min-width: 1024px) 25vw, (min-width: 768px) 50vw, 100vw" />
                <div class="flex flex-1 flex-col p-6">
                    <h3 class="text-xl font-semibold">{{ cms('home.tile_specialists_title') }}</h3>
                    <div class="cms-text mt-1.5">{{ cms_html('home.tile_specialists_text') }}</div>
                    <span class="mt-auto inline-flex items-center gap-1.5 pt-3 text-sm font-semibold text-brand">{{ $overview['specialists'] }} listings<x-icon name="arrow" class="size-4 transition group-hover:translate-x-1" /></span>
                </div>
            </a>

            <a data-reveal href="{{ route('search', ['type' => 'facility']) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-glow">
                <x-photo cms="home.tile_facilities_image" :link-credit="false" class="h-36 shrink-0" img-class="size-full object-cover transition duration-700 group-hover:scale-105" sizes="(min-width: 1024px) 25vw, (min-width: 768px) 50vw, 100vw" />
                <div class="flex flex-1 flex-col p-6">
                    <h3 class="text-xl font-semibold">{{ cms('home.tile_facilities_title') }}</h3>
                    <div class="cms-text mt-1.5">{{ cms_html('home.tile_facilities_text') }}</div>
                    <span class="mt-auto inline-flex items-center gap-1.5 pt-3 text-sm font-semibold text-brand">{{ $overview['facilities'] }} facilities<x-icon name="arrow" class="size-4 transition group-hover:translate-x-1" /></span>
                </div>
            </a>

            <a data-reveal href="{{ route('search', ['diagnostic' => 'imaging']) }}" class="card group flex items-center gap-5 p-7 transition hover:-translate-y-1 hover:shadow-glow">
                <span data-tile class="grid size-11 shrink-0 place-items-center rounded-xl bg-accent-soft text-accent"><x-icon name="scan" class="size-5" /></span>
                <div>
                    <p class="font-display text-3xl font-bold text-ink" data-count="{{ $overview['imaging_centres'] }}">{{ $overview['imaging_centres'] }}</p>
                    <p class="text-sm">imaging centres for X-ray, CT and MRI</p>
                </div>
            </a>

            <div data-reveal class="card flex items-center gap-5 bg-gradient-to-br from-surface to-brand-soft p-7">
                <span data-tile class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand text-brand-ink"><x-icon name="pin" class="size-5" /></span>
                <div>
                    <p class="font-display text-3xl font-bold text-ink" data-count="{{ $overview['areas'] }}">{{ $overview['areas'] }}</p>
                    <p class="text-sm">neighbourhoods across Accra and Tema</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Location-aware dashboard on a pale panel --}}
    <section id="nearby" class="container-page mt-24 lg:mt-36 scroll-mt-20">
        <div class="rounded-[2rem] bg-panel p-6 sm:p-10" data-nearby-panel data-endpoint="{{ route('nearby.panel') }}">
            @include('partials.nearby')
        </div>
    </section>

    {{-- Verified by our team: offset gradient block + stat cards --}}
    <section class="container-page mt-24 lg:mt-36 grid items-center gap-14 lg:grid-cols-2">
        <div data-reveal="left" class="relative mx-auto w-full max-w-xl pb-10 pl-6">
            <div class="absolute bottom-0 left-0 h-4/5 w-4/5 rounded-[2rem] bg-brand-gradient"></div>
            <x-photo cms="home.trust_image" class="relative aspect-[4/3] rounded-[2rem] shadow-card" sizes="(min-width: 1024px) 40vw, 100vw" />
            <div class="card float-slow absolute -right-2 bottom-2 w-64 space-y-3 p-4 sm:-right-6">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-ink">Full blood count</p>
                    <span class="badge-verified !px-2 !py-0.5 !text-[11px]"><x-icon name="shield" class="size-3" />Checked</span>
                </div>
                <p class="text-xs">East Legon · Mon to Sat</p>
                <p class="text-xs text-muted">Last checked 22 Sep 2026</p>
            </div>
        </div>
        <div data-reveal="right">
            <x-section-heading :label="cms('home.trust_label')" title="{{ cms('home.trust_title') }}">
                {{ cms_html('home.trust_text') }}
            </x-section-heading>
            <div data-stagger class="mt-10 grid grid-cols-3 gap-4">
                @foreach ([[$overview['laboratories'], 'Labs confirmed'], [$overview['confirmed_services'], 'Tests confirmed'], [$overview['areas'], 'Neighbourhoods covered']] as [$n, $l])
                    <div data-reveal class="card p-4 text-center sm:p-5">
                        <p class="font-display text-2xl font-bold text-brand sm:text-[40px]" data-count="{{ $n }}">{{ number_format($n) }}</p>
                        <p class="mt-1.5 text-xs text-muted sm:text-sm">{{ $l }}</p>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('verify') }}" class="btn-secondary mt-10">How we check<x-icon name="arrow" /></a>
        </div>
    </section>

    {{-- Popular searches & recent --}}
    <section class="container-page mt-24 lg:mt-36 grid gap-8 lg:grid-cols-3">
        <div data-reveal class="card p-6 sm:p-8 lg:col-span-2">
            <p class="section-label">Specialists people look for</p>
            <div class="mt-5 flex flex-wrap gap-3">
                @foreach ($specialties as $name => $count)
                    <a href="{{ route('search', ['q' => $name, 'type' => 'specialist']) }}" class="chip"><x-icon name="doctor" />{{ $name }} <span class="rounded-full bg-surface-2 px-1.5 text-xs text-muted">{{ $count }}</span></a>
                @endforeach
            </div>
            <p class="section-label mt-10">Tests and scans</p>
            <div class="mt-5 flex flex-wrap gap-3">
                @foreach (config('caresenda.diagnostic_categories') as $key => $label)
                    <a href="{{ route('search', ['diagnostic' => $key]) }}" class="chip"><x-icon :name="$key === 'imaging' ? 'scan' : ($key === 'laboratory' ? 'test' : 'pulse')" />{{ $label }}</a>
                @endforeach
                @foreach (config('caresenda.common_tests') as $test)
                    <a href="{{ route('search', ['q' => $test, 'type' => 'test']) }}" class="chip">{{ $test }}</a>
                @endforeach
            </div>
        </div>
        <div data-reveal style="--i:1" class="card p-6 sm:p-8">
            <div class="flex items-center justify-between">
                <p class="section-label">Recent searches</p>
                <button type="button" data-recent-clear class="btn-ghost !px-2 !py-1 text-xs">Clear</button>
            </div>
            <ul data-recent-list class="mt-5 space-y-3"><li class="text-sm text-muted">Nothing yet. Searches you make on this device will show up here.</li></ul>
        </div>
    </section>

    {{-- Call to action on a pale panel (Phlox "Change your life" band) --}}
    <section class="container-page mt-24 lg:mt-36">
        <div data-reveal="zoom" class="grid items-center gap-8 rounded-[2rem] bg-panel px-6 py-14 sm:px-12 lg:grid-cols-[1fr_auto] lg:px-[100px] lg:py-20">
            <div>
                <p class="section-label">{{ cms('home.cta_label') }}</p>
                <h2 class="mt-1 text-[24px] font-bold leading-[1.3] sm:text-3xl lg:text-[36px]">{{ cms('home.cta_title') }}</h2>
                <div class="cms-text mt-4 max-w-2xl text-base sm:text-lg">{{ cms_html('home.cta_text') }}</div>
            </div>
            <div class="flex flex-wrap gap-4">
                <button type="button" data-locate data-force class="btn-primary btn-lg"><x-icon name="locate" />Use my location</button>
                <a href="{{ route('contact') }}" class="btn-secondary btn-lg"><x-icon name="chat" />Contact us</a>
            </div>
        </div>
    </section>
</x-layouts.app>
