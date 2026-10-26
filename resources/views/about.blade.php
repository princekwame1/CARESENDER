<x-layouts.app title="About us" description="CareSenda helps people in Accra and Tema find specialists, tests and health facilities, checked by phone.">
    @php($overview = app(\App\Services\DirectoryStats::class)->overview())
    <div class="container-page max-w-7xl py-12">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div data-reveal>
                <p class="section-label">About us</p>
                <h1 class="mt-2 text-[28px] font-bold sm:text-4xl">{{ cms('about.title') }}</h1>
                <div class="cms-text mt-5 text-base sm:text-lg">{{ cms_html('about.intro') }}</div>
            </div>
            <x-photo data-reveal="right" cms="about.image" class="aspect-[4/3] rounded-[2rem] shadow-card" sizes="(min-width: 1024px) 40vw, 100vw" eager />
        </div>

        <div data-stagger class="mt-16 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat label="Health facilities" :value="$overview['facilities']" icon="building" />
            <x-stat label="Tests and scans" :value="$overview['tests']" icon="test" />
            <x-stat label="Specialist listings" :value="$overview['specialists']" icon="doctor" />
            <x-stat label="Neighbourhoods" :value="$overview['areas']" icon="pin" />
        </div>

        <div class="mt-20 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['search', cms('about.card1_title'), cms_html('about.card1_text')],
                ['phone', cms('about.card2_title'), cms_html('about.card2_text')],
                ['heart', cms('about.card3_title'), cms_html('about.card3_text')],
            ] as [$icon, $title, $text])
                <div data-reveal class="card p-7">
                    <span class="icon-tile"><x-icon :name="$icon" /></span>
                    <h2 class="mt-5 text-lg font-semibold">{{ $title }}</h2>
                    <div class="cms-text mt-2">{{ $text }}</div>
                </div>
            @endforeach
        </div>

        <div data-reveal="zoom" class="mt-20 grid items-center gap-6 rounded-[2rem] bg-panel px-6 py-12 sm:px-12 lg:grid-cols-[1fr_auto]">
            <div>
                <h2 class="text-[24px] font-bold sm:text-3xl">{{ cms('about.cta_title') }}</h2>
                <div class="cms-text mt-3 max-w-2xl">{{ cms_html('about.cta_text') }}</div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('contact', ['topic' => 'add-facility']) }}" class="btn-primary btn-lg">Contact us<x-icon name="arrow" /></a>
                <a href="{{ route('verify') }}" class="btn-secondary btn-lg">How we check</a>
            </div>
        </div>
    </div>
</x-layouts.app>
