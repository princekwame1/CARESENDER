{{-- A table in a card: a header (title + actions on the right), a search/filter bar, then the table. Rows marked data-row are filtered in place by app.js. --}}
@props(['title' => null, 'count' => null])
<div {{ $attributes->class(['card admin-table-card overflow-hidden']) }} data-table-card>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <h2 class="text-lg font-semibold">{{ $title }} @if (! is_null($count))<span class="ml-1 text-sm font-normal text-muted">{{ $count }}</span>@endif</h2>
            @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    @isset($toolbar)
        <div {{ $toolbar->attributes->class(['flex flex-wrap items-center gap-3 border-b border-line bg-surface-2/50 p-4']) }}>{{ $toolbar }}</div>
    @endisset
    <div class="admin-table-wrap">{{ $slot }}</div>
    <p class="p-8 text-center text-sm text-muted" data-table-empty hidden>Nothing matches your search.</p>
</div>
