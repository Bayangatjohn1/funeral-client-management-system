@if(session('success'))
    <p class="svc-status-feedback is-success" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ session('success') }}</p>
    @if(request()->boolean('modal'))
        <script>window.parent?.refreshServiceCatalogHub?.();</script>
    @endif
@endif
@if($errors->any())
    <div class="svc-status-feedback is-error" role="alert">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif
