@extends('layouts.panel')
@section('page_title', 'Change User Role')
@section('page_desc', 'Review and confirm a change to operational access.')
@section('hide_layout_topbar', '1')
@section('content')
@include('partials.user_management_visual_styles')
<div class="user-edit-page px-4 sm:px-6 lg:px-8 py-4">
    <div class="mx-auto max-w-3xl rounded-xl border p-6" style="background:var(--card);border-color:var(--border);color:var(--ink)">
        <h2 class="text-xl font-semibold mb-2">{{ $user->name }}</h2>
        <p class="mb-5">Current access: {{ $user->roleLabel() }} — {{ $user->branch?->branch_name ?? 'No branch' }}.</p>
        @if($errors->any())<div role="alert" class="form-error mb-4"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.users.role.update', $user) }}" class="space-y-5">
            @csrf @method('PATCH')
            <input type="hidden" name="expected_role" value="{{ $user->isStaff() ? 'staff' : 'branch_admin' }}">
            <div><label for="new_role" class="label-section">New role *</label><select id="new_role" name="role" class="form-select" required><option value="">Select role</option><option value="{{ $user->isStaff() ? 'branch_admin' : 'staff' }}" selected>{{ $user->isStaff() ? 'Branch Admin' : 'Staff' }}</option></select></div>
            <div><label for="role_branch" class="label-section">Assigned branch *</label><select id="role_branch" name="branch_id" class="form-select" required><option value="">Select active branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) old('branch_id', $user->branch_id) === $branch->id)>{{ $branch->branch_code }} — {{ $branch->branch_name }}</option>@endforeach</select></div>
            <p class="form-hint">{{ $user->isStaff() ? 'Branch Admin can manage staff accounts and access administrative workflows within the assigned branch. Staff permissions will be replaced.' : 'Staff receives operational access within the assigned branch. Branch Admin permissions will be removed.' }} System Admin access requires a separate personal account.</p>
            <div><label for="role_reason" class="label-section">Reason *</label><textarea id="role_reason" name="reason" class="form-input" required minlength="10" maxlength="500">{{ old('reason') }}</textarea></div>
            <div><label for="role_password" class="label-section">Your current password *</label><input id="role_password" name="current_password" type="password" autocomplete="current-password" class="form-input" required></div>
            <label class="flex items-start gap-2"><input type="checkbox" name="confirmed" value="1" required><span>I confirm this role change. Existing sessions will be revoked and the user must sign in again.</span></label>
            <div class="flex justify-end gap-3"><a class="btn btn-outline" href="{{ route('admin.users.edit', $user) }}">Cancel</a><button class="btn btn-primary-custom" type="submit">Confirm role change</button></div>
        </form>
    </div>
</div>
@endsection
