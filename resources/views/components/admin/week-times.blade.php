{{--
    Day-by-day time editor.
    name:  input prefix, e.g. "opening_hours" or "services[abc][slots]"
    value: ["Monday" => ["start" => "08:00", "end" => "17:00"], ...]
    from/to: field names for the two times ("open"/"close" or "start"/"end")
--}}
@props(['name', 'value' => [], 'from' => 'start', 'to' => 'end', 'fromLabel' => 'From', 'toLabel' => 'To'])
<div class="overflow-hidden rounded-2xl border border-line" data-week-times>
    @foreach (\App\Services\SearchFilters::DAYS as $day)
        @php($row = $value[$day] ?? null)
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-line/70 px-4 py-2.5 last:border-0 has-[:checked]:bg-brand-soft/40">
            <label class="flex w-32 cursor-pointer items-center gap-2.5 text-sm font-medium">
                <input type="checkbox" name="{{ $name }}[{{ $day }}][on]" value="1" @checked($row) class="peer size-4 accent-[var(--brand)]">
                {{ $day }}
            </label>
            <div class="flex items-center gap-2 text-sm">
                <label class="sr-only" for="{{ $name }}-{{ $day }}-{{ $from }}">{{ $day }} {{ $fromLabel }}</label>
                <input id="{{ $name }}-{{ $day }}-{{ $from }}" type="time" name="{{ $name }}[{{ $day }}][{{ $from }}]" value="{{ $row[$from] ?? '' }}" class="field !w-32 !py-1.5">
                <span class="text-muted">to</span>
                <label class="sr-only" for="{{ $name }}-{{ $day }}-{{ $to }}">{{ $day }} {{ $toLabel }}</label>
                <input id="{{ $name }}-{{ $day }}-{{ $to }}" type="time" name="{{ $name }}[{{ $day }}][{{ $to }}]" value="{{ $row[$to] ?? '' }}" class="field !w-32 !py-1.5">
            </div>
        </div>
    @endforeach
    <div class="flex flex-wrap gap-2 bg-surface-2/60 px-4 py-2.5 text-xs">
        <span class="text-muted">Quick fill:</span>
        <button type="button" class="font-medium text-brand hover:underline" data-fill="weekdays" data-from="08:00" data-to="17:00">Weekdays 8am to 5pm</button>
        <button type="button" class="font-medium text-brand hover:underline" data-fill="copy-first">Copy first ticked day to all ticked</button>
        <button type="button" class="font-medium text-brand hover:underline" data-fill="clear">Clear</button>
    </div>
</div>
