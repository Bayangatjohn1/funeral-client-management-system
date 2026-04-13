@extends('layouts.panel')

@section('page_title', 'Add Package')
@section('page_desc', 'Create a new service package with pricing and inclusions.')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.packages.index'))))
<form id="packageCreateForm" method="POST" action="{{ route('admin.packages.store') }}" class="max-w-4xl w-full mx-auto font-ui-body">
@csrf
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="modal-shell-card rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200">
        <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Add Package</h2>
        <p class="text-base text-slate-500">Create a new service package with pricing and inclusions.</p>
    </div>

    <div class="p-6 space-y-5">
        <div>
            <label class="label-section">Package Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="First Class Package" required>
            @error('name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label-section">Coffin Type</label>
                <input type="text" name="coffin_type" value="{{ old('coffin_type') }}" class="form-input" placeholder="Premium Coffin">
                @error('coffin_type') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="label-section">Price <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price') }}" class="form-input" placeholder="0.00" required>
                @error('price') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label class="label-section">Inclusions</label>
            <textarea name="inclusions" rows="4" class="form-textarea" placeholder="List of included services...">{{ old('inclusions') }}</textarea>
            @error('inclusions') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Freebies</label>
            <textarea name="freebies" rows="3" class="form-textarea" placeholder="Optional freebies/notes...">{{ old('freebies') }}</textarea>
            @error('freebies') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">
            <div class="text-[1.1rem] leading-tight text-slate-900 font-ui-heading">Promo Settings (Optional)</div>
            <div>
                <label class="label-section">Promo Label</label>
                <input type="text" name="promo_label" value="{{ old('promo_label') }}" class="form-input" placeholder="e.g. Summer Promo">
                @error('promo_label') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="label-section">Promo Value Type</label>
                    <select name="promo_value_type" class="form-select">
                        <option value="">None</option>
                        <option value="AMOUNT" {{ old('promo_value_type') === 'AMOUNT' ? 'selected' : '' }}>Amount</option>
                        <option value="PERCENT" {{ old('promo_value_type') === 'PERCENT' ? 'selected' : '' }}>Percent</option>
                    </select>
                    @error('promo_value_type') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="label-section">Promo Value</label>
                    <input type="number" step="0.01" min="0" name="promo_value" value="{{ old('promo_value') }}" class="form-input" placeholder="0.00">
                    @error('promo_value') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="label-section">Promo Start</label>
                    <input type="datetime-local" name="promo_starts_at" value="{{ old('promo_starts_at') }}" class="form-input">
                    @error('promo_starts_at') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="label-section">Promo End</label>
                    <input type="datetime-local" name="promo_ends_at" value="{{ old('promo_ends_at') }}" class="form-input">
                    @error('promo_ends_at') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 font-medium">
                <input type="hidden" name="promo_is_active" value="0">
                <input type="checkbox" name="promo_is_active" value="1" {{ old('promo_is_active') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
                Enable promo for this package
            </label>
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700 font-medium">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
            Active package
        </label>
    </div>

    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save Package
        </button>
    </div>
</div>
</form>
@endsection
