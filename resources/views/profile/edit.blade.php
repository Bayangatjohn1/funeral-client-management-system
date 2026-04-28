@extends('layouts.panel')

@section('page_title', 'My Profile')
@section('page_desc', 'View and manage your account information.')
@section('hide_layout_topbar', '1')

@section('content')
@if (session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif
@if (session('status') === 'password-updated')
    <div class="flash-success">Password updated successfully.</div>
@endif

<div class="prof-shell font-ui-body">

    {{-- ── 1. Profile Hero ──────────────────────────────────────── --}}
    <div class="card prof-hero-card">
        <div class="prof-banner"></div>
        <div class="prof-hero-body">

            {{-- Avatar row --}}
            <div class="prof-avatar-row">
                <div class="prof-avatar" aria-hidden="true">
                    {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                </div>
            </div>

            {{-- Identity --}}
            <div class="prof-identity">
                <h1 class="prof-name">{{ $user->name }}</h1>
                <p class="prof-email">{{ $user->email }}</p>
                <div class="prof-badges">
                    <span class="badge badge-brand">{{ $user->roleLabel() }}</span>
                    @if ($user->branch)
                        <span class="badge badge-neutral">
                            <i class="bi bi-geo-alt" style="font-size:9px;"></i>
                            {{ $user->branch->branch_name }}
                        </span>
                    @endif
                    @if ($user->is_active)
                        <span class="badge badge-success">
                            <span class="prof-dot"></span>Active
                        </span>
                    @else
                        <span class="badge badge-neutral">Inactive</span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="prof-hero-actions">
                <button type="button" class="btn btn-outline btn-sm" id="openEditModal">
                    <i class="bi bi-pencil" style="font-size:11px;"></i>
                    Edit Profile
                </button>
                <button type="button" class="btn btn-sm prof-pw-btn" id="openPwModal">
                    <i class="bi bi-key" style="font-size:11px;"></i>
                    Change Password
                </button>
            </div>

        </div>
    </div>

    {{-- ── 2. Personal Information (read-only) ─────────────────── --}}
    <div class="prof-detail-grid">
    <div class="card prof-section-card prof-personal-card">
        <div class="prof-section-head">
            <div class="prof-section-icon"><i class="bi bi-person-lines-fill"></i></div>
            <h2 class="prof-section-title">Personal Information</h2>
            <button type="button" class="btn btn-outline btn-sm prof-head-action" id="openEditModal2">
                <i class="bi bi-pencil" style="font-size:11px;"></i>
                Edit
            </button>
        </div>

        <div class="prof-fields">
            <div class="prof-field">
                <p class="prof-label">Full Name</p>
                <p class="prof-value">{{ $user->name ?: '—' }}</p>
            </div>
            <div class="prof-field">
                <p class="prof-label">Email Address</p>
                <p class="prof-value" style="word-break:break-all;">{{ $user->email ?: '—' }}</p>
            </div>
            <div class="prof-field">
                <p class="prof-label">Contact Number</p>
                <p class="prof-value">{{ $user->contact_number ?: '—' }}</p>
            </div>
            <div class="prof-field">
                <p class="prof-label">Position</p>
                <p class="prof-value">{{ $user->position ?: '—' }}</p>
            </div>
            <div class="prof-field prof-field-span">
                <p class="prof-label">Address</p>
                <p class="prof-value">{{ $user->address ?: '—' }}</p>
            </div>
        </div>
    </div>

    {{-- ── 3. Account Details (read-only) ──────────────────────── --}}
    <div class="prof-side-stack">
    <div class="card prof-section-card">
        <div class="prof-section-head">
            <div class="prof-section-icon"><i class="bi bi-shield-check"></i></div>
            <h2 class="prof-section-title">Account Details</h2>
        </div>

        <div class="prof-fields prof-fields-single">
            <div class="prof-field">
                <p class="prof-label">Role</p>
                <p class="prof-value">{{ $user->roleLabel() }}</p>
            </div>
            <div class="prof-field">
                <p class="prof-label">Branch</p>
                <p class="prof-value">{{ $user->branch?->branch_name ?: '—' }}</p>
            </div>
            <div class="prof-field" style="border-bottom:none;">
                <p class="prof-label">Account Status</p>
                <div style="margin-top:8px;">
                    @if ($user->is_active)
                        <span class="badge badge-success">
                            <span class="prof-dot"></span>Active
                        </span>
                    @else
                        <span class="badge badge-neutral">Inactive</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="prof-lock-note">
            <i class="bi bi-lock-fill" style="font-size:10px;flex-shrink:0;"></i>
            These fields are system-controlled and can only be changed by an administrator.
        </div>
    </div>

    {{-- ── 4. Security (password row) ──────────────────────────── --}}
    <div class="card prof-section-card">
        <div class="prof-section-head">
            <div class="prof-section-icon"><i class="bi bi-key-fill"></i></div>
            <h2 class="prof-section-title">Security</h2>
        </div>

        <div class="prof-security-row">
            <div>
                <p class="prof-label">Password</p>
                <p class="prof-pw-dots" aria-label="Password hidden">••••••••••••</p>
            </div>
            <button type="button" class="btn btn-outline btn-sm" id="openPwModal2">
                <i class="bi bi-key" style="font-size:11px;"></i>
                Change Password
            </button>
        </div>
    </div>
    </div>
    </div>

</div>{{-- /.prof-shell --}}


{{-- ══════════════════════════════════════════════════════════
     MODAL A — Edit Profile
     ══════════════════════════════════════════════════════════ --}}
<div id="editProfileOverlay" class="prof-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div id="editProfileSheet" class="prof-sheet scale-95 opacity-0">

        <button type="button" class="prof-modal-close" id="closeEditModal" aria-label="Close">
            <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
        </button>

        <div class="prof-modal-scroll">
            <div class="prof-modal-head">
                <div class="prof-section-icon"><i class="bi bi-person-lines-fill"></i></div>
                <div>
                    <h2 class="prof-modal-title">Edit Profile</h2>
                    <p class="prof-modal-desc">Update your personal information.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="prof-modal-form">
                @csrf
                @method('PATCH')

                <div class="prof-form-grid">
                    <div class="prof-form-span">
                        <label class="prof-label" style="display:block;margin-bottom:6px;">
                            Full Name <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="form-input" required autocomplete="name">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="prof-form-span">
                        <label class="prof-label" style="display:block;margin-bottom:6px;">
                            Email Address <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="form-input" required autocomplete="email">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="prof-label" style="display:block;margin-bottom:6px;">Contact Number</label>
                        <input type="text" name="contact_number"
                            value="{{ old('contact_number', $user->contact_number) }}"
                            class="form-input" placeholder="+63 9XX XXX XXXX">
                        @error('contact_number') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="prof-label" style="display:block;margin-bottom:6px;">Position</label>
                        <input type="text" name="position"
                            value="{{ old('position', $user->position) }}"
                            class="form-input" placeholder="e.g. Senior Staff">
                        @error('position') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="prof-form-span">
                        <label class="prof-label" style="display:block;margin-bottom:6px;">Address</label>
                        <input type="text" name="address"
                            value="{{ old('address', $user->address) }}"
                            class="form-input" autocomplete="street-address">
                        @error('address') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="prof-modal-foot">
                    <button type="button" class="btn btn-outline" id="cancelEditModal">Cancel</button>
                    <button type="submit" class="btn prof-submit-btn">
                        <i class="bi bi-save2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════
     MODAL B — Change Password
     ══════════════════════════════════════════════════════════ --}}
