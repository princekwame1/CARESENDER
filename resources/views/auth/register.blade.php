<x-layouts.app title="Create an account">
    <div class="container-page grid min-h-[70vh] place-items-center py-12">
        <form method="POST" action="{{ route('register') }}" class="card w-full max-w-2xl space-y-5 p-8 sm:p-10">
            @csrf
            <div class="flex flex-col items-center text-center">
                <img src="/images/brand/icon-128.webp" alt="" width="64" height="64" class="size-16">
                <h1 class="mt-5 text-2xl font-bold">Create an account</h1>
                <p class="mt-1 text-sm text-muted">Use it on this website and in the CareSenda app.</p>
            </div>
            <x-social-sign-in class="sm:grid-cols-2" />
            <div class="grid gap-5 sm:grid-cols-2">
            <label class="block"><span class="label">Your name</span>
                <input name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" maxlength="120" class="field">
                @error('name')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror
            </label>
            <label class="block"><span class="label">Email</span>
                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autocapitalize="none" maxlength="190" class="field">
                @error('email')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror
            </label>
            <div class="sm:col-span-2">
                <span class="label" id="phone-label">Phone (optional)</span>
                <div class="flex gap-2">
                    <div class="w-44 shrink-0 sm:w-56">
                        <select name="phone_country" class="field" aria-label="Country code">
                            @foreach (\App\Support\PhoneNumber::options() as $code => $label)
                                <option value="{{ $code }}" @selected(old('phone_country', \App\Support\PhoneNumber::DEFAULT_COUNTRY) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel-national" maxlength="30" class="field min-w-0 flex-1" placeholder="24 000 0000" aria-labelledby="phone-label">
                </div>
                @error('phone')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror
                @error('phone_country')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror
            </div>
            <label class="block"><span class="label">Password</span>
                <input name="password" type="password" required autocomplete="new-password" minlength="8" class="field">
                <span class="mt-1 block text-xs text-muted">At least 8 characters.</span>
                @error('password')<span class="mt-1 block text-xs text-danger">{{ $message }}</span>@enderror
            </label>
            <label class="block"><span class="label">Type the password again</span>
                <input name="password_confirmation" type="password" required autocomplete="new-password" minlength="8" class="field">
            </label>
            </div>
            <button class="btn-primary w-full justify-center sm:mx-auto sm:flex sm:w-auto sm:min-w-64">Create account</button>
            <p class="text-center text-sm text-muted">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand underline">Sign in</a></p>
        </form>
    </div>
</x-layouts.app>
