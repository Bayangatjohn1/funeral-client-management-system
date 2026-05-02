@extends('layouts.panel')

@section('page_title','Create User')
@section('page_desc', 'Create a new system user and assign role access.')

@section('content')
@php
    $returnTo = old('return_to', request('return_to', route('admin.users.index')));
    $hasSplitUserNames = \Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name')
        && \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name');
    $hasMiddleName = \Illuminate\Support\Facades\Schema::hasColumn('users', 'middle_name');
    $hasSuffix = \Illuminate\Support\Facades\Schema::hasColumn('users', 'suffix');
    $branchesWithActiveBranchAdmin = collect($branchesWithActiveBranchAdmin ?? [])->map(fn ($id) => (int) $id)->all();
    $isMainBranchAdmin = auth()->user()?->isMainBranchAdmin();
    $isBranchAdmin = auth()->user()?->role === 'admin' && ! auth()->user()?->isMainBranchAdmin();
@endphp
<form id="userCreateForm" method="POST" action="{{ route('admin.users.store') }}" class="max-w-4xl w-full mx-auto font-ui-body">
@csrf
<div class="w-full bg-slate-100 min-h-screen py-10 px-4 sm:px-6 lg:px-8 flex justify-center">

<div class="w-full max-w-5xl">

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

<div class="modal-shell-card max-w-5xl mx-auto rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

    <!-- HEADER -->
    <div class="px-8 py-6 border-b border-slate-200 bg-slate-50">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Create User</h2>
                <p class="text-sm text-slate-500 mt-1">Create a new system user and assign role access.</p>
            </div>
            <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                New Account
            </span>
        </div>
    </div>

    <div class="p-8 space-y-8">

        <!-- ACCOUNT CARD -->
        <div class="rounded-xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Information</h3>

            <div class="grid gap-5 md:grid-cols-2">

                @if($hasSplitUserNames)
                <div>
                    <label class="label-section">First Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-input" placeholder="Juan" autocomplete="off" required>
                    @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="first_name"></div>
                </div>

                @if($hasMiddleName)
                <div>
                    <label class="label-section">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-input" placeholder="Santos" autocomplete="off">
                    @error('middle_name') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="middle_name"></div>
                </div>
                @endif

                <div>
                    <label class="label-section">Last Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-input" placeholder="Dela Cruz" autocomplete="off" required>
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
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="Juan Dela Cruz" required>
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
                    <label class="label-section">Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" class="form-input" placeholder="Minimum 6 characters" required autocomplete="new-password">
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                    <div class="form-error hidden" data-field-error="password"></div>
                </div>

            </div>
        </div>

        <!-- ROLE CARD -->
        <div class="rounded-xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Role & Access</h3>

            <div class="grid gap-5 md:grid-cols-2">

                <div>
                    <label class="label-section">Role <span class="text-rose-500">*</span></label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                        @if($isMainBranchAdmin)
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Branch Admin</option>
                        @endif
                    </select>

                    @error('role') <div class="form-error">{{ $message }}</div> @enderror

                    <div class="form-hint" id="role">
                       
                    </div>
                </div>

                <div>
                    <label class="label-section">Branch</label>
                    <select name="branch_id" id="branch_id" class="form-select">
                        <option value="">- Select Branch -</option>

                        @foreach($branches as $branch)
                            @php
                                $branchId = (int) $branch->id;
                                $hasActiveBranchAdmin = in_array($branchId, $branchesWithActiveBranchAdmin, true);
                                $selected = old('branch_id') == $branch->id;
                            @endphp

                            <option
                                value="{{ $branch->id }}"
                                data-has-branch-admin="{{ $hasActiveBranchAdmin ? '1' : '0' }}"
                                {{ $selected ? 'selected' : '' }}
                            >
                                {{ $branch->branch_name }}
                                {{ $hasActiveBranchAdmin ? ' - already has Branch Admin' : '' }}
                            </option>
                        @endforeach
                    </select>

                    @error('branch_id') <div class="form-error">{{ $message }}</div> @enderror

                    <div class="form-hint" id="branchHint">
                        * Staff will auto assigned on the current branch.<br>
                        * You can't assign branch admin to a branch that has already a branch admin. <br>
                    </div>
                </div>

            </div>
        </div>

        <!-- PERSONAL CARD -->
        <div class="rounded-xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Personal Information</h3>

            <div class="grid gap-5 md:grid-cols-2">

                <div>
                    <label class="label-section">Contact Number</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="form-input" placeholder="+63 9XX XXX XXXX">
                    @error('contact_number') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="label-section">Position</label>
                    <select name="position" id="position" class="form-select" data-selected="{{ old('position') }}">
                        <option value="">Select position</option>
                    </select>
                    @error('position') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="label-section">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-input" placeholder="House No., Street, Barangay, City">
                    @error('address') <div class="form-error">{{ $message }}</div> @enderror
                </div>

            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="px-8 py-5 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] text-white px-6">
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
        const branchSelect = document.getElementById('branch_id');
        const positionSelect = document.getElementById('position');
        const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];
        const positions = {
            staff: ['Staff', 'Encoder', 'Cashier', 'Branch Staff', 'Funeral Assistant'],
            admin: ['Branch Admin', 'Branch Manager', 'Office Admin'],
            main_admin: ['Main Admin', 'System Admin', 'Administrator'],
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

       function sync() {
            if (!roleSelect || !branchSelect) {
                syncPositions();
                return;
            }

            const isStaff = roleSelect.value === 'staff';
            const isAdmin = roleSelect.value === 'admin';

            if (isStaff) {
                branchSelect.required = false;
                branchSelect.disabled = true;
            } else if (isAdmin) {
                branchSelect.required = true;
                branchSelect.disabled = false;
            }

            syncPositions();
        }

        function validateForm(event) {
            if (!form) return true;
            let valid = true;
            const fields = ['first_name', 'middle_name', 'last_name', 'name', 'email', 'password', 'branch_id', 'contact_number', 'position', 'address'];
            fields.forEach(clearFieldError);

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

            if (branchSelect && roleSelect?.value === 'admin' && !branchSelect.value) {
                valid = false;
                showFieldError('branch_id', 'Branch is required for branch admin accounts.');
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
        if (form) {
            form.addEventListener('submit', validateForm);
            form.addEventListener('input', (event) => {
                if (event.target?.name) clearFieldError(event.target.name);
            });
        }

        sync();
    })();
</script>
@endsection
