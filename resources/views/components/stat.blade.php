@props(['label', 'value', 'icon' => 'chart', 'hint' => null])
<div data-reveal {{ $attributes->merge(['class' => 'card flex flex-col items-start gap-2 p-3 sm:flex-row sm:gap-5 sm:p-5']) }}>
    <span class="icon-tile max-sm:!size-9 max-sm:!rounded-xl"><x-icon :name="$icon" class="size-5 max-sm:!size-4" /></span>
    <div class="min-w-0">
        <p class="text-xs leading-snug text-muted sm:text-sm">{{ $label }}</p>
        <p class="font-display text-lg font-bold tabular-nums sm:text-2xl">@if (is_numeric($value))<span data-count="{{ $value }}">{{ number_format($value) }}</span>@else{{ $value }}@endif</p>
        @if ($hint)<p class="mt-1 hidden text-xs text-muted sm:block">{{ $hint }}</p>@endif
    </div>
</div>
