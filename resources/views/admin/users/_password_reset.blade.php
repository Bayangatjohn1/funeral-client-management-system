<section class="modal-shell-card max-w-5xl mx-auto rounded-2xl border p-6" aria-labelledby="password-reset-heading">
    <h2 id="password-reset-heading" class="text-xl font-semibold"><i class="bi bi-key" aria-hidden="true"></i> Reset user password</h2>
    @if($user->isSystemAdmin())
        <p class="mt-2 text-sm">System Admin accounts use the designated account recovery process.</p>
    @else
        <p class="mt-2 mb-4 text-sm">After verifying this person's identity, issue a temporary password they must change on their next sign-in.</p>
        <button type="button" class="btn btn-outline" data-open-password-reset>Reset password</button>
        <dialog id="password-reset-dialog" class="password-reset-dialog" aria-labelledby="reset-modal-title" aria-describedby="reset-modal-notice" data-reopen="{{ old('password_reset_action') === '1' && $errors->any() ? '1' : '0' }}">
            <header><h2 id="reset-modal-title">Reset user password</h2><button type="button" data-close-password-reset aria-label="Close password reset"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}" data-password-reset-form>
                @csrf @method('PATCH')
                <input type="hidden" name="password_reset_action" value="1">
                <div class="reset-result-error" data-reset-feedback role="alert" hidden></div>
                @if(old('password_reset_action') === '1' && $errors->any())
                    <div class="reset-result-error" role="alert"><strong>Password reset unsuccessful.</strong> {{ $errors->first() }} No password was changed. A new temporary password has been generated; review and confirm again.</div>
                @endif
                <p class="reset-target"><strong>{{ $user->name }}</strong> · {{ $user->isStaff() ? 'Staff' : 'Branch Admin' }}</p>
                <p id="reset-modal-notice" class="reset-notice">Signs out existing sessions. MFA stays enabled; password change is required at next sign-in.</p>
                <div class="reset-field-group" data-reset-details>
                <label for="reset-temporary-password">Temporary password *</label>
                <div class="reset-password-row">
                <input id="reset-temporary-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="128" required class="form-input" readonly aria-describedby="reset-password-error">
                <input type="hidden" name="password_confirmation" data-reset-confirmation>
                <div class="reset-password-tools">
                    <button type="button" class="btn btn-outline" data-reset-show aria-label="Show temporary password" title="Show or hide password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                    <button type="button" class="btn btn-outline" data-reset-copy aria-label="Copy temporary password" title="Copy password"><i class="bi bi-copy" aria-hidden="true"></i></button>
                    <button type="button" class="btn btn-outline" data-reset-generate aria-label="Generate another temporary password" title="Regenerate password"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
                </div>
                </div>
                <span class="reset-helper" data-reset-status role="status"></span>
                <div id="reset-password-error"><x-input-error :messages="$errors->get('password')" /></div>
                </div>
                <div class="reset-field-group" data-reset-details>
                <label for="reset-reason">Reason for reset *</label>
                <textarea id="reset-reason" name="reason" required minlength="10" maxlength="1000" rows="2" class="form-input" placeholder="e.g. User forgot their password; identity verified.">{{ old('password_reset_action') === '1' ? old('reason') : '' }}</textarea>
                <x-input-error :messages="$errors->get('reason')" />
                </div>
                <div class="reset-field-group" data-reset-authorize hidden>
                <p class="reset-helper">Confirm your password to complete this reset for <strong>{{ $user->name }}</strong>.</p>
                <label for="reset-admin-password">Your password *</label>
                <input id="reset-admin-password" name="current_password" type="password" required autocomplete="current-password" class="form-input" disabled>
                <p class="reset-helper">Use your own password to authorize. Share the temporary password privately.</p>
                <x-input-error :messages="$errors->get('current_password')" />
                </div>
                <footer><button type="button" class="btn btn-outline" data-close-password-reset>Cancel</button><button type="button" class="btn btn-outline" data-reset-back hidden>Back</button><button type="button" class="btn btn-primary-custom" data-reset-review>Confirm password reset</button><button type="submit" class="btn btn-primary-custom" data-reset-submit hidden>Confirm reset</button></footer>
            </form>
        </dialog>
    @endif
</section>
