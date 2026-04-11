@php
    $total = (int) $paginator->total();
    $from = (int) ($paginator->firstItem() ?? 0);
    $to = (int) ($paginator->lastItem() ?? 0);
    $current = (int) $paginator->currentPage();
    $lastPage = max((int) $paginator->lastPage(), 1);

    if ($total === 0) {
        $summary = 'No records found';
    } elseif ($lastPage <= 1) {
        $summary = "Showing all {$total} records";
    } else {
        $summary = "Showing records {$from}-{$to} of {$total}";
    }
@endphp

<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="table-paginator">
    <div class="table-paginator-meta">
        {{ $summary }}
    </div>

    <div class="table-paginator-nav">
        @if($paginator->onFirstPage())
            <span class="table-paginator-btn is-disabled" aria-disabled="true" title="First page">
                <i class="bi bi-chevron-double-left"></i>
            </span>
            <span class="table-paginator-btn is-disabled" aria-disabled="true" title="Previous page">
                <i class="bi bi-chevron-left"></i>
            </span>
        @else
            <a class="table-paginator-btn" href="{{ $paginator->url(1) }}" rel="first" title="First page">
                <i class="bi bi-chevron-double-left"></i>
            </a>
            <a class="table-paginator-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" title="Previous page">
                <i class="bi bi-chevron-left"></i>
            </a>
        @endif

        @if ($paginator->hasPages())
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="table-paginator-btn is-ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $current)
                            <span class="table-paginator-btn is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="table-paginator-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        @else
            <span class="table-paginator-btn is-active" aria-current="page">1</span>
        @endif

        @if($paginator->hasMorePages())
            <a class="table-paginator-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" title="Next page">
                <i class="bi bi-chevron-right"></i>
            </a>
            <a class="table-paginator-btn" href="{{ $paginator->url($lastPage) }}" rel="last" title="Last page">
                <i class="bi bi-chevron-double-right"></i>
            </a>
        @else
            <span class="table-paginator-btn is-disabled" aria-disabled="true" title="Next page">
                <i class="bi bi-chevron-right"></i>
            </span>
            <span class="table-paginator-btn is-disabled" aria-disabled="true" title="Last page">
                <i class="bi bi-chevron-double-right"></i>
            </span>
        @endif
    </div>
</nav>
