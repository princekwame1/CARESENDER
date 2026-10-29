<x-layouts.admin title="Team" heading="Team">
    <div class="space-y-5">
        <x-admin.table-card title="Team" :count="$team->count()">
            <x-slot:actions>
                <button type="button" class="btn-primary btn-sm" data-modal-open="add-member">Add someone</button>
            </x-slot:actions>
            <table class="admin-table min-w-[720px]">
                <thead>
                    <tr><th>Name</th><th>Role</th><th>Can do</th><th class="text-right"></th></tr>
                </thead>
                <tbody>
                    @foreach ($team as $person)
                        <tr>
                            <td class="min-w-48">
                                <p class="font-medium text-ink">{{ $person->name }}@if ($person->is(auth()->user())) <span class="text-xs font-normal text-muted">(you)</span>@endif</p>
                                <p class="text-xs text-muted">{{ $person->username ?? $person->email }}</p>
                            </td>
                            <td><span @class([$person->isAdmin() ? 'badge-confirmed' : 'badge-pending'])>{{ $person->roleLabel() }}</span></td>
                            <td class="text-xs text-muted">{{ $person->isAdmin() ? 'Everything' : 'Facilities, services, specialties and messages' }}</td>
                            <td class="whitespace-nowrap text-right">
                                @unless ($person->is(auth()->user()))
                                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-member-{{ $person->id }}">Edit</button>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.table-card>
        <p class="text-sm text-muted">The secretariat keeps facilities and specialists up to date and answers messages. They can’t delete anything or see payments, plans, settings or page content.</p>
    </div>

    <x-admin.modal id="add-member" title="Add someone to the team">
        <form id="add-member-form" method="POST" action="{{ route('admin.team.store') }}" class="grid gap-4">
            @csrf
            <input type="hidden" name="_modal" value="add-member">
            <x-admin.field label="Name" name="name" modal="add-member" required maxlength="120" />
            <x-admin.field label="Email" name="email" type="email" modal="add-member" required maxlength="190" autocapitalize="none" />
            <x-admin.field label="Role" name="role" type="select" modal="add-member">
                @foreach (\App\Models\User::STAFF_ROLES as $role => $label)
                    <option value="{{ $role }}" @selected((old('role') ?? \App\Models\User::SECRETARIAT) === $role)>{{ $label }}</option>
                @endforeach
            </x-admin.field>
            <x-admin.field label="Password" name="password" type="password" modal="add-member" minlength="8" autocomplete="new-password" />
            <p class="-mt-2 text-xs text-muted">Needed for a new account. If this email already has a CareSenda account, leave it empty: they keep their own password.</p>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="add-member-form" class="btn-primary btn-sm">Add to team</button>
        </x-slot:footer>
    </x-admin.modal>

    @foreach ($team as $person)
        @continue($person->is(auth()->user()))
        @php($m = "edit-member-{$person->id}")
        <x-admin.modal :id="$m" :title="'Edit '.$person->name">
            <form id="{{ $m }}-form" method="POST" action="{{ route('admin.team.update', $person) }}" class="grid gap-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="{{ $m }}">
                <x-admin.field label="Role" name="role" type="select" :modal="$m">
                    @foreach (\App\Models\User::STAFF_ROLES as $role => $label)
                        <option value="{{ $role }}" @selected($person->role === $role)>{{ $label }}</option>
                    @endforeach
                </x-admin.field>
            </form>
            <x-slot:footer>
                <form method="POST" action="{{ route('admin.team.destroy', $person) }}" class="mr-auto" onsubmit="return confirm('Remove {{ addslashes($person->name) }} from the team? Their account stays, without dashboard access.')">@csrf @method('DELETE')<button class="btn-ghost !text-danger">Remove from team</button></form>
                <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                <button form="{{ $m }}-form" class="btn-primary btn-sm">Save</button>
            </x-slot:footer>
        </x-admin.modal>
    @endforeach
</x-layouts.admin>
