@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination Navigation">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" style="opacity:0.4;cursor:not-allowed">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
        @endif

        {{-- Pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-disabled="true" style="opacity:0.4">...</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
        @else
            <span aria-disabled="true" style="opacity:0.4;cursor:not-allowed">›</span>
        @endif
    </nav>
@endif
