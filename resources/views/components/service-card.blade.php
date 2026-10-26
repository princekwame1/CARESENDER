@props(['service'])
@php
    $f = $service->facility;
    $open = app(\App\Services\AccessGate::class)->allows(request(), $service->facility_id, \App\Services\AccessGate::areaForService($service->type));
    $schedule = $open && $service->schedule && ! str_contains($service->schedule, 'pending') ? $service->schedule : null;
    $price = $open ? $service->price : null;
@endphp
<article data-reveal class="card group relative flex flex-col gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-glow">
    <div class="min-w-0">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-base font-semibold leading-snug">{{ $service->service }}</h3>
            <x-distance :km="$service->distanceKm" class="mt-0.5 shrink-0" />
        </div>
        <a href="{{ route('facilities.show', $f) }}" class="mt-0.5 block truncate text-sm text-brand after:absolute after:inset-0 hover:underline">{{ $f->name }}</a>
        <p class="truncate text-xs text-muted">{{ $f->area ?: $f->city }}@if ($open && $service->specialist_name) · {{ $service->specialist_name }}@endif</p>
    </div>

    @if ($schedule || $price)
        <div class="flex flex-wrap gap-1.5 text-xs">
            @if ($schedule)<span class="inline-flex items-center gap-1 rounded-full bg-surface-2 px-2.5 py-1"><x-icon name="calendar" class="size-3.5 text-muted" />{{ $schedule }}</span>@endif
            @if ($price)<span class="inline-flex items-center rounded-full bg-surface-2 px-2.5 py-1 font-medium">{{ $price }}</span>@endif
        </div>
    @endif

    <div class="relative z-10 mt-auto flex items-center justify-between gap-2 border-t border-line/70 pt-3.5">
        <x-verification-badge :service="$service" />
        @if ($open)
            <div class="flex gap-1.5">
                @if ($f->phone)
                    <a href="tel:{{ $f->phone }}" class="btn-primary !px-3.5 !py-1.5 text-[13px]" title="Call {{ $f->phone }}"><x-icon name="phone" />Call</a>
                @endif
                <a href="{{ $f->directionsUrl() }}" target="_blank" rel="noopener" class="btn-secondary !p-2" title="Directions" aria-label="Directions to {{ $f->name }}"><x-icon name="route" /></a>
            </div>
        @else
            <span class="inline-flex items-center gap-1.5 text-xs text-muted"><x-icon name="lock" class="size-3.5" />Needs a plan</span>
        @endif
    </div>
</article>
