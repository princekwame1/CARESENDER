@props(['km'])
@if (! is_null($km))
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-brand-soft px-2 py-0.5 text-[11px] font-semibold text-brand']) }} title="Approximate, based on neighbourhood location">
        <x-icon name="pin" class="size-3" />~{{ $km < 1 ? 'under 1' : number_format($km, 1) }} km
    </span>
@endif
