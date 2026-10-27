@if($canManage)
    @foreach($statusActions as $index => $action)
        @php($dialogId = 'catalog-status-confirm-' . $index)
        <button type="button" class="svc-detail-secondary" data-status-open="{{ $dialogId }}">
            <i class="bi bi-{{ $action['icon'] }}" aria-hidden="true"></i> {{ $action['label'] }}
        </button>
        <dialog class="svc-status-dialog" id="{{ $dialogId }}" aria-labelledby="{{ $dialogId }}-title">
            <div class="svc-status-dialog-head">
                <span class="svc-status-dialog-icon" aria-hidden="true"><i class="bi bi-{{ ($action['blocked'] ?? false) ? 'exclamation-circle' : $action['icon'] }}"></i></span>
                <div>
                    <h2 id="{{ $dialogId }}-title">{{ $action['title'] }}</h2>
                    <p>{{ $action['message'] }}</p>
                </div>
            </div>
            @if(!empty($action['affected']))
                <div class="svc-status-affected">
                    <strong>Active packages using this item</strong>
                    <ul>
                        @foreach($action['affected'] as $affectedPackage)
                            <li><a href="{{ route('admin.packages.show', ['package' => $affectedPackage, 'modal' => 1, 'return_to' => $returnTo]) }}" data-service-modal-url="{{ route('admin.packages.show', ['package' => $affectedPackage, 'modal' => 1, 'return_to' => $returnTo]) }}" data-service-modal-title="View Package">{{ $affectedPackage->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(!empty($action['details']))
                <div class="svc-status-affected"><strong>What to fix</strong><ul>@foreach($action['details'] as $detail)<li>{{ $detail }}</li>@endforeach</ul></div>
            @endif
            <div class="svc-status-dialog-actions">
                <button type="button" class="svc-detail-secondary" data-status-cancel>{{ ($action['blocked'] ?? false) ? 'Close' : 'Cancel' }}</button>
                @unless($action['blocked'] ?? false)
                    <form method="POST" action="{{ $action['url'] }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="svc-detail-primary">{{ $action['label'] }}</button>
                    </form>
                @endunless
            </div>
        </dialog>
    @endforeach
    @once
        <script>
            document.addEventListener('click', event => {
                const opener = event.target.closest('[data-status-open]');
                if (opener) document.getElementById(opener.dataset.statusOpen)?.showModal();
                const cancel = event.target.closest('[data-status-cancel]');
                if (cancel) cancel.closest('dialog')?.close();
            });
            document.addEventListener('click', event => {
                if (event.target instanceof HTMLDialogElement && event.target.matches('.svc-status-dialog')) event.target.close();
            });
        </script>
    @endonce
@endif
