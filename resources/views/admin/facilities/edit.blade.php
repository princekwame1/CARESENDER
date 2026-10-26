@php
    use App\Services\SearchFilters;
    // Specialist times as "Day" => [start, end]; days recorded without times stay ticked.
    $slotMap = function ($service) {
        $map = [];
        foreach ($service->days ?? [] as $d) {
            $map[$d] = ['start' => '', 'end' => ''];
        }
        foreach ($service->slots ?? [] as $slot) {
            $map[$slot['day']] = ['start' => $slot['start'], 'end' => $slot['end']];
        }

        return $map;
    };
    $time = fn ($t) => \App\Support\Schedule::time($t);
    $offered = $facility->services->pluck('catalog_service_id')->filter()->all();
        // One "Specialists" box for all doctors, then one box per test group.
        $available = fn ($g) => $g->catalogServices->reject(fn ($i) => in_array($i->id, $offered));
        $boxes = collect();
        $clinical = $catalogue->where('kind', 'clinical')->flatMap(fn ($g) => $available($g)->map(fn ($i) => [$i, $g]));
        if ($clinical->isNotEmpty()) {
            $boxes->push(['Specialists', $clinical]);
        }
        foreach ($catalogue->where('kind', 'diagnostic') as $g) {
            if ($available($g)->isNotEmpty()) {
                $boxes->push([$g->name, $available($g)->map(fn ($i) => [$i, $g])]);
            }
        }
