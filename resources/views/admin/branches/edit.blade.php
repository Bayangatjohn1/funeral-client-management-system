@extends('layouts.panel')

@section('page_title','Edit Branch')
@section('page_desc', 'Update branch profile and operational details.')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.branches.index'))))
<form id="branchEditForm" method="POST" action="{{ route('admin.branches.update', $branch) }}" class="max-w-3xl w-full mx-auto space-y-6">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="p-5 md:p-6 space-y-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Edit Branch</h2>
            <p class="text-sm text-slate-500">Update branch details and status.</p>
        </div>
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">Branch ID: {{ $branch->branch_code }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="label-section">Branch Code</label>
            <input type="text" value="{{ $branch->branch_code }}" class="form-input bg-slate-100 font-semibold" readonly>
        </div>

        <div>
            <label class="label-section">Branch Name</label>
            <input type="text" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}" class="form-input" required>
            @error('branch_name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Address</label>
            <input type="text" name="address" value="{{ old('address', $branch->address) }}" class="form-input">
            @error('address') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="md:col-span-2 flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
                <span>Active</span>
            </label>
        </div>
    </div>
</div>

<div class="flex flex-wrap gap-2 pt-3 justify-end">
    <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
    <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
        <i class="bi bi-save2"></i>
        Save Changes
    </button>
</div>
</form>
@endsection
