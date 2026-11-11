@foreach($users as $directoryUser)
<dialog id="user-information-{{ $directoryUser->id }}" class="user-information-dialog record-drawer" aria-labelledby="user-information-title-{{ $directoryUser->id }}">
    <div class="user-information-head">
        <div class="user-information-heading"><span class="user-information-heading-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span><div><h2 id="user-information-title-{{ $directoryUser->id }}">User Information</h2><p>{{ $directoryUser->name }}</p></div></div>
        <button type="button" data-user-dialog-close aria-label="Close user information"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="record-drawer-body">
    <section class="record-detail-section">
    <h3>Access and assignment</h3>
    <div class="user-information-access"><div><span>Sign-in Access</span><strong class="user-modal-status {{ $directoryUser->is_active ? 'is-active' : 'is-inactive' }}">{{ $directoryUser->is_active ? 'Active' : 'Inactive' }}</strong></div><div><span>System Role</span><strong>{{ $directoryUser->roleLabel() }}</strong></div></div>
    <dl class="user-information-fields"><div><dt>Branch</dt><dd>{{ $directoryUser->branch ? $directoryUser->branch->branch_code.' - '.$directoryUser->branch->branch_name : 'No branch assigned' }}</dd></div></dl>
    </section>
    <section class="record-detail-section">
    <h3>Personal and contact information</h3>
    <dl class="user-information-fields">
        @foreach(['Full Name' => $directoryUser->name, 'Email' => $directoryUser->email, 'Contact Number' => $directoryUser->contact_number ?: 'Not provided', 'Address' => $directoryUser->address ?: 'Not provided'] as $label => $value)
            <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
    </section>
    <section class="record-detail-section">
    <h3>Record information</h3>
    <dl class="user-information-fields"><div><dt>Date Created</dt><dd>{{ $directoryUser->created_at?->format('M d, Y') ?? 'Not available' }}</dd></div></dl>
    </section>
    </div>
</dialog>
<dialog id="user-status-{{ $directoryUser->id }}" class="user-information-dialog user-status-dialog directory-feedback-dialog" data-confirm-tone="{{ $directoryUser->is_active ? 'warning' : 'success' }}" aria-labelledby="user-status-title-{{ $directoryUser->id }}" aria-describedby="user-status-copy-{{ $directoryUser->id }}">
    <span class="directory-feedback-dialog__icon" aria-hidden="true"><i class="bi {{ $directoryUser->is_active ? 'bi-exclamation-triangle' : 'bi-check-circle' }}"></i></span>
    <h2 id="user-status-title-{{ $directoryUser->id }}">{{ $directoryUser->is_active ? 'Deactivate user?' : 'Activate user?' }}</h2>
    <p class="directory-feedback-dialog__record">{{ $directoryUser->name }}</p>
    <p id="user-status-copy-{{ $directoryUser->id }}">{{ $directoryUser->is_active ? 'This account will be marked inactive. The user will no longer be able to sign in until the account is activated again.' : 'This account will be marked active. The user can sign in again with their existing role and branch access.' }}</p>
    <footer class="user-information-footer">
        <button type="button" class="btn-outline" data-user-dialog-close>Cancel</button>
        <form method="POST" action="{{ route('admin.users.toggleActive', $directoryUser) }}" data-user-status-form>
            @csrf @method('PATCH')
            <button type="submit" class="directory-feedback-dialog__confirm" data-pending-label="{{ $directoryUser->is_active ? 'Deactivating…' : 'Activating…' }}"><i class="bi {{ $directoryUser->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}" aria-hidden="true"></i> <span>{{ $directoryUser->is_active ? 'Deactivate user' : 'Activate user' }}</span></button>
        </form>
    </footer>
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
            window.recordDialogMotion.open(dialog);
            document.body.classList.add('user-information-open');
        }
        const close = event.target.closest('[data-user-dialog-close]');
        if (close) window.recordDialogMotion.close(close.closest('dialog'));
    });
    const bindDialogs = () => document.querySelectorAll('.user-information-dialog, .user-status-dialog').forEach(dialog => {
        if (dialog.dataset.resultDialogBound) return;
        dialog.dataset.resultDialogBound = 'true';
        dialog.addEventListener('cancel', event => { event.preventDefault(); window.recordDialogMotion.close(dialog); });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) window.recordDialogMotion.close(dialog);
        });
        dialog.addEventListener('close', () => {
            document.body.classList.toggle('user-information-open', !!document.querySelector('.user-information-dialog[open]'));
            returnFocus.get(dialog)?.focus();
        });
    });
    bindDialogs();
    document.addEventListener('submit', event => {
        const form = event.target.closest?.('[data-user-status-form]');
        if (!form) return;
        const button = form.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const label = button.querySelector('span');
        if (label) label.textContent = button.dataset.pendingLabel || 'Updating…';
    });
    document.addEventListener('record-results:updated', bindDialogs);
})();
</script>
