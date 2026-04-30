@extends('layouts.panel')

@section('page_title','Edit User')
@section('page_desc', 'Update user profile details, roles, and credentials.')

@section('content')

@php
    $hasSplitUserNames = \Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name')
        && \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name');
    $hasMiddleName = \Illuminate\Support\Facades\Schema::hasColumn('users', 'middle_name');
    $hasSuffix = \Illuminate\Support\Facades\Schema::hasColumn('users', 'suffix');
    $fallbackNameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $fallbackFirstName = $fallbackNameParts[0] ?? '';
    $fallbackLastName = count($fallbackNameParts) > 1 ? end($fallbackNameParts) : '';
@endphp

<div id="userEditModalContent" class="space-y-8 font-ui-body">
    <form id="userEditForm" method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-3xl w-full mx-auto space-y-6 font-ui-body">
        @csrf
        @method('PUT')

        <input type="hidden" name="return_to" value="{{ old('return_to', request('return_to', route('admin.users.index'))) }}">

        <div class="modal-shell-card p-5 md:p-6 space-y-5 bg-white border border-slate-200 rounded-2xl">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-2">
                    <h2 class="text-[1.5rem] leading-tight text-slate-900 font-ui-heading">Edit User</h2>
                    <p class="text-base text-slate-500">Update account details and access for {{ $user->name }}.</p>
                    <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-xs font-semibold tracking-wide text-slate-600 uppercase">
                        User ID: {{ $user->id }}
                    </span>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @if($hasSplitUserNames)
                <div>
                    <label class="label-section">First Name <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="first_name"
                        value="{{ old('first_name', $user->first_name ?: $fallbackFirstName) }}"
                        class="form-input"
                        placeholder="Juan"
                        required
                    >
                    @error('first_name')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
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
                    <label class="label-section">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" placeholder="Juan Dela Cruz" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="name"></div>
                </div>
                @endif

                <div>
                    <label class="label-section">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        class="form-input"
                        placeholder="user@example.com"
                        required
                    >
                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div class="form-error hidden" data-field-error="email"></div>
                </div>

                <div>
                    <label class="label-section">Role</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Branch Admin</option>
                    </select>
                    @error('role')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div class="form-hint mt-1">
                        {{ $user->isMainBranchAdmin() ? 'Main Branch Administrator access is preserved and cannot be reassigned here.' : 'Admin accounts managed here remain Branch Admins.' }}
                    </div>
                </div>

                <div>
                    <label class="label-section">Branch</label>
                    <select name="branch_id" id="branch_id" class="form-select">
                        <option value="">- None -</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('branch_id', $user->branch_id) === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div class="form-error hidden" data-field-error="branch_id"></div>
                    <div id="branch_hint" class="form-hint mt-1">Branch is required for staff and branch admin accounts.</div>
                </div>

                <div id="contact_wrap">
                    <label class="label-section">Contact Number</label>
                    <input
                        type="text"
                        name="contact_number"
                        value="{{ old('contact_number', $user->contact_number) }}"
                        class="form-input"
                        inputmode="tel"
                        placeholder="+63 9XX XXX XXXX"
                    >
                    @error('contact_number')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div class="form-error hidden" data-field-error="contact_number"></div>
                </div>

                <div id="position_wrap">
                    <label class="label-section">Position</label>
                    <select name="position" id="position" class="form-select" data-selected="{{ old('position', $user->position) }}">
                        <option value="">Select position</option>
                    </select>
                    @error('position')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div id="address_wrap" class="md:col-span-2">
                    <label class="label-section">Address</label>
                    <input
                        type="text"
                        name="address"
                        value="{{ old('address', $user->address) }}"
                        class="form-input"
                        placeholder="House No., Street, Barangay, City"
                    >
                    @error('address')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                    <div class="form-error hidden" data-field-error="address"></div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 pt-1 justify-end">
            <a href="{{ old('return_to', request('return_to', route('admin.users.index'))) }}" class="btn btn-outline">Cancel</a>

            <button type="submit" class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
                <i class="bi bi-save2"></i>
                Save Changes
            </button>
        </div>
    </form>

    <div class="modal-shell-card max-w-3xl w-full mx-auto border border-slate-200 rounded-2xl bg-white p-5">
        <div class="text-[1.2rem] leading-tight text-slate-900 font-ui-heading mb-3">Reset Password (Optional)</div>

        <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}" class="space-y-3">
            @csrf
            @method('PATCH')

            <input type="hidden" name="return_to" value="{{ old('return_to', request('return_to', route('admin.users.index'))) }}">

            <div>
                <label class="label-section mb-1">New Password</label>
                <input type="password" name="password" class="form-input">
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="label-section mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-input">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-outline">
                    Reset
                </button>
            </div>
        </form>
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
                const needsBranch = roleSelect && ['staff', 'admin'].includes(roleSelect.value);

                if (branchSelect) {
                    branchSelect.required = !!needsBranch;
                }

                if (branchHint) {
                    if (roleSelect?.value === 'staff') {
                        branchHint.textContent = 'Branch is required for staff accounts.';
                    } else {
                        branchHint.textContent = 'Branch is required for branch admin accounts.';
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
                        showFieldError(field, 'Name fields may only contain letters, spaces, hyphen, apostrophe, period, ñ, Ñ, and accented letters.');
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

                if (branchSelect && ['staff', 'admin'].includes(roleSelect?.value) && !branchSelect.value) {
                    valid = false;
                    showFieldError('branch_id', 'Branch is required for staff and branch admin accounts.');
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
</div>
@endsection
