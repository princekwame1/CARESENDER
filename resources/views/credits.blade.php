<x-layouts.app title="Photo credits">
    <div class="container-page max-w-7xl py-12">
        <x-section-heading label="Credits" title="Photo credits">
            We use openly licensed photos. Thank you to the photographers who share their work. Photos uploaded by the CareSenda team are our own.
        </x-section-heading>
        <div data-stagger class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (config('caresenda.photos') as $slug => $p)
                <div data-reveal class="card overflow-hidden">
                    <x-photo :slug="$slug" :alt="$p['title']" :credit="false" class="aspect-[4/3]" sizes="(min-width: 1024px) 33vw, 100vw" />
                    <div class="p-5 text-sm">
                        <p class="font-semibold text-ink">{{ $p['title'] }}</p>
                        <p class="mt-1">{{ $p['creator'] ?? 'Unknown photographer' }} ·
                            @if ($p['license_url'])<a class="text-brand hover:underline" href="{{ $p['license_url'] }}" target="_blank" rel="noopener license">{{ $p['license'] }}</a>@else{{ $p['license'] }}@endif
                            @if ($p['source']) · <a class="text-brand hover:underline" href="{{ $p['source'] }}" target="_blank" rel="noopener">Source</a>@endif
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
