<x-layouts.app title="Page not found">
    <div class="container-page grid min-h-[60vh] place-items-center py-16">
        <div class="max-w-lg text-center">
            <p class="section-label">Error 404</p>
            <h1 class="mt-2 text-[28px] font-bold sm:text-4xl">We couldn’t find that page</h1>
            <p class="mt-4">The link may be old, or the address may have a typo. Here are some places to start instead.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="btn-primary"><x-icon name="home" />Home</a>
                <a href="{{ route('search') }}" class="btn-secondary"><x-icon name="search" />Browse care</a>
                <a href="{{ route('contact') }}" class="btn-secondary"><x-icon name="chat" />Contact us</a>
            </div>
            <p class="mt-8 text-sm text-muted">Looking for the team dashboard? <a href="{{ route('admin.dashboard') }}" class="font-medium text-brand hover:underline">Go to team sign-in</a></p>
        </div>
    </div>
</x-layouts.app>
