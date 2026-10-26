@php
    $heading = $filters->q ?: ['all' => 'All care', 'specialist' => 'Specialists', 'test' => 'Diagnostic tests', 'facility' => 'Health facilities'][$filters->type];
    $total = $tab === 'facilities' ? $summary['facilities'] : $summary['services'];
    $active = array_filter([
        'city' => $filters->city,
        'diagnostic' => $filters->diagnostic ? config("caresenda.diagnostic_categories.{$filters->diagnostic}") : null,
        'day' => $filters->day,
        'radius' => $filters->radius ? "Within {$filters->radius} km" : null,
        'confirmed' => $filters->confirmedOnly ? 'Only confirmed' : null,
    ]);
@endphp
<x-layouts.app :title="$heading">
    <div class="container-page py-8">
        <a href="{{ route('home') }}" class="btn-ghost -ml-3 !px-3"><x-icon name="back" />Back to home</a>

        <form action="{{ route('search') }}" method="GET" data-autosubmit class="mt-3">
            {{-- Title + search --}}
            @php($headerImage = \App\Support\SpecialtyPhoto::for($filters->q, $filters->type))
            <div class="relative overflow-hidden rounded-[2rem] bg-panel px-6 py-8 sm:px-10 sm:py-10 lg:pr-[38%]">
                <x-photo :image="$headerImage" class="!absolute inset-y-0 right-0 hidden w-[34%] lg:block [mask-image:linear-gradient(90deg,transparent,black_30%)]" sizes="34vw" />
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgb(7_104_159/.12)_1px,transparent_0)] [background-size:22px_22px] [mask-image:linear-gradient(100deg,transparent_40%,black)]"></div>
                <div class="relative">
                    <p class="section-label">You searched for</p>
                    <h1 class="mt-1.5 text-[26px] font-bold sm:text-[40px]">{{ $heading }}</h1>
                    @if ($location)
                        <p class="mt-3 flex items-center gap-2 text-sm">
                            <x-icon name="locate" class="size-4 text-brand" />
                            {{ $location['in_coverage'] ? 'Near '.$location['area'] : 'You’re outside Accra and Tema' }}
                            @if ($filters->sort === 'distance') · closest first @endif
                        </p>
                    @endif

                    <div data-suggest-root class="relative mt-7 flex items-center gap-3 rounded-3xl bg-surface p-2 shadow-card ring-1 ring-line focus-within:ring-brand/40">
                        <x-icon name="search" class="ml-3 size-5 shrink-0 text-brand" />
                        <input name="q" type="search" value="{{ $filters->q }}" autocomplete="off" data-suggest aria-label="Search specialists, tests or facilities" placeholder="Try another search" class="min-w-0 flex-1 bg-transparent py-3 outline-none placeholder:text-muted/70">
                        <button class="btn-primary !py-3.5 sm:!px-8">Search</button>
                    </div>

                    <div class="mt-5 inline-flex max-w-full gap-1 overflow-x-auto rounded-full bg-surface/80 p-1 ring-1 ring-line [scrollbar-width:none]" role="radiogroup" aria-label="Category">
                        @foreach (['all' => 'All', 'specialist' => 'Specialists', 'test' => 'Tests', 'facility' => 'Facilities'] as $value => $label)
                            <label class="shrink-0 cursor-pointer rounded-full px-3 py-1.5 text-[13px] font-medium sm:px-4 sm:py-2 sm:text-sm text-muted transition hover:text-ink has-[:checked]:bg-brand has-[:checked]:text-brand-ink has-[:checked]:shadow-glow">
                                <input type="radio" name="type" value="{{ $value }}" class="sr-only" @checked($filters->type === $value)>{{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-8 lg:grid-cols-[17rem_1fr] xl:grid-cols-[19rem_1fr]">
                {{-- Filters: sticky sidebar on desktop, collapsible on phones --}}
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <details class="card group p-0" data-filters @if ($active) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5 lg:pointer-events-none">
                            <span class="flex items-center gap-2 font-semibold"><x-icon name="filter" class="size-4 text-brand" />Filters</span>
                            @if ($active)<span class="badge-confirmed">{{ count($active) }} on</span>@endif
                        </summary>
                        <div class="grid gap-5 border-t border-line p-5">
                            <label><span class="label">City</span>
                                <select name="city" class="field"><option value="">All locations</option>
                                    @foreach (array_keys(config('caresenda.cities')) as $city)<option @selected($filters->city === $city)>{{ $city }}</option>@endforeach
                                </select>
                            </label>
                            <label><span class="label">Type of test</span>
                                <select name="diagnostic" class="field"><option value="">All</option>
                                    @foreach (config('caresenda.diagnostic_categories') as $k => $l)<option value="{{ $k }}" @selected($filters->diagnostic === $k)>{{ $l }}</option>@endforeach
                                </select>
                            </label>
                            <label><span class="label">Day</span>
                                <select name="day" class="field"><option value="">Any day</option>
                                    @foreach (\App\Services\SearchFilters::DAYS as $d)<option @selected($filters->day === $d)>{{ $d }}</option>@endforeach
                                </select>
                            </label>
                            <label><span class="label">Distance</span>
                                <select name="radius" class="field" @disabled(! $filters->hasOrigin())>
                                    <option value="">Any distance</option>
                                    @foreach ([2, 5, 10, 20] as $r)<option value="{{ $r }}" @selected($filters->radius == $r)>Within {{ $r }} km</option>@endforeach
                                </select>
                                @unless ($filters->hasOrigin())<span class="mt-1.5 block text-xs text-muted">Turn on your location to use this.</span>@endunless
                            </label>
                            <label><span class="label">Sort by</span>
                                <select name="sort" class="field">
                                    @if ($filters->hasOrigin())<option value="distance" @selected($filters->sort === 'distance')>Nearest first</option>@endif
                                    <option value="relevance" @selected($filters->sort === 'relevance')>Confirmed first</option>
                                    <option value="name" @selected($filters->sort === 'name')>Name (A to Z)</option>
                                </select>
                            </label>
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl bg-surface-2 px-4 py-3 text-sm font-medium">
                                Only confirmed
                                <input type="checkbox" name="confirmed" value="1" class="peer sr-only" @checked($filters->confirmedOnly)>
                                <span class="relative h-6 w-11 shrink-0 rounded-full bg-line transition after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-brand peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-accent"></span>
                            </label>
                            @if ($active)
                                <a href="{{ route('search', array_filter(['q' => $filters->q, 'type' => $filters->type !== 'all' ? $filters->type : null])) }}" class="btn-ghost justify-center">Clear all filters</a>
                            @endif
                        </div>
                    </details>
                </aside>

                <div class="min-w-0">
                    {{-- Summary --}}
                    <section data-stagger class="grid grid-cols-3 gap-2.5 sm:gap-4">
                        <x-stat label="Services found" :value="$summary['services']" icon="list" :hint="'at '.$summary['service_facilities'].' places'" />
                        <x-stat label="Confirmed by our team" :value="$summary['confirmed_services']" icon="shield" hint="Prices and hours still need checking" />
                        <x-stat label="Facilities found" :value="$summary['facilities']" icon="building" />
                    </section>

                    @if (cms('search.notice') !== '')
                        <div class="mt-5 flex items-start gap-3 rounded-2xl bg-accent-soft px-4 py-3 text-sm"><x-icon name="info" class="mt-1 size-4 shrink-0 text-accent" /><div class="cms-text">{{ cms_html('search.notice') }}</div></div>
                    @endif

                    @if ($active)
                        <div class="mt-5 flex flex-wrap items-center gap-2" aria-label="Active filters">
                            @foreach ($active as $key => $label)
                                <a href="{{ request()->fullUrlWithQuery([$key => null, 'page' => null]) }}" class="chip !border-brand/30 !bg-brand-soft !py-1.5 text-sm !text-brand" aria-label="Remove filter {{ $label }}">{{ $label }}<x-icon name="x" class="size-3.5" /></a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Tabs --}}
                    <div class="mt-8 inline-flex gap-1 rounded-full bg-surface-2 p-1" role="tablist">
                        @foreach (['services' => ['Services', $summary['services']], 'facilities' => ['Facilities', $summary['facilities']]] as $key => [$label, $count])
                            <a role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
                               @class(['rounded-full px-5 py-2 text-sm font-semibold transition', 'bg-surface text-brand shadow-card' => $tab === $key, 'text-muted hover:text-ink' => $tab !== $key])>
                                {{ $label }} <span class="ml-1 rounded-full bg-brand-soft px-2 py-0.5 text-xs text-brand">{{ number_format($count) }}</span>
                            </a>
                        @endforeach
                    </div>

                    @if ($locked && $total > 0)
                        <x-access-locked class="mt-6" :title="number_format($total).' '.($tab === 'facilities' ? Str::plural('facility', $total) : Str::plural('listing', $total)).' match this search'" :plans="$plans" :unlock="$unlock" :searches-left="$access['searches_left'] ?? 0">
                            Names, contacts, doctors, days and prices are for members with a plan that includes {{ $lockedAreas }}.
                        </x-access-locked>
                    @elseif ($results === null || $results->isEmpty())
                        <div class="card mt-6 flex flex-col items-center gap-4 p-12 text-center">
                            <span class="icon-tile"><x-icon name="search" /></span>
                            <h2 class="text-xl font-bold">We couldn’t find anything for that</h2>
                            <p class="max-w-md text-sm">Try a different word or spelling, or clear some of the filters.</p>
                            <a href="{{ route('search', ['q' => $filters->q]) }}" class="btn-secondary">Clear filters</a>
                        </div>
                    @else
                        <p class="mt-5 text-sm text-muted">Showing {{ number_format($results->firstItem()) }} to {{ number_format($results->lastItem()) }} of {{ number_format($total) }}</p>
                        <div data-stagger @class(['mt-4 grid gap-4', 'sm:grid-cols-2 xl:grid-cols-3' => true])>
                            @foreach ($results as $item)
                                @if ($tab === 'facilities')
                                    <x-facility-card :facility="$item" />
                                @else
                                    <x-service-card :service="$item" />
                                @endif
                            @endforeach
                        </div>
                        <div class="mt-10">{{ $results->onEachSide(1)->links() }}</div>
                    @endif
                </div>
            </div>
            <input type="hidden" name="tab" value="{{ $tab }}">
        </form>
    </div>
</x-layouts.app>
