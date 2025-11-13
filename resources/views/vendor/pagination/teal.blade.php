@if ($paginator->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-2">
        <div class="text-muted small">
            @if ($paginator->firstItem())
                Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} resultados
            @else
                Mostrando {{ $paginator->count() }} resultados
            @endif
        </div>

        <div class="btn-group" role="group" aria-label="Pagination">
            @if ($paginator->onFirstPage())
                <a class="btn btn-outline-secondary rounded-pill disabled"><i class="bi bi-chevron-left"></i> Anterior</a>
            @else
                <a class="btn btn-outline-secondary rounded-pill" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i> Anterior</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="btn btn-teal rounded-pill" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente <i class="bi bi-chevron-right"></i></a>
            @else
                <a class="btn btn-outline-secondary rounded-pill disabled">Siguiente <i class="bi bi-chevron-right"></i></a>
            @endif
        </div>
    </div>
@endif