<div id="changePwOverlay" class="prof-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div id="changePwSheet" class="prof-sheet scale-95 opacity-0">

        <button type="button" class="prof-modal-close" id="closePwModal" aria-label="Close">
            <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
        </button>

        <div class="prof-modal-scroll">
            <div class="prof-modal-head">
                <div class="prof-section-icon"><i class="bi bi-key-fill"></i></div>
                <div>
                    <h2 class="prof-modal-title">Change Password</h2>
                    <p class="prof-modal-desc">Use a strong password of at least 8 characters.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="prof-modal-form">
                @csrf
                @method('PUT')

                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div>
                        <label class="prof-label" style="display:block;margin-bottom:6px;">
                            Current Password <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="password" name="current_password" class="form-input" autocomplete="current-password">
                        @if ($errors->updatePassword->has('current_password'))
                            <p class="form-error">{{ $errors->updatePassword->first('current_password') }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="prof-label" style="display:block;margin-bottom:6px;">
                            New Password <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="password" name="password" class="form-input" autocomplete="new-password">
                        @if ($errors->updatePassword->has('password'))
                            <p class="form-error">{{ $errors->updatePassword->first('password') }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="prof-label" style="display:block;margin-bottom:6px;">
                            Confirm New Password <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
                    </div>
                </div>

                <div class="prof-modal-foot">
                    <button type="button" class="btn btn-outline" id="cancelPwModal">Cancel</button>
                    <button type="submit" class="btn prof-submit-btn">
                        <i class="bi bi-key"></i>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════
     STYLES
     ══════════════════════════════════════════════════════════ --}}
