<div class="service-catalog-actions">
    <button type="button" class="row-hover-action is-view-action service-card-view service-primary-view primary-view-action {{ ($cardAction ?? false) ? 'primary-view-action--card' : '' }} record-action-control" data-service-modal-url="{{ route('admin.promotions.show', ['promotion' => $promotion, 'modal' => 1, '_service_modal' => 1]) }}" data-service-modal-title="Promotion Details" data-action-tooltip="View promotion" aria-label="View promotion details for {{ $promotion->name }}">
        <i class="bi bi-eye" aria-hidden="true"></i>
        @if($cardAction ?? false)<span>View</span>@endif
    </button>
    @if($canManage)
        <div class="row-action-menu compact-action-menu service-actions-menu" data-row-menu>
            <button type="button" class="row-action-trigger compact-action-trigger record-action-control" data-row-menu-trigger data-action-tooltip="More actions" aria-label="More promotion actions for {{ $promotion->name }}" aria-haspopup="menu" aria-expanded="false"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
            <div class="row-action-dropdown service-catalog-write-actions" role="menu">
            <button type="button" class="row-action-item compact-action-item is-edit-action" data-row-menu-item data-service-modal-url="{{ route('admin.promotions.edit', ['promotion' => $promotion, 'modal' => 1, '_service_modal' => 1]) }}" data-service-modal-title="Edit Promotion" aria-label="Edit Promotion">
                <i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span>
            </button>
            @if(in_array($promotionEffectiveStatus, ['Active', 'Scheduled'], true))
                <form method="POST" action="{{ route('admin.promotions.stop', $promotion) }}" data-promotion-confirm="stop" data-promotion-name="{{ $promotion->name }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="confirmed" value="1">
                    <button type="submit" class="row-action-item compact-action-item is-caution" data-row-menu-item aria-label="Turn Off Promotion"><i class="bi bi-power" aria-hidden="true"></i><span>Turn off</span></button>
                </form>
            @elseif($promotionEffectiveStatus === 'Turned Off')
                <form method="POST" action="{{ route('admin.promotions.reactivate', $promotion) }}" data-promotion-confirm="on" data-promotion-name="{{ $promotion->name }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="row-action-item compact-action-item is-positive" data-row-menu-item aria-label="Turn On Promotion"><i class="bi bi-power" aria-hidden="true"></i><span>Turn on</span></button>
                </form>
            @endif
            </div>
        </div>
    @endif
</div>

