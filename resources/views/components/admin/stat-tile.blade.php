{{--
    Dashboard number: label, value, optional change vs the previous period (an arrow and words, not colour alone)
    and an optional sparkline whose last $current points (the current period) are drawn in the brand colour.
--}}
@props(['label', 'value', 'icon' => 'chart', 'hint' => null, 'delta' => null, 'deltaLabel' => 'vs last week', 'trend' => null, 'current' => 7, 'href' => null])
@php
    $points = null;
    if ($trend && count($trend) > 1) {
        $max = max(1, ...$trend);
        $step = 100 / (count($trend) - 1);
        $xy = array_map(fn ($v, $i) => round($i * $step, 2).','.round(30 - $v / $max * 26, 2), $trend, array_keys($trend));
        $points = ['all' => implode(' ', $xy), 'current' => implode(' ', array_slice($xy, -$current)), 'area' => '0,32 '.implode(' ', $xy).' 100,32'];
    }
@endphp
<div data-reveal {{ $attributes->class('card relative flex flex-col gap-3 p-5') }}>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted">{{ $label }}</p>
        <span class="icon-tile !size-9"><x-icon :name="$icon" class="size-4" /></span>
    </div>
    <p class="font-display text-3xl font-bold leading-none text-ink">@if (is_numeric($value))<span data-count="{{ $value }}">{{ number_format($value) }}</span>@else{{ $value }}@endif</p>
    <div class="flex min-h-6 flex-wrap items-center gap-2 text-xs">
        @if (! is_null($delta))
            <span @class(['badge !py-0.5', 'bg-accent-soft text-accent' => $delta >= 0, 'bg-danger-soft text-danger' => $delta < 0])>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" @class(['rotate-180' => $delta < 0])><path d="M12 19V5m-6 6 6-6 6 6"/></svg>
                {{ $delta >= 0 ? 'Up' : 'Down' }} {{ abs($delta) }}%
            </span>
            <span class="text-muted">{{ $deltaLabel }}</span>
        @elseif ($hint)
            <span class="text-muted">{{ $hint }}</span>
        @endif
    </div>
    @if ($points)
        <svg class="h-8 w-full overflow-visible" viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true">
            <polygon points="{{ $points['area'] }}" class="fill-brand/10" />
            <polyline points="{{ $points['all'] }}" fill="none" class="stroke-muted/40" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />
            <polyline points="{{ $points['current'] }}" fill="none" class="stroke-brand" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />
        </svg>
    @endif
    @if ($href)<a href="{{ $href }}" class="absolute inset-0 rounded-[inherit]" aria-label="{{ $label }}: open"></a>@endif
</div>