<style>
/* ── Shell ──────────────────────────────────────────────── */
.prof-shell {
    max-width: none;
    width: auto;
    margin: clamp(12px, 1.5vw, 20px);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.prof-shell .card,
.prof-overlay .card {
    box-shadow: none;
}

/* ── Hero card ──────────────────────────────────────────── */
.prof-hero-card {
    position: sticky;
    top: clamp(10px, 1.25vw, 16px);
    z-index: 30;
    padding: 0;
    overflow: hidden;
}

.prof-banner {
    height: 64px;
    background: linear-gradient(130deg, var(--brand) 0%, #28456e 60%, #1e3557 100%);
}
html[data-theme='dark'] .prof-banner {
    background: linear-gradient(130deg, #182234 0%, #1b2f4a 100%);
}

.prof-hero-body {
    padding: 0 28px 20px;
    margin-top: -34px;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    column-gap: 20px;
    row-gap: 12px;
    align-items: center;
}

.prof-avatar-row { display: flex; justify-content: space-between; align-items: flex-end; }

.prof-avatar {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: linear-gradient(145deg, #7a3502 0%, #b06520 100%);
    color: #fff;
    font-size: 26px;
    font-family: var(--font-heading);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid var(--card);
    box-shadow: none;
    flex-shrink: 0;
    user-select: none;
    letter-spacing: 0;
}

.prof-identity {
    min-width: 0;
    padding-top: 34px;
}

.prof-name {
    font-size: 1.22rem;
    font-weight: 700;
    font-family: var(--font-heading);
    color: var(--ink);
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.prof-email {
    font-size: 13px;
    color: var(--ink-muted);
    margin-top: 5px;
}

.prof-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.prof-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    display: inline-block;
    flex-shrink: 0;
}

.prof-hero-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    padding-top: 34px;
    white-space: nowrap;
}

.prof-pw-btn {
    background: var(--brand-soft);
    border-color: transparent;
    color: var(--brand);
    font-weight: 600;
}
.prof-pw-btn:hover {
    background: var(--border);
    border-color: var(--border-strong);
    color: var(--ink);
}

/* ── Section cards ──────────────────────────────────────── */
.prof-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(340px, .9fr);
    gap: 20px;
    align-items: start;
}

.prof-side-stack {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.prof-section-card { padding: 0; overflow: hidden; }

.prof-section-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-panel);
}

.prof-section-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--brand-soft);
    color: var(--brand);
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.prof-section-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    font-family: var(--font-heading);
    margin: 0;
    flex: 1;
}

.prof-head-action { margin-left: auto; }

/* ── Field grid ─────────────────────────────────────────── */
.prof-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.prof-field {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
}

.prof-field:nth-child(odd):not(.prof-field-span) {
    border-right: 1px solid var(--border);
}

.prof-field-span {
    grid-column: 1 / -1;
}

.prof-fields-single {
    grid-template-columns: 1fr;
}

.prof-fields-single .prof-field {
    border-right: 0 !important;
}

.prof-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--ink-muted);
    margin: 0;
}

.prof-value {
    font-size: 14px;
    font-weight: 500;
    color: var(--ink);
    margin: 7px 0 0;
    line-height: 1.5;
}

/* ── Lock note ──────────────────────────────────────────── */
.prof-lock-note {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 13px 24px;
    border-top: 1px solid var(--border);
    background: var(--surface-panel);
    color: var(--ink-muted);
    font-size: 11.5px;
    line-height: 1.5;
}

/* ── Security row ───────────────────────────────────────── */
.prof-security-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 24px;
}

.prof-pw-dots {
    font-size: 18px;
    color: var(--ink-muted);
    letter-spacing: 4px;
    margin: 7px 0 0;
    line-height: 1;
}

/* ── Modal overlay ──────────────────────────────────────── */
.prof-overlay {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,.55);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    z-index: 1300;
    font-family: var(--font-body);
    opacity: 0;
    pointer-events: none;
    transition: opacity .18s ease;
}

.prof-overlay.is-open {
    opacity: 1;
    pointer-events: auto;
}

.prof-sheet {
    position: relative;
    width: 92vw;
    max-width: 500px;
    max-height: 92vh;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: none;
    overflow: hidden;
    transform-origin: center;
    transition: transform .18s ease, opacity .18s ease;
}

.prof-modal-scroll {
    overflow-y: auto;
    max-height: 92vh;
}

.prof-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 20;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: var(--card);
    border: 1px solid var(--border);
    color: var(--ink-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background .12s, color .12s;
    box-shadow: none;
}
.prof-modal-close:hover { background: var(--surface-muted); color: var(--ink); }

.prof-modal-head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 20px 22px 18px;
    border-bottom: 1px solid var(--border);
    padding-right: 56px;
    background: var(--surface-panel);
}

