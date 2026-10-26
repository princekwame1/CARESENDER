@props(['items', 'empty' => 'No data yet.'])
@php($max = max(1, ...array_values($items ?: [0])))
@if (empty($items))
    <p class="text-sm text-muted">{{ $empty }}</p>
@else
    <ul class="space-y-3.5">
        @foreach ($items as $label => $count)
            <li>
                <div class="mb-1 flex justify-between gap-4 text-sm"><span class="truncate">{{ $label }}</span><span class="tabular-nums font-semibold">{{ number_format($count) }}</span></div>
                <div class="h-2 rounded-full bg-surface-2"><div class="h-2 rounded-full bg-brand" style="width: {{ round($count / $max * 100, 1) }}%"></div></div>
            </li>
        @endforeach
    </ul>
@endif
