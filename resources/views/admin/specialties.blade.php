<x-layouts.admin title="Specialties" heading="Specialties">
    <div class="space-y-5">
        <x-admin.table-card title="Specialties" :count="$specialties->count()">
            <x-slot:actions>
                <button type="button" class="btn-primary btn-sm" data-modal-open="add-specialty">Add a specialty</button>
            </x-slot:actions>
            <x-slot:toolbar>
                <input type="search" data-table-search placeholder="Search specialties" class="field max-w-xs" aria-label="Search specialties">
                <select data-table-filter="kind" class="field sm:w-auto" aria-label="Type">
                    <option value="">All types</option>
                    <option value="clinical">Doctors</option>
                    <option value="diagnostic">Tests and scans</option>
                </select>
                <select data-table-filter="status" class="field sm:w-auto" aria-label="On the website">
                    <option value="">Shown and hidden</option>
                    <option value="shown">Shown</option>
                    <option value="hidden">Hidden</option>
                </select>
            </x-slot:toolbar>
            <table class="admin-table min-w-[820px]">
                <thead>
                    <tr><th>Name</th><th>Type</th><th>Services</th><th>Offered by</th><th>On the website</th><th>On the home page</th><th class="text-right"></th></tr>
                </thead>
                <tbody>
                    @foreach ($specialties as $sp)
                        <tr data-row data-kind="{{ $sp->kind }}" data-status="{{ $sp->is_active ? 'shown' : 'hidden' }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($sp->image)<img src="/storage/{{ $sp->image['path'] }}-640.webp" alt="" class="size-9 shrink-0 rounded-md object-cover">@endif
                                    <div>
                                        <p class="font-medium text-ink">{{ $sp->name }}</p>
                                        @if ($sp->description)<p class="max-w-xs truncate text-xs text-muted">{{ $sp->description }}</p>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">{{ $sp->kind === 'clinical' ? 'Doctors' : 'Tests and scans' }}</td>
                            <td><a href="{{ route('admin.catalog.index', ['q' => $sp->name]) }}" class="text-brand hover:underline">{{ $sp->catalog_services_count }}</a></td>
                            <td class="whitespace-nowrap">{{ $sp->services_count }} {{ Str::plural('listing', $sp->services_count) }}</td>
                            <td>@if ($sp->is_active)<span class="badge-confirmed">Shown</span>@else<span class="badge-pending">Hidden</span>@endif</td>
                            <td>@if ($sp->kind === 'clinical')@if ($sp->show_on_home)<span class="badge-confirmed">Yes</span>@else<span class="badge-pending">No</span>@endif @endif</td>
                            <td class="text-right"><button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-specialty-{{ $sp->id }}">Edit</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.table-card>
    </div>

    {{-- Add a specialty --}}
    <x-admin.modal id="add-specialty" title="Add a specialty">
        <form id="add-specialty-form" method="POST" action="{{ route('admin.specialties.store') }}" class="grid gap-4">
            @csrf
            <input type="hidden" name="_modal" value="add-specialty">
            <input type="hidden" name="is_active" value="1">
            <x-admin.field label="What’s it called?" name="name" modal="add-specialty" required placeholder="For example, Neurologist" />
            <x-admin.field label="Is it a kind of doctor or a kind of test?" name="kind" type="select" modal="add-specialty">
                <option value="clinical" @selected(old('kind') !== 'diagnostic')>A kind of doctor</option>
                <option value="diagnostic" @selected(old('kind') === 'diagnostic')>A kind of test or scan</option>
            </x-admin.field>
            <x-admin.field label="Describe it in a sentence (optional)" name="description" type="textarea" modal="add-specialty" placeholder="What they look after, in everyday words" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="add-specialty-form" class="btn-primary btn-sm">Add specialty</button>
        </x-slot:footer>
    </x-admin.modal>

    {{-- Edit each specialty --}}
    @foreach ($specialties as $sp)
        @php($m = 'edit-specialty-'.$sp->id)
        <x-admin.modal :id="$m" :title="'Edit '.$sp->name">
            <form id="{{ $m }}-form" method="POST" action="{{ route('admin.specialties.update', $sp) }}" enctype="multipart/form-data" class="grid gap-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="{{ $m }}">
                <x-admin.field label="Name" name="name" :value="$sp->name" :modal="$m" required />
                <x-admin.field label="Description" name="description" type="textarea" :value="$sp->description" :modal="$m" placeholder="What they look after, in everyday words" />

                <div class="space-y-2">
                    <label class="flex items-center justify-between gap-4 rounded-md bg-surface-2 px-4 py-3 text-sm">
                        Show it on the website
                        <input type="checkbox" name="is_active" value="1" @checked($sp->is_active)>
                    </label>
                    @if ($sp->kind === 'clinical')
                        <label class="flex items-center justify-between gap-4 rounded-md bg-surface-2 px-4 py-3 text-sm">
                            Feature it on the home page
                            <input type="checkbox" name="show_on_home" value="1" @checked($sp->show_on_home)>
                        </label>
                    @endif
                </div>

                <div class="rounded-md border border-line p-4">
                    <p class="text-sm font-medium text-ink">Photo for its search results</p>
                    <div class="mt-3 flex items-start gap-4">
                        @if ($sp->image)<img src="/storage/{{ $sp->image['path'] }}-640.webp" alt="" class="size-16 shrink-0 rounded-md object-cover">@endif
                        <x-file-upload name="image" accept="image/jpeg,image/png,image/webp" label="Browse a photo to upload" hint="JPG, PNG or WebP" class="min-w-0 flex-1" />
                    </div>
                    <input type="hidden" name="image_alt" value="{{ $sp->image['alt'] ?? '' }}">
                    @if ($sp->image)
                        <label class="mt-3 flex items-center justify-between gap-4 text-sm">Remove this photo <input type="checkbox" name="remove_image" value="1"></label>
                    @endif
                </div>
            </form>
            <x-slot:footer>
                @if ($sp->services_count === 0 && auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.specialties.destroy', $sp) }}" class="mr-auto" onsubmit="return confirm('Delete {{ addslashes($sp->name) }}?')">@csrf @method('DELETE')<button class="btn-ghost !text-danger">Delete</button></form>
                @endif
                <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                <button form="{{ $m }}-form" class="btn-primary">Save changes</button>
            </x-slot:footer>
        </x-admin.modal>
    @endforeach
</x-layouts.admin>
