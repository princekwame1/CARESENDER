{{-- Ranked counts: position, name and count on one line, a thin bar (relative to the top item) under it. --}}
@props(['items', 'empty' => 'No data yet.'])
@if (empty($items))
    <p class="py-6 text-center text-sm text-muted">{{ $empty }}</p>
@else
    @php($max = max(1, ...array_values($items)))
    <ol class="space-y-3">
        @foreach ($items as $label => $count)
            <li class="grid grid-cols-[1.25rem_1fr] items-start gap-x-2">
                <span class="pt-px text-xs font-semibold tabular-nums text-muted">{{ $loop->iteration }}</span>
                <div class="min-w-0">
                    <div class="flex justify-between gap-3 text-sm"><span class="truncate text-ink">{{ $label }}</span><span class="shrink-0 font-semibold tabular-nums">{{ number_format($count) }}</span></div>
                    <div class="mt-1.5 h-1.5 rounded-full bg-surface-2"><div class="h-full rounded-full bg-brand" style="width: {{ max(2, round($count / $max * 100, 1)) }}%"></div></div>
                </div>
            </li>
        @endforeach
    </ol>
@endif