.prof-modal-title {
    font-size: 1.05rem;
    font-weight: 700;
    font-family: var(--font-heading);
    color: var(--ink);
    margin: 0;
    letter-spacing: -0.02em;
}

.prof-modal-desc {
    font-size: 12px;
    color: var(--ink-muted);
    margin: 3px 0 0;
}

.prof-modal-form { padding: 22px; }

.prof-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.prof-form-span { grid-column: 1 / -1; }

.prof-modal-foot {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
}

.prof-submit-btn {
    background: var(--brand-mid);
    border-color: var(--brand-mid);
    color: #fff;
}
.prof-submit-btn:hover {
    background: #7f4412;
    border-color: #7f4412;
    box-shadow: none;
}

/* ── Responsive ─────────────────────────────────────────── */
@media (max-width: 900px) {
    .prof-shell {
        max-width: none;
        width: auto;
        margin: 14px;
    }

    .prof-detail-grid {
        grid-template-columns: 1fr;
    }

    .prof-side-stack {
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: stretch;
    }
}

@media (max-width: 760px) {
    .prof-hero-body {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .prof-hero-actions {
        grid-column: 1 / -1;
        justify-content: flex-start;
        padding-top: 4px;
    }
}

@media (max-width: 700px) {
    .prof-side-stack {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 560px) {
    .prof-shell { gap: 14px; }
    .prof-fields { grid-template-columns: 1fr; }
    .prof-field:nth-child(odd):not(.prof-field-span) { border-right: none; }
    .prof-field { padding: 14px 18px; }
    .prof-section-head { padding: 13px 18px; }
    .prof-hero-body {
        padding: 0 18px 18px;
        grid-template-columns: 1fr;
        margin-top: -34px;
    }
    .prof-identity { padding-top: 0; }
    .prof-avatar { width: 68px; height: 68px; font-size: 24px; }
    .prof-form-grid { grid-template-columns: 1fr; }
    .prof-form-span { grid-column: 1; }
    .prof-security-row {
        grid-template-columns: 1fr;
        align-items: flex-start;
        gap: 14px;
        padding: 16px 18px;
    }
    .prof-hero-actions {
        flex-wrap: wrap;
        justify-content: flex-start;
        padding-top: 0;
    }
}
</style>


{{-- ══════════════════════════════════════════════════════════
     SCRIPTS
     ══════════════════════════════════════════════════════════ --}}
<script>
(() => {
    /* ── generic modal factory ──────────────────────────── */
    function makeModal(overlayId, sheetId, openIds, closeIds) {
        const overlay = document.getElementById(overlayId);
        const sheet   = document.getElementById(sheetId);
        if (!overlay || !sheet) return;
        let hideTimer = null;

        const lock   = () => { document.documentElement.classList.add('overflow-hidden'); document.body.classList.add('overflow-hidden'); };
        const unlock = () => { document.documentElement.classList.remove('overflow-hidden'); document.body.classList.remove('overflow-hidden'); };
        const resetUi = () => { document.dispatchEvent(new CustomEvent('panel-ui:reset')); };

        const show = () => {
            window.clearTimeout(hideTimer);
            resetUi();
            overlay.style.display = 'flex';
            lock();
            requestAnimationFrame(() => {
                overlay.classList.add('is-open');
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
            });
        };

        const hide = () => {
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            overlay.classList.remove('is-open');
            unlock();
            resetUi();
            hideTimer = window.setTimeout(() => { overlay.style.display = 'none'; }, 180);
        };

        openIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', show);
        });

        closeIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', hide);
        });

        overlay.addEventListener('click', e => { if (e.target === overlay) hide(); });

        return { show, hide, overlay };
    }

    /* ── Edit Profile modal ─────────────────────────────── */
    const editModal = makeModal(
        'editProfileOverlay', 'editProfileSheet',
        ['openEditModal', 'openEditModal2'],
        ['closeEditModal', 'cancelEditModal']
    );

    /* ── Change Password modal ──────────────────────────── */
    const pwModal = makeModal(
        'changePwOverlay', 'changePwSheet',
        ['openPwModal', 'openPwModal2'],
        ['closePwModal', 'cancelPwModal']
    );

    /* ── Escape key closes whichever modal is open ──────── */
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (editModal?.overlay.style.display === 'flex') editModal.hide();
        if (pwModal?.overlay.style.display === 'flex')   pwModal.hide();
    });

    /* ── Auto-open on validation errors ────────────────── */
    @if ($errors->updatePassword->any())
        pwModal?.show();
    @elseif ($errors->any())
        editModal?.show();
    @endif
})();
</script>
@endsection
