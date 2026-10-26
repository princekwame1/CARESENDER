@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $weekDelta = $week['previous'] > 0 ? (int) round(($week['searches'] - $week['previous']) / $week['previous'] * 100) : null;

    // Column chart: a clean top value (1, 2 or 5 × 10ⁿ) so the axis reads 0 / half / top.
    $peak = max(0, ...array_values($activity['daily']));
    $magnitude = 10 ** max(0, floor(log10(max(1, $peak))));
    $top = collect([1, 2, 5, 10])->map(fn ($m) => $m * $magnitude)->first(fn ($v) => $v >= max(1, $peak));
    $peakDay = $peak > 0 ? array_search($peak, $activity['daily'], true) : null;
@endphp
<x-layouts.admin title="Overview">
    <div class="space-y-6">
        {{-- Greeting and quick actions --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted">{{ now()->format('l, j F Y') }}</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-[28px]">{{ $greeting }}, {{ $firstName }}</h2>
                <p class="mt-1 text-sm text-muted">Here’s how CareSenda is doing.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.facilities.index', ['status' => 'unverified']) }}" class="btn-secondary !py-2 text-sm"><x-icon name="phone" class="size-4" />Call list</a>
                <a href="{{ route('admin.facilities.create') }}" class="btn-primary btn-sm"><x-icon name="building" class="size-4" />Add a facility</a>
            </div>
        </div>

        {{-- Headline numbers --}}
        <div data-stagger class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-tile label="Searches this week" :value="$week['searches']" icon="search" :delta="$weekDelta" :trend="$week['trend']" :hint="$week['previous'] ? null : 'No searches the week before'" />
            <x-admin.stat-tile label="Members" :value="$members['total']" icon="heart" :hint="$members['new'].' joined in the last 7 days'" />
            <x-admin.stat-tile label="Received in 30 days" :value="$revenue['currency'].' '.number_format($revenue['amount'], 2)" icon="sparkle" :href="route('admin.payments.index')"
                :hint="$revenue['enabled'] ? $revenue['count'].' '.\Illuminate\Support\Str::plural('payment', $revenue['count']) : 'Payments are switched off'" />
            <x-admin.stat-tile label="Still to call" :value="$unverifiedWithPhone" icon="phone" hint="Have a phone number, never checked" :href="route('admin.facilities.index', ['status' => 'unverified'])" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Searches per day --}}
            <section class="card p-6 xl:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">Searches in the last 14 days</h2>
                        <p class="mt-0.5 text-sm text-muted">Each column is one day. Hover or tab to a day for its count.</p>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($activity['channels'] as $channel => $n)<span class="badge-pending">{{ $channel === 'api' ? 'App' : ucfirst($channel) }} · {{ number_format($n) }}</span>@endforeach
                    </div>
                </div>

                <dl class="mt-5 grid grid-cols-3 gap-3 rounded-md bg-surface-2/60 p-3 text-center">
                    <div><dt class="text-xs text-muted">Searches</dt><dd class="text-lg font-semibold">{{ number_format($activity['total']) }}</dd></div>
                    <div><dt class="text-xs text-muted">With a location</dt><dd class="text-lg font-semibold">{{ number_format($activity['located']) }}</dd></div>
                    <div><dt class="text-xs text-muted">Found nothing</dt><dd class="text-lg font-semibold">{{ number_format($activity['zero_results']) }}</dd></div>
                </dl>

                <div class="mt-6 grid grid-cols-[2rem_1fr] gap-2">
                    {{-- Y axis --}}
                    <div class="relative h-48 text-right text-[11px] tabular-nums text-muted" aria-hidden="true">
                        <span class="absolute right-0 top-0 -translate-y-1/2">{{ number_format($top) }}</span>
                        <span class="absolute right-0 top-1/2 -translate-y-1/2">{{ number_format($top / 2) }}</span>
                        <span class="absolute bottom-0 right-0 translate-y-1/2">0</span>
                    </div>
                    <div>
                        <div class="relative h-48">
                            <div class="absolute inset-x-0 top-0 border-t border-line" aria-hidden="true"></div>
                            <div class="absolute inset-x-0 top-1/2 border-t border-line" aria-hidden="true"></div>
                            <div class="absolute inset-x-0 bottom-0 border-t border-line" aria-hidden="true"></div>
                            <ol class="relative flex h-full items-end gap-0.5" aria-label="Searches per day">
                                @foreach ($activity['daily'] as $day => $n)
                                    @php($date = \Illuminate\Support\Carbon::parse($day))
                                    <li tabindex="0" class="group relative flex h-full flex-1 cursor-default items-end justify-center outline-none" aria-label="{{ $date->format('l j F') }}: {{ $n }} {{ \Illuminate\Support\Str::plural('search', $n) }}">
                                        <span @class(['block w-full max-w-6 rounded-t-[4px] transition-colors', 'bg-brand' => $loop->index >= 7, 'bg-brand/45' => $loop->index < 7, 'group-hover:bg-brand-strong group-focus-visible:bg-brand-strong']) style="height: {{ $n ? max(1.5, $n / $top * 100) : 0 }}%"></span>
                                        @if ($day === $peakDay)
                                            <span class="pointer-events-none absolute left-1/2 -translate-x-1/2 text-[11px] font-semibold text-ink group-hover:hidden group-focus-visible:hidden" style="bottom: calc({{ $n / $top * 100 }}% + 4px)">{{ $n }}</span>
                                        @endif
                                        <span class="pointer-events-none absolute left-1/2 z-10 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-ink px-2.5 py-1.5 text-left text-bg shadow-lg group-hover:block group-focus-visible:block" style="bottom: calc({{ $n / $top * 100 }}% + 8px)">
                                            <span class="block text-sm font-semibold">{{ number_format($n) }}</span>
                                            <span class="block text-[11px] opacity-75">{{ $date->format('D j M') }}</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                        <div class="mt-2 flex justify-between text-[11px] text-muted" aria-hidden="true">
                            <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($activity['daily']))->format('j M') }}</span>
                            <span>Last week <span class="mx-1 inline-block size-2 rounded-sm bg-brand/45 align-middle"></span> This week <span class="ml-1 inline-block size-2 rounded-sm bg-brand align-middle"></span></span>
                            <span>Today</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Directory health --}}
            <section class="card flex flex-col p-6">
                <h2 class="text-lg font-semibold">Directory health</h2>
                <p class="mt-0.5 text-sm text-muted">{{ number_format($overview['facilities']) }} facilities · {{ number_format($overview['services']) }} listings</p>
                <div class="mt-6 space-y-5">
                    <x-admin.meter label="Listings confirmed" :value="$overview['confirmed_services']" :total="$overview['services']" />
                    <x-admin.meter label="Facilities with a phone number" :value="$overview['with_phone']" :total="$overview['facilities']" />
                    <x-admin.meter label="Facilities checked by the team" :value="$verifiedFacilities" :total="$overview['facilities']" />
                </div>
                <dl class="mt-auto grid grid-cols-3 gap-3 border-t border-line pt-5 text-center">
                    <div><dt class="text-xs text-muted">Tests</dt><dd class="font-semibold">{{ number_format($overview['tests']) }}</dd></div>
                    <div><dt class="text-xs text-muted">Specialists</dt><dd class="font-semibold">{{ number_format($overview['specialists']) }}</dd></div>
                    <div><dt class="text-xs text-muted">Areas</dt><dd class="font-semibold">{{ number_format($overview['areas']) }}</dd></div>
                </dl>
            </section>
        </div>

        {{-- What people look for --}}
        <div class="grid gap-6 md:grid-cols-2">
            <section class="card p-6"><h2 class="font-semibold">What people search for</h2><p class="mb-5 text-xs text-muted">Last 14 days</p><x-admin.rank-list :items="$activity['top_queries']" empty="No searches recorded yet." /></section>
            <section class="card p-6"><h2 class="font-semibold">Where they search from</h2><p class="mb-5 text-xs text-muted">Last 14 days</p><x-admin.rank-list :items="$activity['top_areas']" empty="No searches with a location yet." /></section>
        </div>

        {{-- What the directory holds --}}
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <section class="card p-6"><h2 class="font-semibold">Specialist listings</h2><p class="mb-5 text-xs text-muted">Most common</p><x-admin.rank-list :items="$specialties" /></section>
            <section class="card p-6"><h2 class="font-semibold">Facility types</h2><p class="mb-5 text-xs text-muted">Most common</p><x-admin.rank-list :items="$types" /></section>
            <section class="card p-6"><h2 class="font-semibold">Lab test categories</h2><p class="mb-5 text-xs text-muted">Most common</p><x-admin.rank-list :items="$labCategories" /></section>
        </div>

        {{-- Recent activity --}}
        <div class="grid gap-6 xl:grid-cols-3">
            <section class="card p-6">
                <h2 class="font-semibold">Latest searches</h2>
                <ul class="mt-4 divide-y divide-line text-sm">
                    @forelse ($recentSearches as $log)
                        <li class="flex items-center justify-between gap-4 py-2.5">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-ink">{{ $log->query ?: ucfirst($log->type) }}</span>
                                <span class="block truncate text-xs text-muted">{{ $log->area ?? $log->city ?? 'No location' }} · {{ $log->created_at->diffForHumans(short: true) }}</span>
                            </span>
                            @if ($log->results_count === 0)
                                <span class="badge shrink-0 bg-danger-soft text-danger"><x-icon name="alert" />No results</span>
                            @else
                                <span class="shrink-0 text-xs tabular-nums text-muted">{{ number_format($log->results_count) }} results</span>
                            @endif
                        </li>
                    @empty
                        <li class="py-6 text-center text-muted">No searches yet.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">Recently checked</h2>
                    <a href="{{ route('admin.facilities.index', ['status' => 'unverified']) }}" class="text-sm font-medium text-brand hover:underline">Call list</a>
                </div>
                <ul class="mt-4 divide-y divide-line text-sm">
                    @forelse ($recentlyVerified as $f)
                        <li class="flex items-center justify-between gap-4 py-2.5">
                            <span class="min-w-0">
                                <a href="{{ route('admin.facilities.edit', $f) }}" class="block truncate font-medium text-ink hover:text-brand">{{ $f->name }}</a>
                                <span class="block truncate text-xs text-muted">{{ $f->area ?: $f->city }}</span>
                            </span>
                            <span class="badge-confirmed shrink-0"><x-icon name="check" />{{ $f->verified_at->diffForHumans(short: true) }}</span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-muted">No one has been checked yet.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">Latest payments</h2>
                    <a href="{{ route('admin.payments.index') }}" class="text-sm font-medium text-brand hover:underline">All payments</a>
                </div>
                <ul class="mt-4 divide-y divide-line text-sm">
                    @forelse ($recentPayments as $pass)
                        <li class="flex items-center justify-between gap-4 py-2.5">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-ink">{{ $pass->user?->name ?? 'Deleted account' }}</span>
                                <span class="block truncate text-xs text-muted">{{ $pass->plan_name }} · {{ $pass->paid_at?->diffForHumans(short: true) }}</span>
                            </span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ $pass->currency }} {{ number_format($pass->amount / 100, 2) }}</span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-muted">{{ $revenue['enabled'] ? 'No payments yet.' : 'Payments are switched off.' }}</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-layouts.admin>
