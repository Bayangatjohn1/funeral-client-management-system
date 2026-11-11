<button type="button" class="user-view-button primary-view-action record-action-control {{ ($userViewVariant ?? '') === 'card' ? 'user-view-button--card primary-view-action--card' : '' }}" data-user-dialog="user-information-{{ $directoryUser->id }}" data-action-tooltip="View user" aria-label="View user details for {{ $directoryUser->name }}" aria-haspopup="dialog">
    <i class="bi bi-eye" aria-hidden="true"></i>
    @if(($userViewVariant ?? '') === 'card')<span>View</span>@endif
</button>
