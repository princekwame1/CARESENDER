<x-layouts.app title="Sign in">
    <div class="container-page grid min-h-[70vh] place-items-center py-12">
        <form method="POST" action="{{ route('login') }}" class="card w-full max-w-md space-y-6 p-8 sm:p-10">
            @csrf
            <div class="flex flex-col items-center text-center">
                <img src="/images/brand/favicon.png" alt="" width="64" height="64" class="size-16">
                <h1 class="mt-5 text-2xl font-bold">Sign in</h1>
                <p class="mt-1 text-sm text-muted">Members and the CareSenda team</p>
            </div>
            <label class="block"><span class="label">Username or email</span>
                <input name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" autocapitalize="none" class="field">
            </label>
            <label class="block"><span class="label">Password</span>
                <input name="password" type="password" required autocomplete="current-password" class="field">
            </label>
            @error('login')<p class="text-sm text-danger">{{ $message }}</p>@enderror
            <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="remember" class="accent-[var(--brand)]"> Keep me signed in</label>
            <button class="btn-primary w-full">Sign in</button>
            <p class="text-center text-sm text-muted">New here? <a href="{{ route('register') }}" class="font-semibold text-brand underline">Create an account</a></p>
        </form>
    </div>
</x-layouts.app>
