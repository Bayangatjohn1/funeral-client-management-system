@if($cases->hasPages())
    {{ $cases->links('components.pagination.table') }}
@else
    <div class="table-paginator-meta">
        Showing {{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }} of {{ $cases->total() }} records
    </div>
@endif
