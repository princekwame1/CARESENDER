<x-layouts.admin title="Users" heading="Users">
    <div>
        <x-admin.table-card title="Users" :count="number_format($users->total())">
            <x-slot:actions>
                <button type="button" class="btn-primary btn-sm" data-modal-open="add-user">Add a user</button>
            </x-slot:actions>
            <x-slot:toolbar>
                <form method="GET" class="flex flex-1 flex-wrap items-center gap-3">
                    <input name="q" type="search" value="{{ $search }}" placeholder="Search by name, email or phone" class="field max-w-xs" aria-label="Search users">
                    <button class="btn-secondary">Search</button>
                    @if ($search)<a href="{{ route('admin.users.index') }}" class="btn-ghost">Clear</a>@endif
                </form>
            </x-slot:toolbar>
            <table class="admin-table min-w-[640px]">
                <thead>
                    <tr><th>Name</th><th>Phone</th><th>Joined</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="min-w-48">
                                <p class="font-medium text-ink">{{ $user->name }}</p>
                                <p class="text-xs text-muted">{{ $user->email }}</p>
                            </td>
                            <td class="whitespace-nowrap">@if ($user->phone)<a href="tel:{{ $user->phone }}" class="font-medium text-brand hover:underline">{{ $user->phone }}</a>@else<span class="text-muted">No phone</span>@endif</td>
                            <td class="whitespace-nowrap text-muted">{{ $user->created_at?->format('j M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-8 text-center text-sm text-muted">{{ $search ? 'No users match your search.' : 'No users yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-admin.table-card>
        <div class="mt-8">{{ $users->links() }}</div>
    </div>

    <x-admin.modal id="add-user" title="Add a user">
        <form id="add-user-form" method="POST" action="{{ route('admin.users.store') }}" class="grid gap-4">
            @csrf
            <input type="hidden" name="_modal" value="add-user">
            <x-admin.field label="Name" name="name" modal="add-user" required maxlength="120" />
            <x-admin.field label="Email" name="email" type="email" modal="add-user" required maxlength="190" autocapitalize="none" />
            <x-admin.field label="Phone (optional)" name="phone" type="tel" modal="add-user" maxlength="30" placeholder="024 000 0000" />
            <x-admin.field label="Password" name="password" type="password" modal="add-user" required minlength="8" autocomplete="new-password" />
            <p class="-mt-2 text-xs text-muted">Share the password with them so they can sign in. They start with the same free trial as signing up. To give someone dashboard access, use Team.</p>
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="add-user-form" class="btn-primary btn-sm">Create account</button>
        </x-slot:footer>
    </x-admin.modal>
</x-layouts.admin>
