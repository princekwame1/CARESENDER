<x-layouts.admin title="Facilities">
    <div>

        <x-admin.table-card title="Facilities" :count="number_format($facilities->total())">
            <x-slot:actions>
                <button type="button" class="btn-primary btn-sm" data-modal-open="add-facility">Add a facility</button>
            </x-slot:actions>
            <x-slot:toolbar>
                <form method="GET" data-autosubmit class="flex flex-1 flex-wrap items-center gap-3">
                    <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name, area or type" class="field max-w-xs" aria-label="Search facilities">
                    <select name="city" class="field sm:w-auto" aria-label="City"><option value="">All cities</option>@foreach (array_keys(config('caresenda.cities')) as $c)<option @selected(($filters['city'] ?? null) === $c)>{{ $c }}</option>@endforeach</select>
                    <select name="status" class="field sm:w-auto" aria-label="Status">
                        <option value="">Any status</option>
                        <option value="unverified" @selected(($filters['status'] ?? null) === 'unverified')>Not checked yet</option>
                        <option value="verified" @selected(($filters['status'] ?? null) === 'verified')>Checked</option>
                        <option value="no-phone" @selected(($filters['status'] ?? null) === 'no-phone')>Missing a phone number</option>
                        <option value="active" @selected(($filters['status'] ?? null) === 'active')>Shown on the website</option>
                        <option value="hidden" @selected(($filters['status'] ?? null) === 'hidden')>Hidden from the website</option>
                    </select>
                    <button class="btn-secondary">Search</button>
                    @if (array_filter($filters))<a href="{{ route('admin.facilities.index') }}" class="btn-ghost">Clear</a>@endif
                </form>
            </x-slot:toolbar>
            <table class="admin-table min-w-[760px]">
                <thead>
                    <tr><th>Facility</th><th>Phone</th><th>Services</th><th>Map</th><th>Last checked</th><th>On the website</th><th class="text-right"></th></tr>
                </thead>
                <tbody>
                    @forelse ($facilities as $f)
                        <tr>
                            <td>
                                <a href="{{ route('admin.facilities.edit', $f) }}" class="font-semibold text-ink hover:text-brand">{{ $f->name }}</a>
                                <p class="text-xs text-muted">{{ collect([$f->facility_type, $f->area ?: $f->city])->filter()->implode(' · ') }}</p>
                            </td>
                            <td class="whitespace-nowrap">@if ($f->phone)<a href="tel:{{ $f->phone }}" class="font-medium text-brand hover:underline">{{ $f->phone }}</a>@else<span class="text-muted">No phone</span>@endif</td>
                            <td class="tabular-nums">{{ $f->services_count }}</td>
                            <td class="whitespace-nowrap text-xs">{{ ['exact' => 'Exact spot', 'area' => 'Neighbourhood', 'city' => 'City only'][$f->geo_precision] ?? '' }}</td>
                            <td class="whitespace-nowrap">@if ($f->verified_at)<span class="badge-verified">{{ $f->verified_at->diffForHumans() }}</span>@else<span class="badge-pending">Not yet</span>@endif</td>
                            <td>@if ($f->is_active)<span class="badge-confirmed">Shown</span>@else<span class="badge bg-danger-soft text-danger">Hidden</span>@endif</td>
                            <td class="text-right"><a href="{{ route('admin.facilities.edit', $f) }}" class="btn-secondary !px-3 !py-1.5 text-xs">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-muted">No facilities match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-admin.table-card>
        <div class="mt-8">{{ $facilities->links() }}</div>
    </div>

    <x-admin.modal id="add-facility" title="Add a facility" size="xl" :data-open-on-load="request()->boolean('add') ? 'true' : null">
        <form id="add-facility-form" method="POST" action="{{ route('admin.facilities.store') }}">
            @csrf
            <input type="hidden" name="_modal" value="add-facility">
            @include('admin.facilities._details', ['facility' => null, 'modal' => 'add-facility'])
            <label class="mt-4 flex items-center justify-between gap-4 rounded-md bg-surface-2 px-4 py-3 text-sm">
                Show it on the website straight away
                <input type="checkbox" name="is_active" value="1" checked>
            </label>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="add-facility-form" class="btn-primary btn-sm">Add facility</button>
        </x-slot:footer>
    </x-admin.modal>
</x-layouts.admin>
