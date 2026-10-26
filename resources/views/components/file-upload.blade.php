{{--
    File picker: a dashed drop area to click or drop a file on, and a bar showing the chosen file
    with a button to clear it. Behaviour in resources/js/modules/file-upload.js.
--}}
@props(['name', 'accept' => null, 'label' => 'Browse a file to upload', 'hint' => null, 'id' => null])
@php($id ??= 'file-'.\Illuminate\Support\Str::random(8))
<div data-file-upload {{ $attributes->class('group/upload flex flex-col gap-1.5 rounded-xl bg-brand-soft/60 p-2.5 shadow-[4px_4px_30px_rgb(0_0_0/0.08)]') }}>
    <label for="{{ $id }}" data-file-drop class="flex min-h-36 flex-1 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-brand/60 px-4 py-6 text-center transition hover:border-brand hover:bg-brand-soft data-[dragging]:border-brand data-[dragging]:bg-brand-soft">
        <span data-file-empty class="flex flex-col items-center gap-2">
            <svg class="size-14 text-brand" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V9a5 5 0 0 1 10 0v1a4 4 0 0 1 2 7.5M7 10a4 4 0 0 0-2 7.5M7 10c.43 0 .85.07 1.24.2M12 12v9m0-9 3 3m-3-3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="text-sm font-medium text-ink">{{ $label }}</span>
            @if ($hint)<span class="text-xs text-muted">{{ $hint }}</span>@endif
        </span>
        {{-- Preview of the chosen image, shown before anything is uploaded. --}}
        <span data-file-preview hidden class="flex flex-col items-center gap-2">
            <img alt="Preview of the chosen image" class="max-h-40 max-w-full rounded-lg object-contain shadow-sm [background:repeating-conic-gradient(var(--surface-2)_0_25%,var(--surface)_0_50%)_0_0/16px_16px]">
            <span class="text-xs text-muted">Click or drop to choose another</span>
        </span>
    </label>
    <div class="flex h-10 items-center gap-2 rounded-xl bg-brand-soft px-2">
        <label for="{{ $id }}" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2">
            <svg class="size-7 shrink-0 rounded-full bg-surface p-1 text-brand shadow-sm" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M15.33 6H8.5v20h15V14.15h-8.17z"/><path d="M18.15 6v5.34h5.35z"/></svg>
            <span data-file-name class="flex-1 truncate text-center text-sm text-muted">No file selected</span>
        </label>
        <button type="button" data-file-clear disabled class="grid size-7 shrink-0 place-items-center rounded-full bg-surface text-brand shadow-sm transition hover:text-danger disabled:opacity-40 disabled:hover:text-brand" aria-label="Remove the chosen file">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5.17 10.15A2 2 0 0 1 7.16 8h9.68a2 2 0 0 1 2 2.15l-.7 9A2 2 0 0 1 16.15 21h-8.3a2 2 0 0 1-2-1.85zM19.5 5h-15M10 3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2h-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
    </div>
    <input id="{{ $id }}" type="file" name="{{ $name }}" @if ($accept) accept="{{ $accept }}" @endif class="sr-only" data-file-input>
</div>
