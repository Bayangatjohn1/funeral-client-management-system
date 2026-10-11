<button type="button" class="user-view-button {{ ($userViewVariant ?? '') === 'card' ? 'user-view-button--card' : '' }}" data-user-dialog="user-information-{{ $directoryUser->id }}" aria-label="View user information for {{ $directoryUser->name }}" aria-haspopup="dialog">
    <i class="bi bi-eye" aria-hidden="true"></i>
    @if(($userViewVariant ?? '') === 'card')<span class="user-view-label">View Details</span>@endif
    <span class="user-view-tooltip" aria-hidden="true">View user information</span>
</button>
