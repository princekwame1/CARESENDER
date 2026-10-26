@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="btn-secondary opacity-40"><x-icon name="back" /><span class="sr-only sm:not-sr-only">Previous</span></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary"><x-icon name="back" /><span class="sr-only sm:not-sr-only">Previous</span></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-muted">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="btn-primary min-w-10">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="btn-ghost hidden min-w-10 sm:inline-flex">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary"><span class="sr-only sm:not-sr-only">Next</span><x-icon name="arrow" /></a>
        @else
            <span class="btn-secondary opacity-40"><span class="sr-only sm:not-sr-only">Next</span><x-icon name="arrow" /></span>
        @endif
    </nav>
@endif
