@extends('layouts.panel')

@section('page_title','Create User')
@section('page_desc', 'Create a new system user and assign role access.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $returnTo = old('return_to', request('return_to', route('admin.users.index')));
    $hasSplitUserNames = \Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name')
        && \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name');
    $hasMiddleName = \Illuminate\Support\Facades\Schema::hasColumn('users', 'middle_name');
    $hasSuffix = \Illuminate\Support\Facades\Schema::hasColumn('users', 'suffix');
    $isSystemAdmin = auth()->user()?->isSystemAdmin();
    $isBranchAdmin = auth()->user()?->isBranchAdmin();
    $selectedRole = old('role', 'staff');
    $selectedBranchId = $isBranchAdmin
        ? (int) auth()->user()?->branch_id
        : (int) old('branch_id');
    $assignedBranch = $isBranchAdmin
        ? $branches->firstWhere('id', $selectedBranchId)
        : null;
@endphp
<style>
.user-create-page {
    min-height:calc(100vh - 1rem);
    background:
        linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
        repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
        #C4D2BE;
    background-size:44px 44px,44px 44px,16px 16px,auto;
}
.user-create-toast {
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
    animation:managementToastIn .18s ease-out, managementToastOut .22s ease-in 3.8s forwards;
}
@keyframes managementToastIn {
    from { opacity:0; transform:translateY(-.35rem); }
    to { opacity:1; transform:translateY(0); }
}
@keyframes managementToastOut {
    to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
}
.user-create-shell {
    border:1px solid #B5C4AD !important;
    border-radius:.85rem !important;
    background:#D3DEC9 !important;
    box-shadow:none !important;
    overflow:hidden;
}
.user-create-head,
.user-create-footer {
    border-color:#B5C4AD !important;
    background:#D3DEC9 !important;
}
.user-create-head h2 {
    color:var(--ink);
    font-size:1.35rem;
    font-weight:780;
    letter-spacing:0;
}
.user-create-head p,
.user-create-section p,
.user-create-note {
    color:#566653;
    font-weight:620;
}
.user-create-badge {
    border:1px solid #B5C4AD;
    border-radius:999px;
    background:#E1E7D9;
    color:#3E4A3D;
    padding:.35rem .7rem;
    font-size:.76rem;
    font-weight:720;
}
.user-create-section {
    border:1px solid #B5C4AD !important;
    border-radius:.85rem !important;
    background:#E1E7D9 !important;
    box-shadow:none !important;
}
.user-create-section h3 {
    color:#3E4A3D !important;
    font-size:.78rem !important;
    font-weight:720 !important;
    letter-spacing:.06em;
}
.user-create-page .form-input,
.user-create-page .form-select {
    min-height:2.65rem;
    border:1px solid #B5C4AD;
    border-radius:.75rem;
    background:#F7FAF3;
    color:var(--ink);
    box-shadow:none !important;
}
.user-create-page .form-select {
    cursor:pointer;
}
.user-create-page .form-input:focus,
.user-create-page .form-select:focus {
    background:#fff;
    border-color:#8EA083;
    box-shadow:none !important;
}
.password-field-shell {
    display:flex;
    align-items:stretch;
    gap:.5rem;
}
.password-field-shell .form-input {
    min-width:0;
    flex:1 1 auto;
}
.password-field-actions {
    display:flex;
    flex:0 0 auto;
    gap:.35rem;
}
.password-field-action {
    width:2.65rem;
    min-height:2.65rem;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid #B5C4AD;
    border-radius:.75rem;
    background:#F7FAF3;
    color:#3E4A3D;
    cursor:pointer;
}
.password-field-action:hover,
.password-field-action:focus-visible {
    border-color:#8EA083;
    background:#fff;
    color:var(--ink);
    outline:none;
}
.password-field-action:disabled {
    cursor:not-allowed;
    opacity:.5;
}
.user-create-page .label-section {
    color:#566653;
    font-size:.76rem;
    font-weight:680;
}
.user-create-page .form-hint {
    color:#566653;
    font-weight:620;
}
.user-create-page .btn-outline {
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
.user-create-page .btn-outline:hover {
    background:#C7D5BE;
    border-color:#8EA083;
    color:var(--ink);
}
.user-create-page .btn-primary-custom {
    min-height:2.65rem;
    border-radius:.75rem;
    background:#344333 !important;
    border-color:#344333 !important;
    box-shadow:none !important;
}
.user-create-page .btn-primary-custom:hover {
    background:#2F3A2E !important;
    border-color:#2F3A2E !important;
}
@media (max-width:768px) {
    .user-create-footer {
        display:grid !important;
    }
}
</style>
<form id="userCreateForm" method="POST" action="{{ route('admin.users.store') }}" class="w-full font-ui-body">
@csrf
<div class="user-create-page w-full min-h-screen pt-4 pb-8 px-4 sm:px-6 lg:px-8">

<div class="w-full max-w-5xl mx-auto">

<div class="user-create-toast no-print" role="status" aria-live="polite" data-page-context-toast>
    <i class="bi bi-person-plus"></i>
    <span>You are adding a new user account.</span>
</div>

<input type="hidden" name="return_to" value="{{ $returnTo }}">

@if ($errors->any())
<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
    <div class="font-semibold">Please fix the following errors:</div>
    <ul class="mt-1 list-disc pl-5 space-y-1">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="modal-shell-card user-create-shell max-w-5xl mx-auto">

    <!-- HEADER -->
    <div class="user-create-head px-6 sm:px-8 py-5 border-b">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2>New account details</h2>
                <p class="text-sm mt-1">Enter account, access, and contact information for the user.</p>
            </div>
            <span class="user-create-badge inline-flex items-center">
                New Account
            </span>
        </div>
    </div>

    <div class="p-5 sm:p-6 space-y-5">

        <!-- ACCOUNT CARD -->
        <div class="user-create-section rounded-xl border p-5 space-y-5">
            <div>
                <h3 class="text-xs uppercase">Account Information</h3>
                <p class="user-create-note text-sm mt-1">Use the name and login credentials assigned to the user.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">

                @if($hasSplitUserNames)
                <div>
                    <label class="label-section">First Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-input" placeholder="Juan" autocomplete="off" autocapitalize="words" data-name-case required>
                    @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="first_name"></div>
                </div>

                @if($hasMiddleName)
                <div>
                    <label class="label-section">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-input" placeholder="Santos" autocomplete="off" autocapitalize="words" data-name-case>
                    @error('middle_name') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="middle_name"></div>
                </div>
                @endif

                <div>
                    <label class="label-section">Last Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-input" placeholder="Dela Cruz" autocomplete="off" autocapitalize="words" data-name-case required>
                    @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="last_name"></div>
                </div>

                @if($hasSuffix)
                <div>
                    <label class="label-section">Suffix</label>
                    <select name="suffix" class="form-select">
                        <option value="">Select suffix</option>
                        @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffix)
                            <option value="{{ $suffix }}" {{ old('suffix') === $suffix ? 'selected' : '' }}>{{ $suffix }}</option>
                        @endforeach
                    </select>
                    @error('suffix') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                @endif
                @else
                <div>
                    <label class="label-section">Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="Juan Dela Cruz" autocapitalize="words" data-name-case required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="name"></div>
                </div>
                @endif

                <div>
                    <label class="label-section">Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="user@example.com" required autocomplete="off">
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="email"></div>
                </div>

                <div>
                    <label for="password" class="label-section">Password <span class="text-rose-500">*</span></label>
                    <div class="password-field-shell">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input"
                            placeholder="Generated automatically"
                            required
                            minlength="6"
                            autocomplete="new-password"
                            autocapitalize="off"
                            spellcheck="false"
                        >
                        <div class="password-field-actions">
                            <button type="button" id="togglePassword" class="password-field-action" aria-label="Show password" aria-pressed="false" title="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="copyPassword" class="password-field-action" aria-label="Copy password" title="Copy password">
                                <i class="bi bi-copy" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="generatePassword" class="password-field-action" aria-label="Generate new password" title="Generate new password">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="password"></div>
                    <div id="passwordFeedback" class="form-hint mt-1" role="status" aria-live="polite">
                        A secure password is generated automatically. Copy it before saving.
                    </div>
                </div>

            </div>
        </div>

        <!-- ROLE CARD -->
        <div class="user-create-section rounded-xl border p-5 space-y-5">
            <div>
                <h3 class="text-xs uppercase">Role & Access</h3>
                <p class="user-create-note text-sm mt-1">
                    {{ $isBranchAdmin ? 'New users are automatically created as Staff for your branch.' : 'Choose the user role and the branch scope that applies.' }}
                </p>
            </div>

            @if($isBranchAdmin)
                <div class="grid gap-4 sm:grid-cols-2" aria-label="Assigned account access">
                    <div class="rounded-xl border border-[#B5C4AD] bg-[#F7FAF3] px-4 py-3">
                        <div class="label-section">Assigned Role</div>
                        <div class="mt-1 font-semibold text-[var(--ink)]">Staff</div>
                    </div>
                    <div class="rounded-xl border border-[#B5C4AD] bg-[#F7FAF3] px-4 py-3">
                        <div class="label-section">Assigned Branch</div>
                        <div class="mt-1 font-semibold text-[var(--ink)]">
                            {{ $assignedBranch?->branch_code }} - {{ $assignedBranch?->branch_name ?? 'Current Branch' }}
                        </div>
                    </div>
                </div>
                <input type="hidden" name="role" value="staff">
                <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
            @else
            <div class="grid gap-5 md:grid-cols-2">

                <div>
                    <label class="label-section">Role <span class="text-rose-500">*</span></label>
                    <select id="role" class="form-select" required>
                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                        @if($isSystemAdmin)
                            <option value="branch_admin" {{ old('role') == 'branch_admin' || (old('role') == 'admin' && old('admin_scope') !== 'system') ? 'selected' : '' }}>Branch Admin</option>
                            <option value="system_admin" {{ old('role') == 'system_admin' || (old('role') == 'admin' && old('admin_scope') === 'system') ? 'selected' : '' }}>System Admin</option>
                        @endif
                    </select>

                    <input type="hidden" name="role" id="roleValue" value="{{ $selectedRole }}">

                    @error('role') <div class="form-error">{{ $message }}</div> @enderror

                    <div class="form-hint" id="role">
                       
                    </div>
                </div>

                <div>
                    <label class="label-section">Branch</label>
                    <select id="branch_id" class="form-select">
                        <option value="">- Select Branch -</option>

                        @foreach($branches as $branch)
                            @php
                                $branchId = (int) $branch->id;
                                $selected = $selectedBranchId === $branchId;
                            @endphp

                            <option
                                value="{{ $branch->id }}"
                                {{ $selected ? 'selected' : '' }}
                            >
                                {{ $branch->branch_code }} - {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="hidden" name="branch_id" id="branchValue" value="{{ $selectedBranchId ?: '' }}">

                    @error('branch_id') <div class="form-error">{{ $message }}</div> @enderror

                    <div class="form-hint" id="branchHint"></div>
                </div>

            </div>
            @endif
        </div>

        <!-- PERSONAL CARD -->
        <div class="user-create-section rounded-xl border p-5 space-y-5">
            <div>
                <h3 class="text-xs uppercase">Contact Information</h3>
                <p class="user-create-note text-sm mt-1">Optional details shown in user records and internal references.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">

                <div>
                    <label class="label-section">Contact Number</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="form-input" placeholder="+63 9XX XXX XXXX">
                    @error('contact_number') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="label-section">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-input" placeholder="House No., Street, Barangay, City">
                    @error('address') <div class="form-error">{{ $message }}</div> @enderror
                </div>

            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="user-create-footer px-5 sm:px-8 py-5 border-t flex justify-end gap-3">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom text-white px-6">
            <i class="bi bi-save2"></i>
            Save User
        </button>
    </div>

</div>

</div>
</div>
<script>
    (function () {
        const form = document.getElementById('userCreateForm');
        const roleSelect = document.getElementById('role');
        const roleValue = document.getElementById('roleValue');
        const branchSelect = document.getElementById('branch_id');
        const branchValue = document.getElementById('branchValue');
        const branchHint = document.getElementById('branchHint');
        const passwordInput = document.getElementById('password');
        const togglePasswordButton = document.getElementById('togglePassword');
        const copyPasswordButton = document.getElementById('copyPassword');
        const generatePasswordButton = document.getElementById('generatePassword');
        const passwordFeedback = document.getElementById('passwordFeedback');
        const creatorIsBranchAdmin = @json($isBranchAdmin);
        const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];

        const normalizeText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
        const normalizeLowercaseName = (value) => {
            const normalized = normalizeText(value);
            const particles = new Set(['da', 'das', 'de', 'del', 'do', 'dos', 'la', 'las', 'los', 'van', 'von']);
            let wordIndex = 0;

            return normalized.replace(/\p{L}[\p{L}\p{M}]*/gu, (word) => {
                const lowercaseWord = word.toLocaleLowerCase();

                if (word !== lowercaseWord) {
                    wordIndex += 1;
                    return word;
                }

                const normalizedWord = wordIndex > 0 && particles.has(lowercaseWord)
                    ? lowercaseWord
                    : `${word.charAt(0).toLocaleUpperCase()}${word.slice(1)}`;
                wordIndex += 1;

                return normalizedWord;
            });
        };
        const hasLetter = (value) => /[\p{L}\p{M}]/u.test(value);
        const isValidName = (value) => /^[\p{L}\p{M}\s.'-]+$/u.test(value) && !/\d/.test(value);
        const isValidPhilippineMobile = (value) => {
            const normalized = String(value || '').replace(/[\s()-]/g, '');
            return !normalized || /^(\+639|639|09)\d{9}$/.test(normalized);
        };
        const setPasswordFeedback = (message) => {
            if (passwordFeedback) passwordFeedback.textContent = message;
        };
        const secureRandomIndex = (maxExclusive) => {
            if (!window.crypto?.getRandomValues || maxExclusive < 1) return null;

            const values = new Uint32Array(1);
            const range = 0x100000000;
            const limit = range - (range % maxExclusive);

            do {
                window.crypto.getRandomValues(values);
            } while (values[0] >= limit);

            return values[0] % maxExclusive;
        };
        const pickSecureCharacter = (characters) => {
            const index = secureRandomIndex(characters.length);
            return index === null ? null : characters[index];
        };
        const generateSecurePassword = (length = 14) => {
            const groups = [
                'ABCDEFGHJKLMNPQRSTUVWXYZ',
                'abcdefghijkmnopqrstuvwxyz',
                '23456789',
                '!@#$%&*?',
            ];
            const allCharacters = groups.join('');
            const generated = groups.map(pickSecureCharacter);

            if (generated.some((character) => character === null)) return null;

            while (generated.length < length) {
                const character = pickSecureCharacter(allCharacters);
                if (character === null) return null;
                generated.push(character);
            }

            for (let index = generated.length - 1; index > 0; index -= 1) {
                const swapIndex = secureRandomIndex(index + 1);
                if (swapIndex === null) return null;
                [generated[index], generated[swapIndex]] = [generated[swapIndex], generated[index]];
            }

            return generated.join('');
        };
        const fillGeneratedPassword = () => {
            if (!passwordInput) return false;

            const generatedPassword = generateSecurePassword();
            if (!generatedPassword) {
                if (generatePasswordButton) generatePasswordButton.disabled = true;
                setPasswordFeedback('Secure password generation is unavailable. Enter a password manually.');
                return false;
            }

            passwordInput.value = generatedPassword;
            clearFieldError('password');
            setPasswordFeedback('A new secure password was generated.');
            return true;
        };
        const copyGeneratedPassword = async () => {
            if (!passwordInput) return;
            if (!passwordInput.value && !fillGeneratedPassword()) return;

            try {
                if (navigator.clipboard?.writeText && window.isSecureContext) {
                    await navigator.clipboard.writeText(passwordInput.value);
                } else {
                    const originalType = passwordInput.type;
                    passwordInput.type = 'text';
                    passwordInput.select();
                    const copied = document.execCommand('copy');
                    passwordInput.setSelectionRange(0, 0);
                    passwordInput.type = originalType;
                    if (!copied) throw new Error('Copy command failed.');
                }
                setPasswordFeedback('Password copied to clipboard.');
            } catch (error) {
                setPasswordFeedback('Password could not be copied. Select and copy it manually.');
                passwordInput.focus();
                passwordInput.select();
            }
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

        function sync() {
            if (!roleSelect || !branchSelect) return;

            if (roleValue) roleValue.value = roleSelect.value;

            if (creatorIsBranchAdmin) {
                branchSelect.disabled = true;
                branchSelect.required = false;
                if (branchValue) branchValue.value = branchSelect.value;
                if (branchHint) {
                    branchHint.textContent = 'Staff is automatically assigned to your current branch.';
                }
                return;
            }

            const isSystemAdmin = roleSelect.value === 'system_admin';
            const isBranchAdmin = ['branch_admin', 'admin'].includes(roleSelect.value);

            if (isSystemAdmin) {
                branchSelect.value = '';
                if (branchValue) branchValue.value = '';
                branchSelect.disabled = true;
                branchSelect.required = false;
                if (branchHint) {
                    branchHint.textContent = 'System Admin has access to all branches; no branch assignment is required.';
                }
            } else {
                branchSelect.disabled = false;
                branchSelect.required = true;
                if (branchValue) branchValue.value = branchSelect.value;
                if (branchHint) {
                    branchHint.textContent = isBranchAdmin
                        ? 'Select any active branch, including the Main Branch, without another active Branch Admin.'
                        : 'Select the branch where this Staff account will work.';
                }
            }
        }

        function validateForm(event) {
            if (!form) return true;
            let valid = true;
            const fields = ['first_name', 'middle_name', 'last_name', 'name', 'email', 'password', 'branch_id', 'contact_number', 'address'];
            fields.forEach(clearFieldError);

            form.querySelectorAll('[data-name-case]').forEach((input) => {
                input.value = normalizeLowercaseName(input.value);
            });

            form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]').forEach((input) => {
                input.value = normalizeText(input.value);
            });

            ['first_name', 'middle_name', 'last_name', 'name'].forEach((field) => {
                const input = form.querySelector(`[name="${field}"]`);
                if (!input) return;
                const value = normalizeText(input.value);
                if (input.required && !value) {
                    valid = false;
                    showFieldError(field, `${input.closest('div')?.querySelector('label')?.textContent.replace('*', '').trim() || 'Name'} is required.`);
                } else if (value && (!hasLetter(value) || !isValidName(value))) {
                    valid = false;
                    showFieldError(field, 'Name fields may only contain letters, spaces, hyphen, apostrophe, period, ñ, Ñ, and accented letters.');
                }
            });

            const parts = ['first_name', 'middle_name', 'last_name', 'suffix']
                .map((field) => normalizeText(form.querySelector(`[name="${field}"]`)?.value || '').toLowerCase())
                .filter(Boolean);
            if (new Set(parts).size !== parts.length) {
                valid = false;
                showFieldError('last_name', 'Name parts must not be exact duplicates.');
            }

            const email = form.querySelector('[name="email"]');
            if (email && !email.validity.valid) {
                valid = false;
                showFieldError('email', 'Enter a valid email address.');
            }

            const password = form.querySelector('[name="password"]');
            if (password && password.value.length < 6) {
                valid = false;
                showFieldError('password', 'Password must be at least 6 characters.');
            }

            if (branchSelect && roleSelect?.value !== 'system_admin' && !branchSelect.value) {
                valid = false;
                showFieldError('branch_id', 'Branch is required for this account.');
            }

            const contact = form.querySelector('[name="contact_number"]');
            if (contact && !isValidPhilippineMobile(contact.value)) {
                valid = false;
                showFieldError('contact_number', 'Enter a valid Philippine mobile number.');
            }

            const address = form.querySelector('[name="address"]');
            if (address && address.value && !normalizeText(address.value)) {
                valid = false;
                showFieldError('address', 'Address must not be blank.');
            }

            if (!valid) {
                event.preventDefault();
                event.stopPropagation();
            }

            return valid;
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', sync);
        }

        if (branchSelect) {
            branchSelect.addEventListener('change', () => {
                if (branchValue) branchValue.value = branchSelect.value;
            });
        }
        if (togglePasswordButton && passwordInput) {
            togglePasswordButton.addEventListener('click', () => {
                const shouldShow = passwordInput.type === 'password';
                passwordInput.type = shouldShow ? 'text' : 'password';
                togglePasswordButton.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
                togglePasswordButton.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
                togglePasswordButton.title = shouldShow ? 'Hide password' : 'Show password';
                const icon = togglePasswordButton.querySelector('i');
                icon?.classList.toggle('bi-eye', !shouldShow);
                icon?.classList.toggle('bi-eye-slash', shouldShow);
            });
        }
        copyPasswordButton?.addEventListener('click', copyGeneratedPassword);
        generatePasswordButton?.addEventListener('click', fillGeneratedPassword);
        if (form) {
            form.querySelectorAll('[data-name-case]').forEach((input) => {
                input.addEventListener('blur', () => {
                    input.value = normalizeLowercaseName(input.value);
                });
            });
            form.addEventListener('submit', validateForm);
            form.addEventListener('input', (event) => {
                if (event.target?.name) clearFieldError(event.target.name);
                if (event.target === passwordInput) {
                    setPasswordFeedback('Password updated. Copy it before saving.');
                }
            });
        }

        if (passwordInput && !passwordInput.value) fillGeneratedPassword();
        sync();
    })();
</script>
@endsection
