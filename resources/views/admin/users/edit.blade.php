@extends('layouts.panel')

@section('page_title','Edit User')
@section('page_desc', 'Update user profile details, roles, and credentials.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $returnTo = old('return_to', request('return_to', route('admin.users.index')));
    $hasSplitUserNames = \Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name')
        && \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name');
    $hasMiddleName = \Illuminate\Support\Facades\Schema::hasColumn('users', 'middle_name');
    $hasSuffix = \Illuminate\Support\Facades\Schema::hasColumn('users', 'suffix');
    $fallbackNameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $fallbackFirstName = $fallbackNameParts[0] ?? '';
    $fallbackLastName = count($fallbackNameParts) > 1 ? end($fallbackNameParts) : '';

    $branchesWithActiveBranchAdmin = collect($branchesWithActiveBranchAdmin ?? [])
        ->map(fn ($id) => (int) $id)
        ->all();

    $mainBranchId = (int) ($mainBranchId ?? auth()->user()?->branch_id);
    $isMainBranchAdmin = auth()->user()?->isMainBranchAdmin();
    $isBranchAdmin = auth()->user()?->role === 'admin' && ! auth()->user()?->isMainBranchAdmin();
    $isEditingStaff = $user->role === 'staff';
    $isEditingBranchAdmin = $user->role === 'admin' && $user->admin_scope === 'branch';
    $isEditingMainAdmin = $user->isMainBranchAdmin();
    $selectedBranchId = (int) old('branch_id', $user->branch_id);
    $shouldLockBranch = $isEditingMainAdmin || $isEditingStaff || $isBranchAdmin;
@endphp
<style>
.user-edit-page {
    min-height:calc(100vh - 1rem);
    background:
        linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
        repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
        #C4D2BE;
    background-size:44px 44px,44px 44px,16px 16px,auto;
}
.user-edit-toast {
    position:fixed;
    top:1rem;
    right:1rem;
    z-index:1200;
    display:flex;
    align-items:center;
    gap:.55rem;
    max-width:calc(100vw - 2rem);
    border:1px solid #8EA083;
    border-radius:.75rem;
    background:#2F3A2E;
    color:#F7FAF3;
    padding:.72rem .9rem;
    font-size:.88rem;
    font-weight:650;
    line-height:1.35;
    box-shadow:none !important;
    pointer-events:none;
    animation:userEditToastIn .18s ease-out, userEditToastOut .22s ease-in 3.8s forwards;
}
@keyframes userEditToastIn {
    from { opacity:0; transform:translateY(-.35rem); }
    to { opacity:1; transform:translateY(0); }
}
@keyframes userEditToastOut {
    to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
}
.user-edit-page .modal-shell-card {
    border:1px solid #B5C4AD !important;
    border-radius:.85rem !important;
    background:#D3DEC9 !important;
    box-shadow:none !important;
}
.user-edit-page .modal-shell-card > .border-b,
.user-edit-page .modal-shell-card > form > .border-t,
.user-edit-page .modal-shell-card > .border-t {
    border-color:#B5C4AD !important;
    background:#D3DEC9 !important;
}
.user-edit-page .modal-shell-card h2 {
    color:var(--ink) !important;
    font-size:1.35rem !important;
    font-weight:780 !important;
}
.user-edit-page .modal-shell-card p,
.user-edit-page .form-hint {
    color:#566653 !important;
    font-weight:620;
}
.user-edit-page .modal-shell-card .rounded-xl.border {
    border-color:#B5C4AD !important;
    background:#E1E7D9 !important;
    box-shadow:none !important;
}
.user-edit-page .modal-shell-card h3 {
    color:#3E4A3D !important;
    font-size:.78rem !important;
    font-weight:720 !important;
    letter-spacing:.06em;
}
.user-edit-page .label-section {
    color:#566653;
    font-size:.76rem;
    font-weight:680;
}
.user-edit-page .form-input,
.user-edit-page .form-select {
    min-height:2.65rem;
    border:1px solid #B5C4AD;
    border-radius:.75rem;
    background:#F7FAF3;
    color:var(--ink);
    box-shadow:none !important;
}
.user-edit-page .form-input:focus,
.user-edit-page .form-select:focus {
    background:#fff;
    border-color:#8EA083;
    box-shadow:none !important;
}
.user-edit-page .btn-outline {
    min-height:2.65rem;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid #B5C4AD;
    border-radius:.75rem;
    background:#E1E7D9;
    color:#3E4A3D;
    padding:0 .95rem;
    font-weight:700;
    box-shadow:none !important;
}
.user-edit-page .btn-outline:hover {
    background:#C7D5BE;
    border-color:#8EA083;
    color:var(--ink);
}
.user-edit-page .btn-primary-custom {
    min-height:2.65rem;
    border-radius:.75rem;
    background:#344333 !important;
    border-color:#344333 !important;
    box-shadow:none !important;
}
.user-edit-page .btn-primary-custom:hover {
    background:#2F3A2E !important;
    border-color:#2F3A2E !important;
}
.user-edit-badge {
    border:1px solid #B5C4AD;
    border-radius:999px;
    background:#E1E7D9;
    color:#3E4A3D;
    padding:.35rem .7rem;
    font-size:.76rem;
    font-weight:720;
}
</style>

<div class="user-edit-page w-full min-h-screen pt-4 pb-8 px-4 sm:px-6 lg:px-8 flex justify-center font-ui-body">
    <div class="w-full max-w-5xl space-y-5">
        <div class="user-edit-toast no-print" role="status" aria-live="polite">
            <i class="bi bi-person-gear"></i>
            <span>You are editing a user account.</span>
        </div>
        <form id="userEditForm" method="POST" action="{{ route('admin.users.update', $user) }}" class="w-full">
            @csrf
            @method('PUT')

            <input type="hidden" name="return_to" value="{{ $returnTo }}">

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <div class="font-semibold">User was not saved. Please fix the following:</div>
                    <ul class="mt-1 list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="modal-shell-card max-w-5xl mx-auto rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 sm:px-8 py-5 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-semibold text-slate-900">Account details</h2>
                            <p class="text-sm text-slate-500 mt-1">Update the user profile, role access, and contact information.</p>
                        </div>
                        <span class="user-edit-badge inline-flex items-center">
                            User ID: {{ $user->id }}
                        </span>
                    </div>
                </div>

                <div class="p-5 sm:p-6 space-y-5">
                    <div class="rounded-xl border border-slate-200 p-6 space-y-5">
                        <div>
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Information</h3>
                            <p class="text-sm mt-1">Review the user name and email used for login.</p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            @if($hasSplitUserNames)
                                <div>
                                    <label class="label-section">First Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name ?: $fallbackFirstName) }}" class="form-input" placeholder="Juan" required>
                                    @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                                    <div class="form-error hidden" data-field-error="first_name"></div>
                                </div>

                                @if($hasMiddleName)
                                    <div>
                                        <label class="label-section">Middle Name</label>
                                        <input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" class="form-input" placeholder="Santos">
                                        @error('middle_name') <div class="form-error">{{ $message }}</div> @enderror
                                        <div class="form-error hidden" data-field-error="middle_name"></div>
                                    </div>
                                @endif

                                <div>
                                    <label class="label-section">Last Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name ?: $fallbackLastName) }}" class="form-input" placeholder="Dela Cruz" required>
                                    @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                                    <div class="form-error hidden" data-field-error="last_name"></div>
                                </div>

                                @if($hasSuffix)
                                    <div>
                                        <label class="label-section">Suffix</label>
                                        <select name="suffix" class="form-select">
                                            <option value="">Select suffix</option>
                                            @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffix)
                                                <option value="{{ $suffix }}" {{ old('suffix', $user->suffix) === $suffix ? 'selected' : '' }}>{{ $suffix }}</option>
                                            @endforeach
                                        </select>
                                        @error('suffix') <div class="form-error">{{ $message }}</div> @enderror
                                    </div>
                                @endif
                            @else
                                <div>
                                    <label class="label-section">Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" placeholder="Juan Dela Cruz" required>
                                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                                    <div class="form-error hidden" data-field-error="name"></div>
                                </div>
                            @endif

                            <div>
                                <label class="label-section">Email <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" placeholder="user@example.com" required>
                                @error('email') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" data-field-error="email"></div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-6 space-y-5">
                        <div>
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Role & Access</h3>
                            <p class="text-sm mt-1">Control the user role and assigned branch scope.</p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="label-section">Role <span class="text-rose-500">*</span></label>
                                <select name="role" id="role" class="form-select" required {{ $isEditingMainAdmin ? 'disabled' : '' }}>
                                    @if($isMainBranchAdmin)
                                        <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Branch Admin</option>
                                    @elseif($isBranchAdmin)
                                        <option value="staff" selected>Staff</option>
                                    @endif
                                </select>

                                @if($isEditingMainAdmin)
                                    <input type="hidden" name="role" value="admin">
                                @endif

                                @error('role') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" data-field-error="role"></div>
                                <div class="form-hint mt-1">
                                    @if($isEditingMainAdmin)
                                        Main Branch Administrator access is preserved and cannot be reassigned here.
                                    @elseif($isEditingBranchAdmin)
                                        Branch Admin role changes to Staff will be rejected by the system.
                                    @elseif($isBranchAdmin)
                                        Branch Admins can only manage Staff accounts.
                                    @else
                                        Admin accounts managed here remain Branch Admins.
                                    @endif
                                </div>
                            </div>

                            <div>
                                <label class="label-section">Branch</label>
                                <select name="branch_id" id="branch_id" class="form-select" {{ $shouldLockBranch ? 'disabled' : '' }}>
                                    <option value="">- Select Branch -</option>
                                    @foreach($branches as $branch)
                                        @php
                                            $branchId = (int) $branch->id;
                                            $isMainBranch = $branchId === $mainBranchId;
                                            $hasOtherActiveBranchAdmin = in_array($branchId, $branchesWithActiveBranchAdmin, true);
                                            $isCurrentSelectedBranch = $selectedBranchId === $branchId;
                                        @endphp
                                        <option value="{{ $branch->id }}" {{ $isCurrentSelectedBranch ? 'selected' : '' }}>
                                            {{ $branch->branch_name }}
                                            @if($isMainBranch && $isEditingBranchAdmin)
                                                - Main Branch not allowed for Branch Admin
                                            @elseif($hasOtherActiveBranchAdmin && ! $isCurrentSelectedBranch && $isEditingBranchAdmin)
                                                - already has Branch Admin
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                                @if($shouldLockBranch)
                                    <input type="hidden" name="branch_id" value="{{ $user->branch_id }}">
                                @endif

                                @error('branch_id') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" data-field-error="branch_id"></div>
                                <div id="branch_hint" class="form-hint mt-1">
                                    @if($isEditingStaff)
                                        Staff remain auto-assigned to the current branch and cannot be reassigned here.
                                    @elseif($isEditingBranchAdmin)
                                        Branch Admin can only be assigned to a non-main branch without an active Branch Admin.
                                    @elseif($isEditingMainAdmin)
                                        Main Branch Admin branch assignment cannot be changed here.
                                    @else
                                        Branch is required for user accounts.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-6 space-y-5">
                        <div>
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact Information</h3>
                            <p class="text-sm mt-1">Optional details for internal user records.</p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div id="contact_wrap">
                                <label class="label-section">Contact Number</label>
                                <input type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" class="form-input" inputmode="tel" placeholder="+63 9XX XXX XXXX">
                                @error('contact_number') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" data-field-error="contact_number"></div>
                            </div>

                            <div id="position_wrap">
                                <label class="label-section">Position</label>
                                <select name="position" id="position" class="form-select" data-selected="{{ old('position', $user->position) }}">
                                    <option value="">Select position</option>
                                </select>
                                @error('position') <div class="form-error">{{ $message }}</div> @enderror
                            </div>

                            <div id="address_wrap" class="md:col-span-2">
                                <label class="label-section">Address</label>
                                <input type="text" name="address" value="{{ old('address', $user->address) }}" class="form-input" placeholder="House No., Street, Barangay, City">
                                @error('address') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" data-field-error="address"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-5 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
                    <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] text-white px-6">
                        <i class="bi bi-save2"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </form>

        <div class="modal-shell-card max-w-5xl mx-auto rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 sm:px-8 py-5 border-b border-slate-200 bg-slate-50">
                <h2 class="text-2xl font-semibold text-slate-900">Password reset</h2>
                <p class="text-sm text-slate-500 mt-1">Optional password update for this account.</p>
            </div>

            <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}">
                @csrf
                @method('PATCH')

                <input type="hidden" name="return_to" value="{{ $returnTo }}">

                <div class="p-5 sm:p-6">
                    <div class="rounded-xl border border-slate-200 p-6">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="label-section">New Password</label>
                                <input type="password" name="password" class="form-input">
                                @error('password') <div class="form-error">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="label-section">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-5 border-t border-slate-200 flex justify-end bg-slate-50">
                    <button type="submit" class="btn btn-outline px-5">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const roleSelect = document.getElementById('role');
        const branchSelect = document.getElementById('branch_id');
        const form = document.getElementById('userEditForm');
        const positionSelect = document.getElementById('position');
        const branchHint = document.getElementById('branch_hint');
        const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];
        const positions = {
            staff: ['Staff', 'Encoder', 'Cashier', 'Branch Staff', 'Funeral Assistant'],
            admin: ['Branch Admin', 'Branch Manager', 'Office Admin'],
        };
        const normalizeText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
        const hasLetter = (value) => /[\p{L}\p{M}]/u.test(value);
        const isValidName = (value) => /^[\p{L}\p{M}\s.'-]+$/u.test(value) && !/\d/.test(value);
        const isValidPhilippineMobile = (value) => {
            const normalized = String(value || '').replace(/[\s()-]/g, '');
            return !normalized || /^(\+639|639|09)\d{9}$/.test(normalized);
        };
        const showFieldError = (field, message) => {
            const input = form?.querySelector(`[name="${field}"]`);
            const error = form?.querySelector(`[data-field-error="${field}"]`);
            if (input) input.classList.add(...invalidClass);
            if (error) {
                error.textContent = message;
                error.classList.remove('hidden');
            }
        };
        const clearFieldError = (field) => {
            const input = form?.querySelector(`[name="${field}"]`);
            const error = form?.querySelector(`[data-field-error="${field}"]`);
            if (input) input.classList.remove(...invalidClass);
            if (error) {
                error.textContent = '';
                error.classList.add('hidden');
            }
        };

        function syncPositions() {
            if (!positionSelect || !roleSelect) return;
            const selected = positionSelect.dataset.selected || positionSelect.value;
            const options = positions[roleSelect.value] || [];
            positionSelect.innerHTML = '<option value="">Select position</option>';
            options.forEach((label) => {
                const option = document.createElement('option');
                option.value = label;
                option.textContent = label;
                option.selected = label === selected;
                positionSelect.appendChild(option);
            });
            positionSelect.dataset.selected = '';
        }

        function syncRoleState() {
            if (!roleSelect || !branchSelect) {
                syncPositions();
                return;
            }

            const isStaff = roleSelect.value === 'staff';
            const isAdmin = roleSelect.value === 'admin';
            const branchWasLockedByServer = branchSelect.hasAttribute('disabled');

            if (isStaff) {
                branchSelect.required = false;
                if (branchHint) {
                    branchHint.textContent = 'Staff branch assignment is locked and automatically assigned by the system.';
                }
            } else if (isAdmin) {
                branchSelect.required = !branchWasLockedByServer;
                if (branchHint) {
                    branchHint.textContent = 'Branch Admin can only be assigned to a non-main branch without an active Branch Admin.';
                }
            }

            syncPositions();
        }

        function validateForm(event) {
            let valid = true;
            ['first_name', 'middle_name', 'last_name', 'name', 'email', 'branch_id', 'contact_number', 'address'].forEach(clearFieldError);
            form?.querySelectorAll('input[type="text"], input[type="email"]').forEach((input) => {
                input.value = normalizeText(input.value);
            });

            ['first_name', 'middle_name', 'last_name', 'name'].forEach((field) => {
                const input = form?.querySelector(`[name="${field}"]`);
                if (!input) return;
                const value = normalizeText(input.value);
                if (input.required && !value) {
                    valid = false;
                    showFieldError(field, `${input.closest('div')?.querySelector('label')?.textContent.replace('*', '').trim() || 'Name'} is required.`);
                } else if (value && (!hasLetter(value) || !isValidName(value))) {
                    valid = false;
                    showFieldError(field, 'Name fields may only contain letters, spaces, hyphen, apostrophe, period, enye, and accented letters.');
                }
            });

            const parts = ['first_name', 'middle_name', 'last_name', 'suffix']
                .map((field) => normalizeText(form?.querySelector(`[name="${field}"]`)?.value || '').toLowerCase())
                .filter(Boolean);
            if (new Set(parts).size !== parts.length) {
                valid = false;
                showFieldError('last_name', 'Name parts must not be exact duplicates.');
            }

            const email = form?.querySelector('[name="email"]');
            if (email && !email.validity.valid) {
                valid = false;
                showFieldError('email', 'Enter a valid email address.');
            }

            if (branchSelect && roleSelect?.value === 'admin' && !branchSelect.disabled && !branchSelect.value) {
                valid = false;
                showFieldError('branch_id', 'Branch is required for branch admin accounts.');
            }

            const contact = form?.querySelector('[name="contact_number"]');
            if (contact && !isValidPhilippineMobile(contact.value)) {
                valid = false;
                showFieldError('contact_number', 'Enter a valid Philippine mobile number.');
            }

            if (!valid) event.preventDefault();
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', syncRoleState);
        }

        if (form) {
            form.addEventListener('submit', validateForm);
            form.addEventListener('input', (event) => {
                if (event.target?.name) clearFieldError(event.target.name);
            });
        }

        syncRoleState();
    })();
</script>
@endsection
