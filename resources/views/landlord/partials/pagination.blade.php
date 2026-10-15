{{-- Pagination styled by site.css. Use: $paginator->links('landlord.partials.pagination') --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="is-disabled">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
        @else
            <span class="is-disabled" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
        @endif
    </nav>
@endif
