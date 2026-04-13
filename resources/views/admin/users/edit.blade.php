@extends('layouts.panel')

@section('page_title','Edit User')
@section('page_desc', 'Update user profile details, roles, and credentials.')

@section('content')

@php
    $latestTemp = $latestTempPermission ?? $user->latestTemporaryPermission;
    $activeTemp = $activeTempPermission ?? ($user->temporaryPermissions()->active()->latest('granted_at')->first());

    $isActiveTemp = $activeTemp
        && !$activeTemp->is_used
        && (!$activeTemp->expires_at || $activeTemp->expires_at->isFuture());

    $defaultGrantChecked = (bool) old('grant_temp_access', $isActiveTemp ? 1 : 0);

    $selectedBranch = old(
        'temp_allowed_branch_id',
        $isActiveTemp ? $activeTemp?->allowed_branch_id : ''
    );

    $selectedExpiry = old(
        'temp_expires_at',
        ($isActiveTemp && $activeTemp?->expires_at)
            ? $activeTemp->expires_at->format('Y-m-d')
            : ''
    );
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
                <div>
                    <label class="label-section">Name</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        class="form-input"
                        required
                    >
                    @error('name')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="label-section">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        class="form-input"
                        required
                    >
                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="label-section">Role</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="owner" {{ old('role', $user->role) == 'owner' ? 'selected' : '' }}>Owner</option>
                    </select>
                    @error('role')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
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
                    <div id="branch_hint" class="form-hint mt-1">Branch is required for staff accounts.</div>
                </div>

                <div id="cross_branch_wrap" class="md:col-span-2 space-y-3 border border-amber-200 rounded-xl p-4 bg-amber-50 hidden">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="grant_temp_access" value="0">

                        <input
                            type="checkbox"
                            name="grant_temp_access"
                            id="grant_temp_access"
                            value="1"
                            {{ $defaultGrantChecked ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]"
                        >

                        <span>Grant Temporary Cross-Branch Access</span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label-section mb-1">Allowed Branch</label>
                            <select name="temp_allowed_branch_id" id="temp_allowed_branch_id" class="form-select">
                                <option value="">Select branch</option>
                                @foreach($branches as $branch)
                                    @if(strtoupper($branch->branch_code) !== 'BR001')
                                        <option value="{{ $branch->id }}" {{ (string) $selectedBranch === (string) $branch->id ? 'selected' : '' }}>
                                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('temp_allowed_branch_id')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label-section mb-1">Expires At (optional)</label>
                            <input
                                type="date"
                                name="temp_expires_at"
                                id="temp_expires_at"
                                value="{{ $selectedExpiry }}"
                                class="form-input"
                            >
                            @error('temp_expires_at')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-700 space-y-1">
                        <p>Single-use permission; auto-consumed after one other-branch intake save.</p>

                        @if($isActiveTemp && $activeTemp)
                            <p class="font-semibold text-emerald-700">
                                Current: {{ $activeTemp->status_label }}
                                {{ $activeTemp->expires_at ? ' - Expires '.$activeTemp->expires_at->toFormattedDateString() : '' }}
                            </p>
                        @elseif($latestTemp)
                            <p class="text-slate-600">
                                Last permission: {{ $latestTemp->status_label }}
                            </p>
                        @else
                            <p class="text-slate-500">No active temporary access.</p>
                        @endif
                    </div>
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
                </div>

                <div id="position_wrap">
                    <label class="label-section">Position</label>
                    <input
                        type="text"
                        name="position"
                        value="{{ old('position', $user->position) }}"
                        class="form-input"
                        placeholder="Staff/Admin Position"
                    >
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
                    >
                    @error('address')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
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
            const crossWrap = document.getElementById('cross_branch_wrap');
            const grantCheckbox = document.getElementById('grant_temp_access');
            const tempBranchSelect = document.getElementById('temp_allowed_branch_id');
            const tempExpiresInput = document.getElementById('temp_expires_at');
            const branchHint = document.getElementById('branch_hint');

            function syncCrossBranchState() {
                const isStaff = roleSelect && roleSelect.value === 'staff';
                const isOwner = roleSelect && roleSelect.value === 'owner';

                if (crossWrap) {
                    crossWrap.classList.toggle('hidden', !isStaff);
                }

                if (branchSelect) {
                    branchSelect.required = isStaff;
                    branchSelect.disabled = isOwner;
                    if (isOwner) {
                        branchSelect.value = '';
                    }
                }

                if (branchHint) {
                    if (isStaff) {
                        branchHint.textContent = 'Branch is required for staff accounts.';
                    } else if (isOwner) {
                        branchHint.textContent = 'Owner accounts are global and not tied to a branch.';
                    } else {
                        branchHint.textContent = 'Branch assignment is optional for admin accounts.';
                    }
                }

                if (!isStaff && grantCheckbox) {
                    grantCheckbox.checked = false;
                }

                const grantEnabled = isStaff && grantCheckbox && grantCheckbox.checked;

                if (tempBranchSelect) {
                    tempBranchSelect.disabled = !grantEnabled;
                    tempBranchSelect.required = grantEnabled;
                    if (!grantEnabled) {
                        tempBranchSelect.value = '';
                    }
                }

                if (tempExpiresInput) {
                    tempExpiresInput.disabled = !grantEnabled;
                    if (!grantEnabled) {
                        tempExpiresInput.value = '';
                    }
                }
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', syncCrossBranchState);
            }

            if (grantCheckbox) {
                grantCheckbox.addEventListener('change', syncCrossBranchState);
            }

            syncCrossBranchState();
        })();
    </script>
</div>
@endsection
