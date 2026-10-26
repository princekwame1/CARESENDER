{{-- A share of a total: label and "x of y" above, a filled bar on a lighter track of the same colour below. --}}
@props(['label', 'value', 'total'])
@php($percent = $total > 0 ? round($value / $total * 100) : 0)
<div {{ $attributes }}>
    <div class="flex items-baseline justify-between gap-3 text-sm">
        <span class="text-ink">{{ $label }}</span>
        <span class="shrink-0"><span class="font-semibold text-ink">{{ $percent }}%</span> <span class="text-xs text-muted tabular-nums">{{ number_format($value) }} of {{ number_format($total) }}</span></span>
    </div>
    <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-soft" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="{{ $label }}">
        <div class="h-full rounded-full bg-brand" style="width: {{ $percent }}%"></div>
    </div>
</div>
