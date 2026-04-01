@extends('layouts.panel')

@section('page_title', 'Add Package')

@section('content')
<form method="POST" action="{{ route('admin.packages.store') }}" class="max-w-2xl space-y-4">
@csrf

<div>
    <label class="block text-sm font-medium">Package Name</label>
    <input type="text" name="name" value="{{ old('name') }}" class="w-full border p-2 rounded" placeholder="First Class Package" required>
    @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Coffin Type</label>
    <input type="text" name="coffin_type" value="{{ old('coffin_type') }}" class="w-full border p-2 rounded" placeholder="Premium Coffin">
    @error('coffin_type') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Price</label>
    <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price') }}" class="w-full border p-2 rounded" placeholder="0.00" required>
    @error('price') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Inclusions</label>
    <textarea name="inclusions" rows="4" class="w-full border p-2 rounded" placeholder="List of included services...">{{ old('inclusions') }}</textarea>
    @error('inclusions') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Freebies</label>
    <textarea name="freebies" rows="3" class="w-full border p-2 rounded" placeholder="Optional freebies/notes...">{{ old('freebies') }}</textarea>
    @error('freebies') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div class="border rounded p-3 space-y-3 bg-slate-50">
    <div class="font-semibold text-sm">Promo Settings (Optional)</div>
    <div>
        <label class="block text-sm font-medium">Promo Label</label>
        <input type="text" name="promo_label" value="{{ old('promo_label') }}" class="w-full border p-2 rounded" placeholder="e.g. Summer Promo">
        @error('promo_label') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium">Promo Value Type</label>
            <select name="promo_value_type" class="w-full border p-2 rounded">
                <option value="">None</option>
                <option value="AMOUNT" {{ old('promo_value_type') === 'AMOUNT' ? 'selected' : '' }}>Amount</option>
                <option value="PERCENT" {{ old('promo_value_type') === 'PERCENT' ? 'selected' : '' }}>Percent</option>
            </select>
            @error('promo_value_type') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Promo Value</label>
            <input type="number" step="0.01" min="0" name="promo_value" value="{{ old('promo_value') }}" class="w-full border p-2 rounded" placeholder="0.00">
            @error('promo_value') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium">Promo Start</label>
            <input type="datetime-local" name="promo_starts_at" value="{{ old('promo_starts_at') }}" class="w-full border p-2 rounded">
            @error('promo_starts_at') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Promo End</label>
            <input type="datetime-local" name="promo_ends_at" value="{{ old('promo_ends_at') }}" class="w-full border p-2 rounded">
            @error('promo_ends_at') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="hidden" name="promo_is_active" value="0">
        <input type="checkbox" name="promo_is_active" value="1" {{ old('promo_is_active') ? 'checked' : '' }}>
        Enable promo for this package
    </label>
</div>

<label class="inline-flex items-center gap-2 text-sm">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
    Active package
</label>

<div class="flex gap-2">
    <button class="bg-black text-white px-4 py-2 rounded">Save Package</button>
    <a href="{{ route('admin.packages.index') }}" class="border px-4 py-2 rounded">Cancel</a>
</div>
</form>
@endsection