@endphp
<x-layouts.admin :title="$facility->name" heading="Facility">
    <div class="space-y-6">
        <a href="{{ route('admin.facilities.index') }}" class="btn-ghost -ml-3 !px-3"><x-icon name="back" />All facilities</a>

        {{-- Summary --}}
        <section class="card flex flex-wrap items-start justify-between gap-5 p-6">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-bold">{{ $facility->name }}</h2>
                    @if ($facility->is_active)<span class="badge-confirmed">Shown on the website</span>@else<span class="badge bg-danger-soft text-danger">Hidden from the website</span>@endif
                </div>
                <p class="mt-1.5 text-sm text-muted">{{ collect([$facility->facility_type, $facility->address, $facility->area ?: $facility->city])->filter()->implode(' · ') }}</p>
                <p class="mt-1 text-sm text-muted">{{ $facility->verified_at ? 'We last checked this on '.$facility->verified_at->format('j F Y').'.' : 'Nobody has checked this facility yet.' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($facility->phone)<a href="tel:{{ $facility->phone }}" class="btn-secondary !py-2">Call them</a>@endif
                <form method="POST" action="{{ route('admin.facilities.update', $facility) }}">@csrf @method('PUT')<input type="hidden" name="mark_checked" value="1"><button class="btn-secondary !py-2">I checked this today</button></form>
                <a href="{{ route('facilities.show', $facility) }}" target="_blank" class="btn-secondary !py-2">See it on the website</a>
                <form method="POST" action="{{ route('admin.facilities.toggle', $facility) }}">@csrf @method('PATCH')<button class="btn-secondary !py-2">{{ $facility->is_active ? 'Hide it' : 'Show it' }}</button></form>
                <form method="POST" action="{{ route('admin.facilities.destroy', $facility) }}" onsubmit="return confirm('Delete {{ addslashes($facility->name) }} and everything listed under it? This can’t be undone.')">@csrf @method('DELETE')<button class="btn-ghost !py-2 !text-danger">Delete</button></form>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            {{-- Details --}}
            <section class="card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold">Details</h3>
                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-details">Edit</button>
                </div>
                <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Phone' => $facility->phone,
                        'WhatsApp' => $facility->whatsapp,
                        'Email' => $facility->email,
                        'Run by' => $facility->ownership,
                        'District' => $facility->district,
                        'On the map' => ['exact' => 'Exact spot', 'area' => 'Middle of '.($facility->area ?: 'the neighbourhood'), 'city' => 'Somewhere in '.$facility->city][$facility->geo_precision] ?? null,
                        'Tests offered' => collect($facility->diagnostic_categories ?? [])->map(fn ($c) => config("caresenda.diagnostic_categories.$c"))->implode(', '),
                        'Out of service' => $facility->issue,
                    ] as $label => $value)
                        <div><dt class="text-xs text-muted">{{ $label }}</dt><dd class="text-ink">{{ $value ?: 'Not added' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            {{-- Opening hours --}}
            <section class="card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold">Opening hours</h3>
                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-hours">Edit</button>
                </div>
                <ul class="mt-4 divide-y divide-line text-sm">
                    @foreach (SearchFilters::DAYS as $day)
                        @php($h = $facility->opening_hours[$day] ?? null)
                        <li class="flex justify-between py-2"><span>{{ $day }}</span><span @class(['text-ink' => $h, 'text-muted' => ! $h])>{{ $h ? $time($h['open']).' to '.$time($h['close']) : 'Closed or not known' }}</span></li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Specialists --}}
        <section>
            @if ($specialists->isEmpty())
                <div class="card">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                        <h2 class="text-lg font-semibold">Specialists</h2>
                        <button type="button" class="btn-primary btn-sm" data-modal-open="add-services">Add services</button>
                    </div>
                    <p class="p-6 text-sm text-muted">No specialists yet.</p>
                </div>
            @else
                <x-admin.table-card title="Specialists" :count="$specialists->count()">
                    <x-slot:actions>
                        <button type="button" class="btn-primary btn-sm" data-modal-open="add-services">Add services</button>
                    </x-slot:actions>
                    <x-slot:toolbar>
                        <input type="search" data-table-search placeholder="Search specialists" class="field max-w-xs" aria-label="Search specialists">
                        <select data-table-filter="available" class="field sm:w-auto" aria-label="Available">
                            <option value="">Available or not</option>
                            <option value="yes">Available</option>
                            <option value="no">Not available</option>
                        </select>
                    </x-slot:toolbar>
                    <table class="admin-table min-w-[720px]">
                        <thead><tr><th>Specialist</th><th>Doctor</th><th>When</th><th>Fee</th><th>Available</th><th class="text-right"></th></tr></thead>
                        <tbody>
                            @foreach ($specialists as $s)
                                <tr data-row data-available="{{ $s->available ? 'yes' : 'no' }}">
                                    <td class="font-medium text-ink">{{ $s->service }}</td>
                                    <td>{{ $s->specialist_name ?: 'Not known' }}</td>
                                    <td>{{ $s->schedule ?: 'Not known' }}</td>
                                    <td class="whitespace-nowrap">{{ $s->price ?: 'Not known' }}</td>
                                    <td>@if ($s->available)<span class="badge-confirmed">Yes</span>@else<span class="badge-pending">No</span>@endif</td>
                                    <td class="text-right"><button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-service-{{ $s->id }}">Edit</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-admin.table-card>
            @endif
        </section>

        {{-- Tests --}}
        <section>
            @if ($tests->isEmpty())
                <div class="card">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                        <h2 class="text-lg font-semibold">Tests and scans</h2>
                        <button type="button" class="btn-primary btn-sm" data-modal-open="add-services">Add services</button>
                    </div>
                    <p class="p-6 text-sm text-muted">No tests yet.</p>
                </div>
            @else
                <x-admin.table-card title="Tests and scans" :count="$tests->count()">
                    <x-slot:actions>
                        <button type="button" class="btn-primary btn-sm" data-modal-open="add-services">Add services</button>
                    </x-slot:actions>
                    <x-slot:toolbar>
                        <input type="search" data-table-search placeholder="Search tests" class="field max-w-xs" aria-label="Search tests">
                        <select data-table-filter="group" class="field sm:w-auto" aria-label="Group">
                            <option value="">All groups</option>
                            @foreach ($tests->pluck('lab_category')->filter()->unique()->sort() as $category)<option>{{ $category }}</option>@endforeach
                        </select>
                        <select data-table-filter="available" class="field sm:w-auto" aria-label="Available">
                            <option value="">Available or not</option>
                            <option value="yes">Available</option>
                            <option value="no">Not available</option>
                        </select>
                    </x-slot:toolbar>
                    <table class="admin-table min-w-[620px]">
                        <thead><tr><th>Test</th><th>Group</th><th>Price</th><th>Available</th><th class="text-right"></th></tr></thead>
                        <tbody>
                            @foreach ($tests as $t)
                                <tr data-row data-group="{{ $t->lab_category }}" data-available="{{ $t->available ? 'yes' : 'no' }}">
                                    <td class="font-medium text-ink">{{ $t->service }}</td>
                                    <td>{{ $t->lab_category }}</td>
                                    <td class="whitespace-nowrap">{{ $t->price ?: 'Not known' }}</td>
                                    <td>@if ($t->available)<span class="badge-confirmed">Yes</span>@else<span class="badge-pending">No</span>@endif</td>
                                    <td class="text-right"><button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-service-{{ $t->id }}">Edit</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-admin.table-card>
            @endif
        </section>
    </div>

    {{-- ===== Modals ===== --}}

    <x-admin.modal id="edit-details" title="Edit details" size="xl">
        <form id="edit-details-form" method="POST" action="{{ route('admin.facilities.update', $facility) }}">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="edit-details">
            @include('admin.facilities._details', ['modal' => 'edit-details'])
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="edit-details-form" class="btn-primary">Save details</button>
        </x-slot:footer>
    </x-admin.modal>

    <x-admin.modal id="edit-hours" title="Opening hours" size="lg">
        <form id="edit-hours-form" method="POST" action="{{ route('admin.facilities.update', $facility) }}">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="edit-hours">
            <x-admin.week-times name="opening_hours" from="open" to="close" :value="old('_modal') === 'edit-hours' ? old('opening_hours', []) : ($facility->opening_hours ?? [])" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="edit-hours-form" class="btn-primary">Save hours</button>
        </x-slot:footer>
    </x-admin.modal>

    @foreach ($specialists as $s)
        @php($m = 'edit-service-'.$s->id)
        <x-admin.modal :id="$m" :title="$s->service" size="lg">
            <form id="{{ $m }}-form" method="POST" action="{{ route('admin.facilities.update', $facility) }}" class="grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="{{ $m }}">
                <x-admin.field label="Doctor’s name" name="services[{{ $s->id }}][specialist_name]" :value="$s->specialist_name" :modal="$m" />
                <x-admin.field label="Doctor is" name="services[{{ $s->id }}][gender]" type="select" :modal="$m">
                    <option value="">Not known</option>
                    @foreach (['Male' => 'A man', 'Female' => 'A woman'] as $g => $gl)<option value="{{ $g }}" @selected($s->gender === $g)>{{ $gl }}</option>@endforeach
                </x-admin.field>
                <x-admin.field label="Consultation fee" name="services[{{ $s->id }}][price]" :value="$s->price" :modal="$m" placeholder="For example, GHS 250" />
                <label class="flex items-center justify-between gap-4 self-end rounded-md bg-surface-2 px-4 py-3 text-sm">
                    Seeing patients at the moment
                    <input type="checkbox" name="services[{{ $s->id }}][available]" value="1" @checked($s->available)>
                </label>
                <div class="sm:col-span-2">
                    <span class="label">When do they see patients?</span>
                    <x-admin.week-times :name="'services['.$s->id.'][slots]'" :value="$slotMap($s)" />
                </div>
            </form>
            <x-slot:footer>
                <form method="POST" action="{{ route('admin.facilities.services.destroy', [$facility, $s]) }}" class="mr-auto" onsubmit="return confirm('Remove {{ addslashes($s->service) }} from this facility?')">@csrf @method('DELETE')<button class="btn-ghost !text-danger">Remove from this facility</button></form>
                <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                <button form="{{ $m }}-form" class="btn-primary">Save</button>
            </x-slot:footer>
        </x-admin.modal>
    @endforeach

    @foreach ($tests as $t)
        @php($m = 'edit-service-'.$t->id)
        <x-admin.modal :id="$m" :title="$t->service" size="sm">
            <form id="{{ $m }}-form" method="POST" action="{{ route('admin.facilities.update', $facility) }}" class="grid gap-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="{{ $m }}">
                <x-admin.field label="Price" name="services[{{ $t->id }}][price]" :value="$t->price" :modal="$m" placeholder="For example, GHS 80" />
                <label class="flex items-center justify-between gap-4 rounded-md bg-surface-2 px-4 py-3 text-sm">
                    They can do this test at the moment
                    <input type="checkbox" name="services[{{ $t->id }}][available]" value="1" @checked($t->available)>
                </label>
            </form>
            <x-slot:footer>
                <form method="POST" action="{{ route('admin.facilities.services.destroy', [$facility, $t]) }}" class="mr-auto" onsubmit="return confirm('Remove {{ addslashes($t->service) }} from this facility?')">@csrf @method('DELETE')<button class="btn-ghost !text-danger">Remove</button></form>
                <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                <button form="{{ $m }}-form" class="btn-primary">Save</button>
            </x-slot:footer>
        </x-admin.modal>
    @endforeach

    {{-- Add services from the central list --}}
    <x-admin.modal id="add-services" title="Add services" size="xl">
        <form id="add-services-form" method="POST" action="{{ route('admin.facilities.services.store', $facility) }}" data-service-picker>
            @csrf
            <input type="hidden" name="_modal" value="add-services">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <input type="search" data-picker-search placeholder="Search services" class="field max-w-xs" aria-label="Search services">
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($boxes as [$title, $items])
                    <fieldset data-picker-group class="rounded-md border border-line p-4">
                        <legend class="px-1 text-sm font-semibold text-ink">{{ $title }}</legend>
                        <div class="mt-1 space-y-1">
                            @foreach ($items as [$item, $group])
                                <label data-picker-item class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 text-sm transition hover:bg-surface-2">
                                    <input type="checkbox" name="catalog_ids[]" value="{{ $item->id }}">
                                    <span>{{ $item->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <p class="text-sm">This facility already offers everything on the list.</p>
                @endforelse
            </div>
        </form>
        <x-slot:footer>
            <a href="{{ route('admin.catalog.index') }}" class="mr-auto text-sm text-brand hover:underline">Something missing? Add it to the services list</a>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="add-services-form" class="btn-primary btn-sm">Add <span data-picker-count>selected</span> services</button>
        </x-slot:footer>
    </x-admin.modal>
</x-layouts.admin>
