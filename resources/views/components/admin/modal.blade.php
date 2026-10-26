{{--
    Dashboard modal built on <dialog>.
    Open it with any button that has data-modal-open="{id}".
    Forms inside should include <input type="hidden" name="_modal" value="{id}">,
    so the modal reopens with the typed values if saving fails.
--}}
@props(['id', 'title', 'size' => 'md'])
@php
    $width = ['sm' => 'max-w-md', 'md' => 'max-w-xl', 'lg' => 'max-w-3xl', 'xl' => 'max-w-5xl'][$size] ?? 'max-w-xl';
    $reopen = request()->hasSession() && old('_modal') === $id;
@endphp
<dialog id="{{ $id }}" data-modal @if ($reopen) data-reopen @endif aria-labelledby="{{ $id }}-title"
        {{ $attributes->class(["admin-modal w-[calc(100%-2rem)] {$width}"]) }}>
    <div class="flex max-h-[min(85dvh,52rem)] flex-col">
        <header class="flex items-center justify-between gap-4 border-b border-line px-6 py-4">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
            <button type="button" data-modal-close class="grid size-8 place-items-center rounded-md text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Close">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </header>
        <div class="overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>
        @isset($footer)
            <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-line bg-surface-2/50 px-6 py-4">{{ $footer }}</footer>
        @endisset
    </div>
</dialog>
