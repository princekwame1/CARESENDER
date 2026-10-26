<x-layouts.admin title="Profile" heading="Your profile">
    <div class="grid gap-6 xl:grid-cols-[22rem_1fr]">
        {{-- Photo and name --}}
        <section class="card flex flex-col items-center p-8 text-center">
            <form method="POST" action="{{ route('admin.profile.avatar') }}" enctype="multipart/form-data" data-avatar-form>
                @csrf
                {{-- Clicking the photo opens the file picker; choosing a file previews it and uploads straight away. --}}
                <label class="group relative block cursor-pointer" title="Change your photo">
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" data-avatar-input>
                    <span data-avatar-preview class="block">
                        <x-admin.avatar :user="$user" size="size-28" class="text-4xl" />
                    </span>
                    <span class="absolute inset-0 grid place-items-center rounded-full bg-black/45 text-xs font-semibold text-white opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">Change</span>
                    <span data-avatar-busy class="absolute inset-0 hidden place-items-center rounded-full bg-black/45"><span class="size-7 animate-spin rounded-full border-[3px] border-white/40 border-t-white"></span></span>
                </label>
                <noscript><button class="btn-primary mt-3 !py-1.5 text-xs">Upload</button></noscript>
            </form>
            <h2 class="mt-4 text-xl font-semibold">{{ $user->name }}</h2>
            <p class="text-sm text-muted">{{ '@'.$user->username }}</p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <button type="button" class="btn-secondary !py-2" onclick="document.querySelector('[data-avatar-input]').click()">{{ $user->avatar_path ? 'Change photo' : 'Add a photo' }}</button>
                @if ($user->avatar_path)
                    <form method="POST" action="{{ route('admin.profile.avatar') }}">@csrf<input type="hidden" name="remove" value="1"><button class="btn-ghost !py-2 !text-danger">Remove photo</button></form>
                @endif
            </div>
        </section>

        <div class="space-y-6">
            <section class="card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold">Your details</h3>
                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="edit-account">Edit</button>
                </div>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-muted">Name</dt><dd class="text-ink">{{ $user->name }}</dd></div>
                    <div><dt class="text-xs text-muted">Username</dt><dd class="text-ink">{{ $user->username }}</dd></div>
                    <div><dt class="text-xs text-muted">Email</dt><dd class="text-ink">{{ $user->email }}</dd></div>
                </dl>
            </section>

            <section class="card flex flex-wrap items-center justify-between gap-4 p-6">
                <div>
                    <h3 class="text-lg font-semibold">Password</h3>
                </div>
                <button type="button" class="btn-secondary !py-2" data-modal-open="change-password">Change password</button>
            </section>
        </div>
    </div>

    <x-admin.modal id="edit-account" title="Your details">
        <form id="edit-account-form" method="POST" action="{{ route('admin.profile.update') }}" class="grid gap-4">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="edit-account">
            <x-admin.field label="Your name" name="name" :value="$user->name" modal="edit-account" required />
            <x-admin.field label="Username" name="username" :value="$user->username" modal="edit-account" required autocomplete="username" autocapitalize="none" />
            <x-admin.field label="Email address" name="email" type="email" :value="$user->email" modal="edit-account" required />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="edit-account-form" class="btn-primary">Save</button>
        </x-slot:footer>
    </x-admin.modal>

    <x-admin.modal id="change-password" title="Change your password" size="sm">
        <form id="change-password-form" method="POST" action="{{ route('admin.profile.password') }}" class="grid gap-4">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="change-password">
            <x-admin.field label="Current password" name="current_password" type="password" modal="change-password" required autocomplete="current-password" />
            <x-admin.field label="New password" name="password" type="password" modal="change-password" required autocomplete="new-password" placeholder="At least 8 letters and numbers" />
            <x-admin.field label="Type the new password again" name="password_confirmation" type="password" modal="change-password" required autocomplete="new-password" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
            <button form="change-password-form" class="btn-primary">Change password</button>
        </x-slot:footer>
    </x-admin.modal>
</x-layouts.admin>
