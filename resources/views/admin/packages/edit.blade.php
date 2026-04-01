@extends('layouts.panel')

@section('page_title','Edit Package')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.packages.index'))))
<form id="packageEditForm" method="POST" action="{{ route('admin.packages.update', $package) }}" enctype="multipart/form-data" class="max-w-4xl w-full mx-auto space-y-6">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="p-5 md:p-6 space-y-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Edit Package</h2>
            <p class="text-sm text-slate-500">Update service inclusions, pricing, and promo details.</p>
        </div>
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">Package ID: {{ $package->id }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="label-section">Name</label>
            <input type="text" name="name" value="{{ old('name', $package->name) }}" class="form-input" required>
            @error('name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Coffin Type</label>
            <input type="text" name="coffin_type" value="{{ old('coffin_type', $package->coffin_type) }}" class="form-input">
        </div>

        <div>
            <label class="label-section">Price</label>
            <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price', $package->price) }}" class="form-input" required>
        </div>

        <div class="md:col-span-2">
            <label class="label-section">Inclusions</label>
            <textarea name="inclusions" class="form-textarea" rows="3">{{ old('inclusions', $package->inclusions) }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="label-section">Freebies</label>
            <textarea name="freebies" class="form-textarea" rows="2">{{ old('freebies', $package->freebies) }}</textarea>
        </div>

        <div>
            <label class="label-section">Promo Value Type</label>
            <select name="promo_value_type" class="form-select">
                <option value="">None</option>
                <option value="PERCENT" {{ old('promo_value_type', $package->promo_value_type) === 'PERCENT' ? 'selected' : '' }}>Percent</option>
                <option value="AMOUNT" {{ old('promo_value_type', $package->promo_value_type) === 'AMOUNT' ? 'selected' : '' }}>Fixed Amount</option>
            </select>
        </div>

        <div>
            <label class="label-section">Promo Value</label>
            <input type="number" step="0.01" name="promo_value" value="{{ old('promo_value', $package->promo_value) }}" class="form-input">
        </div>

        <div>
            <label class="label-section">Promo Label</label>
            <input type="text" name="promo_label" value="{{ old('promo_label', $package->promo_label) }}" class="form-input">
        </div>

        <div>
            <label class="label-section">Promo Starts At</label>
            <input type="datetime-local" name="promo_starts_at" value="{{ old('promo_starts_at', $package->promo_starts_at?->format('Y-m-d\TH:i')) }}" class="form-input">
        </div>

        <div>
            <label class="label-section">Promo Ends At</label>
            <input type="datetime-local" name="promo_ends_at" value="{{ old('promo_ends_at', $package->promo_ends_at?->format('Y-m-d\TH:i')) }}" class="form-input">
        </div>

        <div class="md:col-span-2 flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
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
