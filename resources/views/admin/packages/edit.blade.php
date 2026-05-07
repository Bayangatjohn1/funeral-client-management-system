@extends('layouts.panel')

@section('page_title', 'Edit Package')
@section('page_desc', 'Update package details, pricing, inclusions, freebies, and optional add-ons.')

@section('content')
<style>
    body, .page-content { background:#E8E4DC !important; }
    .pkg-package-form { color:#333333; }
    .pkg-section { border:1px solid #C9C5BB; background:#FAFAF7; border-radius:14px; padding:18px; color:#333333; }
    .pkg-section-alt { background:#F3F0E8; }
    .pkg-section-title { font-size:1rem; font-weight:800; color:#333333; letter-spacing:-.01em; }
    .pkg-section-help, .pkg-help { margin-top:4px; font-size:.82rem; line-height:1.45; color:#5F685F; font-weight:500; }
    .pkg-label { display:block; margin-bottom:7px; font-size:.72rem; font-weight:900; text-transform:uppercase; letter-spacing:.08em; color:#5F685F; }
    .pkg-label-optional { text-transform:none; letter-spacing:0; font-weight:700; color:#5F685F; }
    .pkg-add-button { display:inline-flex; align-items:center; gap:7px; border:1px solid #C9C5BB; border-radius:10px; background:#FAFAF7; color:#3E4A3D; padding:8px 12px; font-size:.78rem; font-weight:800; cursor:pointer; }
    .pkg-add-button:hover { background:#F3F0E8; }
    .pkg-add-button-primary, .pkg-primary-button { border-color:#3E4A3D; background:#3E4A3D; color:#fff; }
    .pkg-add-button-primary:hover, .pkg-primary-button:hover { background:#344033; border-color:#344033; color:#fff; }
    .pkg-money-input { display:flex; align-items:center; border:1px solid #C9C5BB; border-radius:10px; background:#fff; overflow:hidden; }
    .pkg-money-input span { padding:0 0 0 12px; color:#5F685F; font-weight:900; }
    .pkg-money-input .form-input { border:0; box-shadow:none; }
    .pkg-package-form .form-input, .pkg-package-form .form-select { border-color:#C9C5BB; background:#fff; color:#333333; }
    .pkg-package-form .form-input:focus, .pkg-package-form .form-select:focus { border-color:#3E4A3D; box-shadow:0 0 0 4px rgba(62,74,61,.14); }
    .pkg-package-form .form-error { color:#9E4B3F; }
    .pkg-actions { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:8px; }
</style>
@php
    $returnTo       = old('return_to', request('return_to', route('admin.packages.index')));
    $oldInclusions  = old('inclusions');
    $oldFreebies    = old('freebies');
    $oldAddOns      = old('add_ons');
    $initialInclusions = is_array($oldInclusions)
        ? $oldInclusions
        : ($oldInclusions !== null
            ? \App\Models\Package::parseLegacyItems($oldInclusions)
            : $package->inclusionNames());
    $initialFreebies = is_array($oldFreebies)
        ? $oldFreebies
        : ($oldFreebies !== null
            ? \App\Models\Package::parseLegacyItems($oldFreebies)
            : $package->freebieNames());
    $initialAddOns = is_array($oldAddOns)
        ? $oldAddOns
        : $package->packageAddOns->map(fn ($a) => [
            'id'          => $a->id,
            'name'        => $a->name,
            'description' => $a->description,
            'price'       => $a->price,
            'is_active'   => $a->is_active,
        ])->all();
@endphp

<form id="packageEditForm" method="POST" action="{{ route('admin.packages.update', $package) }}"
      class="pkg-package-form max-w-6xl w-full mx-auto font-ui-body grid grid-cols-1 md:grid-cols-2 gap-5 items-start"
      style="padding:0 1.5rem;">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

{{-- Row 1 col 1: Basic Details --}}
<section class="pkg-section">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <div class="pkg-section-title">Basic Package Details</div>
            <p class="pkg-section-help">Set the package identity and base amount.</p>
        </div>
        <span class="inline-flex items-center rounded-lg border border-[#C9C5BB] bg-[#F3F0E8] px-3 py-1 text-xs font-bold tracking-wide text-[#5F685F]">
            PKG-{{ $package->id }}
        </span>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="pkg-label">Package Name <span class="text-[#9E4B3F]">*</span></label>
            <input type="text" name="name" value="{{ old('name', $package->name) }}" class="form-input" placeholder="e.g. First Class Package" required>
            @error('name') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="name"></div>
        </div>
        <div>
            <label class="pkg-label">Coffin Type <span class="text-[#9E4B3F]">*</span></label>
            <input type="text" name="coffin_type" value="{{ old('coffin_type', $package->coffin_type) }}" class="form-input" placeholder="e.g. Premium Coffin" required>
            @error('coffin_type') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="coffin_type"></div>
        </div>
        <div>
            <label class="pkg-label">Base Price <span class="text-[#9E4B3F]">*</span></label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $package->price) }}" class="form-input" placeholder="0.00" required>
            <p class="pkg-help">Before add-ons, discounts, or promos.</p>
            @error('price') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="price"></div>
        </div>
        <div class="md:col-span-2">
            <input type="hidden" name="is_active" value="0">
            <label class="inline-flex items-center gap-2 text-sm text-[#5F685F] font-semibold cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $package->is_active) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-[#C9C5BB] text-[#3E4A3D] focus:ring-[#3E4A3D]">
                Active package
            </label>
        </div>
    </div>
</section>

{{-- Row 1 col 2: Promo Settings --}}
<section class="pkg-section">
    <div class="mb-4"><div class="pkg-section-title">Promo Settings</div>
    <p class="pkg-section-help">Optional discount settings for this package.</p></div>
    <div class="space-y-4">
        <div>
            <label class="pkg-label">Promo Label</label>
            <input type="text" name="promo_label" value="{{ old('promo_label', $package->promo_label) }}" class="form-input" placeholder="e.g. Summer Promo">
            @error('promo_label') <div class="form-error">{{ $message }}</div> @enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="pkg-label">Value Type</label>
                <select name="promo_value_type" class="form-select">
                    <option value="">None</option>
                    <option value="AMOUNT"  {{ old('promo_value_type', $package->promo_value_type) === 'AMOUNT'  ? 'selected':'' }}>Amount</option>
                    <option value="PERCENT" {{ old('promo_value_type', $package->promo_value_type) === 'PERCENT' ? 'selected':'' }}>Percent</option>
                </select>
            </div>
            <div>
                <label class="pkg-label">Value</label>
                <input type="number" step="0.01" min="0" name="promo_value" value="{{ old('promo_value', $package->promo_value) }}" class="form-input" placeholder="0.00">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="pkg-label">Start</label>
                <input type="datetime-local" name="promo_starts_at"
                       value="{{ old('promo_starts_at', $package->promo_starts_at?->format('Y-m-d\TH:i')) }}"
                       class="form-input">
            </div>
            <div>
                <label class="pkg-label">End</label>
                <input type="datetime-local" name="promo_ends_at"
                       value="{{ old('promo_ends_at', $package->promo_ends_at?->format('Y-m-d\TH:i')) }}"
                       class="form-input">
            </div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-[#5F685F] font-medium cursor-pointer">
            <input type="hidden" name="promo_is_active" value="0">
            <input type="checkbox" name="promo_is_active" value="1"
                   {{ old('promo_is_active', $package->promo_is_active) ? 'checked':'' }}
                   class="h-4 w-4 rounded border-[#C9C5BB] text-[#3E4A3D] focus:ring-[#3E4A3D]">
            Enable promo for this package
        </label>
    </div>
</section>

{{-- Row 2: Package Contents (full width) --}}
<section class="pkg-section md:col-span-2">
    <div class="mb-4"><div class="pkg-section-title">Package Contents</div>
    <p class="pkg-section-help">Add inclusions and freebies for this package.</p></div>
    <div class="grid gap-6 md:grid-cols-2">
        @include('admin.packages._list-inputs', compact('initialInclusions','initialFreebies'))
    </div>
</section>

{{-- Row 3: Add-ons (full width) --}}
@include('admin.packages._add-ons-inputs', compact('initialAddOns'))

{{-- Row 4: Actions --}}
<section class="pkg-section pkg-section-alt pkg-actions md:col-span-2">
    <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
    <button class="btn pkg-primary-button px-5"><i class="bi bi-save2"></i> Save Changes</button>
</section>
</form>
<script>
(function () {
    var form = document.getElementById('packageEditForm');
    if (!form) return;

    var invalidClass = ['border-rose-300','bg-rose-50','focus:border-rose-500','focus:ring-rose-500'];
    var normalize = function(v){ return String(v||'').replace(/\s+/g,' ').trim(); };
    var hasLetter = function(v){ return /[\p{L}\p{M}]/u.test(v); };
    var numOnly   = function(v){ return /^\d+(?:\.\d+)?$/.test(v); };

    function showErr(field, msg) {
        var el  = form.querySelector('[data-field-error="'+field+'"]');
        var inp = form.querySelector('[name="'+field+'"],[name="'+field+'[]"]');
        if (inp) inp.classList.add.apply(inp.classList, invalidClass);
        if (el) { el.textContent = msg; el.classList.remove('hidden'); }
    }
    function clearErr(field) {
        var el  = form.querySelector('[data-field-error="'+field+'"]');
        var inp = form.querySelector('[name="'+field+'"],[name="'+field+'[]"]');
        if (inp) inp.classList.remove.apply(inp.classList, invalidClass);
        if (el) { el.textContent = ''; el.classList.add('hidden'); }
    }

    form.addEventListener('input', function(e){
        if (e.target && e.target.name) clearErr(e.target.name.replace('[]',''));
    });
    var priceInp = form.querySelector('[name="price"]');
    if (priceInp) priceInp.addEventListener('input', function(e){
        if (Number(e.target.value) < 0) e.target.value = '0';
    });

    form.addEventListener('submit', function(e){
        var valid = true;
        ['name','coffin_type','price','inclusions','freebies'].forEach(clearErr);

        ['name','coffin_type'].forEach(function(f){
            var inp = form.querySelector('[name="'+f+'"]');
            if (inp) inp.value = normalize(inp.value);
        });

        [['name','Package name is required.','Package name must include letters.'],
         ['coffin_type','Coffin type is required.','Coffin type must include valid description text.']
        ].forEach(function(rule){
            var val = normalize((form.querySelector('[name="'+rule[0]+'"]')||{}).value||'');
            if (!val) { valid=false; showErr(rule[0],rule[1]); }
            else if (!hasLetter(val)||numOnly(val)) { valid=false; showErr(rule[0],rule[2]); }
        });

        var inclVals = Array.from(form.querySelectorAll('[name="inclusions[]"]'))
            .map(function(i){ return i.value.trim(); }).filter(Boolean);
        if (!inclVals.length) { valid=false; showErr('inclusions','At least one inclusion is required.'); }
        else if (inclVals.some(function(v){ return !hasLetter(v)||numOnly(v); }))
            { valid=false; showErr('inclusions','Inclusion must include valid description text.'); }

        var freeVals = Array.from(form.querySelectorAll('[name="freebies[]"]'))
            .map(function(i){ return i.value.trim(); }).filter(Boolean);
        if (freeVals.some(function(v){ return !hasLetter(v)||numOnly(v); }))
            { valid=false; showErr('freebies','Freebie must include valid description text.'); }

        var p = form.querySelector('[name="price"]');
        if (!p||!p.value) { valid=false; showErr('price','Price is required.'); }
        else if (isNaN(Number(p.value))) { valid=false; showErr('price','Price must be a valid amount.'); }
        else if (Number(p.value)<0) { valid=false; showErr('price','Price cannot be negative.'); }

        if (!valid) e.preventDefault();
    });
})();
</script>
@endsection
