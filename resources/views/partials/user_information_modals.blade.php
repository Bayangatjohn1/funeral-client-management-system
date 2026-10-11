@foreach($users as $directoryUser)
<dialog id="user-information-{{ $directoryUser->id }}" class="user-information-dialog" aria-labelledby="user-information-title-{{ $directoryUser->id }}">
    <div class="user-information-head">
        <div class="user-information-heading"><span class="user-information-heading-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span><div><h2 id="user-information-title-{{ $directoryUser->id }}">User Information</h2><p>{{ $directoryUser->name }}</p></div></div>
        <button type="button" data-user-dialog-close aria-label="Close user information"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="user-information-access"><div><span>Sign-in Access</span><strong class="user-modal-status {{ $directoryUser->is_active ? 'is-active' : 'is-inactive' }}">{{ $directoryUser->is_active ? 'Active' : 'Inactive' }}</strong></div><div><span>System Role</span><strong>{{ $directoryUser->roleLabel() }}</strong></div></div>
    <dl class="user-information-fields">
        @foreach(['Full Name' => $directoryUser->name, 'Email' => $directoryUser->email, 'Branch' => $directoryUser->branch ? $directoryUser->branch->branch_code.' - '.$directoryUser->branch->branch_name : 'No branch assigned', 'Contact Number' => $directoryUser->contact_number ?: 'Not provided', 'Address' => $directoryUser->address ?: 'Not provided', 'Date Created' => $directoryUser->created_at?->format('M d, Y') ?? 'Not available'] as $label => $value)
            <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
    <div class="user-information-footer">
        <button type="button" class="btn-outline user-information-close-action" data-user-dialog-close>Close</button>
        <a class="btn-outline" href="{{ route('admin.users.edit', ['user' => $directoryUser, 'return_to' => request()->fullUrl()]) }}"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit user</a>
        <button type="button" class="btn-outline" data-user-status-dialog="user-status-{{ $directoryUser->id }}" aria-haspopup="dialog"><i class="bi {{ $directoryUser->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}" aria-hidden="true"></i> {{ $directoryUser->is_active ? 'Deactivate user' : 'Activate user' }}</button>
    </div>
</dialog>
<dialog id="user-status-{{ $directoryUser->id }}" class="user-information-dialog user-status-dialog" aria-labelledby="user-status-title-{{ $directoryUser->id }}">
    <div class="user-information-head">
        <div class="user-information-heading"><span class="user-information-heading-icon" aria-hidden="true"><i class="bi {{ $directoryUser->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}"></i></span><div><h2 id="user-status-title-{{ $directoryUser->id }}">{{ $directoryUser->is_active ? 'Deactivate user?' : 'Activate user?' }}</h2><p>{{ $directoryUser->name }}</p></div></div>
        <button type="button" data-user-dialog-close aria-label="Close status confirmation"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="user-status-body">
        <p>{{ $directoryUser->is_active ? 'This account will be marked inactive. The user will no longer be able to sign in until the account is activated again.' : 'This account will be marked active. The user can sign in again with their existing role and branch access.' }}</p>
    </div>
    <div class="user-information-footer">
        <button type="button" class="btn-outline" data-user-dialog-close>Cancel</button>
        <form method="POST" action="{{ route('admin.users.toggleActive', $directoryUser) }}">
            @csrf @method('PATCH')
            <button type="submit" class="{{ $directoryUser->is_active ? 'user-status-confirm-danger' : 'btn-primary' }}"><i class="bi {{ $directoryUser->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}" aria-hidden="true"></i> {{ $directoryUser->is_active ? 'Deactivate user' : 'Activate user' }}</button>
        </form>
    </div>
</dialog>
@endforeach
<script>
(() => {
    const returnFocus = new WeakMap();
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-user-dialog], [data-user-status-dialog]');
        if (trigger) {
            const dialog = document.getElementById(trigger.dataset.userDialog || trigger.dataset.userStatusDialog);
            if (!dialog) return;
            returnFocus.set(dialog, trigger);
            dialog.showModal();
            document.body.classList.add('user-information-open');
        }
        const close = event.target.closest('[data-user-dialog-close]');
        if (close) close.closest('dialog').close();
    });
    document.querySelectorAll('.user-information-dialog').forEach(dialog => {
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
        });
        dialog.addEventListener('close', () => {
            document.body.classList.toggle('user-information-open', !!document.querySelector('.user-information-dialog[open]'));
            returnFocus.get(dialog)?.focus();
        });
    });
})();
</script>
