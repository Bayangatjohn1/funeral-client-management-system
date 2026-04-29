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
@endphp
<form id="userCreateForm" method="POST" action="{{ route('admin.users.store') }}" class="max-w-4xl w-full mx-auto font-ui-body">
@csrf
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="modal-shell-card rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Create User</h2>
                <p class="text-base text-slate-500">Create a new system user and assign role access.</p>
            </div>
            <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold tracking-wide text-slate-700">
                New Account
            </span>
        </div>
    </div>

    <div class="p-6 space-y-5">
        <div class="grid gap-4 md:grid-cols-2">
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

            <div>
                <label class="label-section">Role <span class="text-rose-500">*</span></label>
                <select name="role" id="role" class="form-select" required>
                    <option value="staff" {{ old('role')=='staff' ? 'selected' : '' }}>Staff</option>
                    <option value="admin" {{ old('role')=='admin' ? 'selected' : '' }}>Branch Admin</option>
                </select>
                @error('role') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="role"></div>
                <div class="form-hint">New admin accounts are created as Branch Admins automatically.</div>
            </div>

            <div class="md:col-span-2">
                <label class="label-section">Branch</label>
                <select name="branch_id" id="branch_id" class="form-select">
                    <option value="">- None -</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id')==$branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="branch_id"></div>
                <div class="form-hint">Branch is required for staff and branch admin accounts.</div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label-section">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="form-input" placeholder="+63 9XX XXX XXXX">
                @error('contact_number') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="contact_number"></div>
            </div>

            <div>
                <label class="label-section">Position</label>
                <select name="position" id="position" class="form-select" data-selected="{{ old('position') }}">
                    <option value="">Select position</option>
                </select>
                @error('position') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="position"></div>
            </div>
        </div>

        <div>
            <label class="label-section">Address</label>
            <input type="text" name="address" value="{{ old('address') }}" class="form-input" placeholder="House No., Street, Barangay, City">
            @error('address') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="address"></div>
        </div>
    </div>

    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save User
        </button>
    </div>
</div>
</form>

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
            const needsBranch = roleSelect && ['staff', 'admin'].includes(roleSelect.value);
            if (branchSelect) {
                branchSelect.required = !!needsBranch;
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

            if (branchSelect && ['staff', 'admin'].includes(roleSelect?.value) && !branchSelect.value) {
                valid = false;
                showFieldError('branch_id', 'Branch is required for staff and branch admin accounts.');
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
