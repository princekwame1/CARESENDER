@php
    $groupOptions = function ($selected = null) use ($allGroups) {
        $html = '';
        foreach ($allGroups->groupBy('kind') as $kind => $list) {
            $html .= '<optgroup label="'.($kind === 'clinical' ? 'Specialists' : 'Tests and scans').'">';
            foreach ($list as $g) {
                $html .= '<option value="'.$g->id.'"'.((string) $selected === (string) $g->id ? ' selected' : '').'>'.e($g->name).'</option>';
            }
            $html .= '</optgroup>';
        }

        return new \Illuminate\Support\HtmlString($html);
    };
@endphp
<x-layouts.admin title="Services" heading="Services">
    <div class="space-y-5">
        <x-admin.table-card title="Services" :count="$groups->sum(fn ($g) => $g->catalogServices->count())">
            <x-slot:actions>
                <button type="button" class="btn-primary btn-sm" data-modal-open="add-service">Add a service</button>
            </x-slot:actions>
            <x-slot:toolbar>
                <input type="search" data-table-search value="{{ $q }}" placeholder="Search services" class="field max-w-xs" aria-label="Search services">
                <select data-table-filter="group" class="field sm:w-auto" aria-label="Group">
                    <option value="">All groups</option>
                    {{ $groupOptions() }}
                </select>
                <select data-table-filter="type" class="field sm:w-auto" aria-label="Type">
                    <option value="">All types</option>
                    <option value="specialist">Specialist visits</option>
                    <option value="test">Tests and scans</option>
                </select>
                <select data-table-filter="status" class="field sm:w-auto" aria-label="Status">
                    <option value="">Any status</option>
                    <option value="on">In use</option>
                    <option value="off">Switched off</option>
                </select>
            </x-slot:toolbar>
            <table class="admin-table min-w-[760px]">
                <thead>
                    <tr><th>Service</th><th>Type</th><th>Offered by</th><th>Status</th><th class="text-right"></th></tr>
                </thead>
                @foreach ($groups as $group)
                    <tbody>
                        <tr class="group-row">
                            <td colspan="5">
                                {{ $group->name }}
                                <span class="ml-1 text-xs font-normal text-muted">{{ $group->catalogServices->count() }} {{ Str::plural('service', $group->catalogServices->count()) }}</span>
                                @unless ($group->is_active)<span class="badge bg-danger-soft text-danger ml-1 !py-0">Hidden</span>@endunless
                            </td>
                        </tr>
                        @forelse ($group->catalogServices as $item)
                            <tr data-row data-search="{{ $group->name }}" data-group="{{ $group->id }}" data-type="{{ $item->type === 'specialist' ? 'specialist' : 'test' }}" data-status="{{ $item->is_active ? 'on' : 'off' }}">
                                <td class="font-medium text-ink">{{ $item->name }}</td>
                                <td class="whitespace-nowrap">{{ $item->type === 'specialist' ? 'Specialist visit' : 'Test or scan' }}</td>
                                <td class="whitespace-nowrap">{{ $item->listings_count }} {{ Str::plural('facility', $item->listings_count) }}</td>
                                <td>@if ($item->is_active)<span class="badge-confirmed">In use</span>@else<span class="badge-pending">Switched off</span>@endif</td>
                                <td class="whitespace-nowrap text-right">
                                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-service-{{ $item->id }}">Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No services here yet.</td></tr>
                        @endforelse
                    </tbody>
                @endforeach
            </table>
        </x-admin.table-card>
    </div>

    {{-- Add a service --}}
    <x-admin.modal id="add-service" title="Add a service">
        <form id="add-service-form" method="POST" action="{{ route('admin.catalog.store') }}" class="grid gap-4">
            @csrf
            <input type="hidden" name="_modal" value="add-service">
            <x-admin.field label="What’s it called?" name="name" modal="add-service" required placeholder="For example, Pap smear" />
            <x-admin.field label="Which group does it belong to?" name="specialty_id" type="select" modal="add-service" required>
                <option value="">Choose a group</option>
                {{ $groupOptions(old('_modal') === 'add-service' ? old('specialty_id') : null) }}
            </x-admin.field>
            <x-admin.field label="Is it a test or a specialist visit?" name="type" type="select" modal="add-service">
                <option value="test" @selected(old('type') !== 'specialist')>A test or scan</option>
                <option value="specialist" @selected(old('type') === 'specialist')>A visit to a specialist</option>
            </x-admin.field>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="add-service-form" class="btn-primary btn-sm">Add service</button>
        </x-slot:footer>
    </x-admin.modal>

    {{-- Edit each service --}}
    @foreach ($groups as $group)
        @foreach ($group->catalogServices as $item)
            @php($m = 'edit-service-'.$item->id)
            <x-admin.modal :id="$m" :title="'Edit '.$item->name">
                <form id="{{ $m }}-form" method="POST" action="{{ route('admin.catalog.update', $item) }}" class="grid gap-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_modal" value="{{ $m }}">
                    <x-admin.field label="Name" name="name" :value="$item->name" :modal="$m" required />
                    <x-admin.field label="Group" name="specialty_id" type="select" :modal="$m">
                        {{ $groupOptions(old('_modal') === $m ? old('specialty_id') : $item->specialty_id) }}
                    </x-admin.field>
                    <label class="flex items-center justify-between gap-4 rounded-md bg-surface-2 px-4 py-3 text-sm">
                        Facilities can add this service
                        <input type="checkbox" name="is_active" value="1" @checked($item->is_active)>
                    </label>
                    <p class="text-sm text-muted">{{ $item->listings_count ? $item->listings_count.' '.Str::plural('facility', $item->listings_count).' offer this, so renaming it changes their listings too.' : 'No facility offers this yet.' }}</p>
                </form>
                <x-slot:footer>
                    @if ($item->listings_count === 0 && auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admin.catalog.destroy', $item) }}" class="mr-auto" onsubmit="return confirm('Delete {{ addslashes($item->name) }}?')">@csrf @method('DELETE')<button class="btn-ghost !text-danger">Delete</button></form>
                    @endif
                    <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                    <button form="{{ $m }}-form" class="btn-primary">Save changes</button>
                </x-slot:footer>
            </x-admin.modal>
        @endforeach
    @endforeach
</x-layouts.admin>
