@props(['facility', 'compact' => false])
@php($open = app(\App\Services\AccessGate::class)->allows(request(), $facility->id))
<article data-reveal {{ $attributes->merge(['class' => 'card group relative flex flex-col gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-glow']) }}>
    <div class="min-w-0">
        <a href="{{ route('facilities.show', $facility) }}" class="line-clamp-2 text-base font-semibold leading-snug text-ink after:absolute after:inset-0 hover:text-brand">{{ $facility->name }}</a>
        <p class="mt-0.5 truncate text-xs text-muted">{{ $facility->facility_type ?? 'Health facility' }} · {{ $facility->area ?: $facility->city }}</p>
    </div>

    <div class="flex flex-wrap gap-1.5">
        <x-distance :km="$facility->distanceKm" />
        @if ($facility->services_count)
            <span class="badge-pending">{{ $facility->services_count }} {{ Str::plural('service', $facility->services_count) }}</span>
        @endif
    </div>

    <div class="relative z-10 mt-auto flex gap-1.5 border-t border-line/70 pt-3.5">
        @if ($open)
            @if ($facility->phone)
                <a href="tel:{{ $facility->phone }}" class="btn-primary !px-3.5 !py-1.5 text-[13px]" title="Call {{ $facility->phone }}"><x-icon name="phone" />Call</a>
            @endif
            @if ($facility->whatsappUrl())
                <a href="{{ $facility->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secondary !p-2" title="WhatsApp" aria-label="WhatsApp {{ $facility->name }}"><x-icon name="chat" /></a>
            @endif
            <a href="{{ $facility->directionsUrl() }}" target="_blank" rel="noopener" class="btn-secondary !p-2" title="Directions" aria-label="Directions to {{ $facility->name }}"><x-icon name="route" /></a>
        @else
            <span class="inline-flex items-center gap-1.5 text-xs text-muted"><x-icon name="lock" class="size-3.5" />Contacts and directions need a plan</span>
        @endif
    </div>
</article>
