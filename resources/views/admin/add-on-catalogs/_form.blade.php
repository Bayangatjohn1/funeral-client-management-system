@php
    $isEdit = $catalog->exists;
    $action = $isEdit ? route('admin.add-on-catalogs.update', $catalog) : route('admin.add-on-catalogs.store');
    $returnTo = old('return_to', request('return_to', route('admin.add-on-catalogs.index')));
    $categoryOptions = $categoryOptions ?? \App\Models\AddOnCatalog::CATEGORY_OPTIONS;
    $unitOptions = \App\Models\AddOnCatalog::UNIT_OPTIONS;
@endphp

<style>
    html:not([data-theme='dark']) .page-content { background:#E8E4DC !important; }
    .aoc-form { max-width:900px; padding:24px 1rem 34px; color:#2F302C; }
    .aoc-card { background:#FAFAF7; border:1px solid #C9C5BB; border-radius:8px; overflow:hidden; }
    .aoc-head { padding:20px; border-bottom:1px solid #E1DDD5; }
    .aoc-title { font-size:1.2rem; font-weight:950; color:#2F302C; display:flex; gap:10px; align-items:center; }
    .aoc-copy { margin-top:5px; color:#5F6B5C; font-size:.9rem; font-weight:650; line-height:1.5; max-width:720px; }
    .aoc-note { margin-top:12px; display:flex; gap:10px; border:1px solid #D8D0C4; background:#F8F4EC; border-radius:8px; padding:12px; color:#5b4537; font-size:.82rem; font-weight:750; line-height:1.5; }
    .aoc-body { padding:20px; display:grid; gap:16px; }
    .aoc-grid { display:grid; grid-template-columns:1fr 190px; gap:16px; }
    .aoc-label { display:block; margin-bottom:8px; font-size:.74rem; font-weight:950; text-transform:uppercase; letter-spacing:.07em; color:#53604F; }
    .aoc-form .form-input, .aoc-form .form-select, .aoc-form textarea { width:100%; min-height:44px; border:1px solid #C9C5BB; border-radius:7px; background:#fff; color:#2F302C; font-size:.92rem; font-weight:650; }
    .aoc-select-wrap { position:relative; }
    .aoc-select-wrap .form-select { cursor:pointer; appearance:none; background-image:none !important; padding-right:2.4rem; }
    .aoc-select-icon { position:absolute; right:.85rem; top:50%; transform:translateY(-50%); color:#5F685F; font-size:.9rem; pointer-events:none; }
    .aoc-money { display:flex; align-items:center; border:1px solid #C9C5BB; border-radius:7px; background:#fff; overflow:hidden; }
    .aoc-money span { padding-left:12px; font-weight:950; color:#5F685F; }
    .aoc-money .form-input { border:0; border-radius:0; }
    .aoc-actions { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #E1DDD5; background:#F8F4EC; }
    .aoc-primary { min-height:44px; border:1px solid #2F392E; background:#3E4A3D; color:#fff; border-radius:8px; font-weight:950; padding:0 16px; display:inline-flex; align-items:center; gap:8px; }
    .aoc-secondary { min-height:44px; border:1px solid #BDB5A8; background:#fff; color:#3E4A3D; border-radius:8px; font-weight:950; padding:0 16px; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .form-error { margin-top:6px; color:#9E4B3F; font-size:.78rem; font-weight:800; }
    html[data-theme='dark'] .page-content { background:#0f1a2e !important; }
    html[data-theme='dark'] .aoc-form { color:#e2ecf9; }
    html[data-theme='dark'] .aoc-card { background:#111e33; border-color:#243954; }
    html[data-theme='dark'] .aoc-head, html[data-theme='dark'] .aoc-actions { background:#13263d; border-color:#243954; }
    html[data-theme='dark'] .aoc-title { color:#e2ecf9; }
    html[data-theme='dark'] .aoc-copy { color:#9fb6d2; }
    html[data-theme='dark'] .aoc-note { background:#16263b; border-color:#2a3f5f; color:#c7d8ee; }
    html[data-theme='dark'] .aoc-label { color:#7fa6cf; }
    html[data-theme='dark'] .aoc-form .form-input, html[data-theme='dark'] .aoc-form .form-select, html[data-theme='dark'] .aoc-form textarea, html[data-theme='dark'] .aoc-money { background:#1a2b41 !important; border-color:#2a3f5f !important; color:#d8ecff !important; }
    html[data-theme='dark'] .aoc-money span { color:#7fa6cf; }
    html[data-theme='dark'] .aoc-primary { background:#3b82f6; border-color:#60a5fa; }
    html[data-theme='dark'] .aoc-secondary { background:#152035; border-color:#2a3f5f; color:#d8ecff; }
    html:not([data-theme='dark']) .page-content {
        background:
            linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
            #C4D2BE !important;
        background-size:44px 44px,44px 44px,16px 16px,auto;
    }
    .aoc-form { color:var(--ink); }
    .aoc-card { background:#D3DEC9 !important; border-color:var(--border) !important; box-shadow:none !important; }
    .aoc-head, .aoc-actions { background:#C7D5BE !important; border-color:var(--border) !important; }
    .aoc-title { color:var(--ink) !important; font-weight:750 !important; }
    .aoc-copy { color:var(--ink-muted) !important; font-weight:600 !important; }
    .aoc-note { background:#E1E7D9 !important; border-color:var(--border) !important; color:var(--ink-muted) !important; box-shadow:none !important; }
    .aoc-label { color:#566653 !important; font-weight:650 !important; }
    .aoc-form .form-input, .aoc-form .form-select, .aoc-form textarea, .aoc-money {
        background:#E1E7D9 !important;
        border-color:var(--border) !important;
        color:var(--ink) !important;
        box-shadow:none !important;
    }
    .aoc-form .form-input:hover, .aoc-form .form-select:hover, .aoc-form textarea:hover, .aoc-money:hover,
    .aoc-form .form-input:focus, .aoc-form .form-select:focus, .aoc-form textarea:focus {
        background:#EEF3E8 !important;
        border-color:#8EA083 !important;
        box-shadow:none !important;
    }
    .aoc-select-wrap:hover .aoc-select-icon, .aoc-select-wrap:focus-within .aoc-select-icon { color:#344333; }
    .aoc-primary, .aoc-secondary { cursor:pointer; box-shadow:none !important; }
    .aoc-primary { background:#344333 !important; border-color:#344333 !important; color:#fff !important; font-weight:700 !important; }
    .aoc-primary:hover { background:#2F3A2E !important; border-color:#2F3A2E !important; }
    .aoc-secondary { background:#E1E7D9 !important; border-color:var(--border) !important; color:var(--ink) !important; font-weight:700 !important; }
    .aoc-secondary:hover { background:#D3DEC9 !important; border-color:#8EA083 !important; }
    @media (max-width:720px) { .aoc-grid { grid-template-columns:1fr; } .aoc-actions { display:grid; } }
</style>

<form method="POST" action="{{ $action }}" class="aoc-form mx-auto">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    <section class="aoc-card">
        <div class="aoc-head">
            <div class="aoc-title"><i class="bi bi-plus-circle"></i> {{ $isEdit ? 'Edit Add-on' : 'Add Add-on' }}</div>
            <p class="aoc-copy">Reusable optional add-ons for future intake selection. These are not package inclusions, freebies, casket upgrades, excess kilometer charges, or extended-day charges.</p>
            <div class="aoc-note"><i class="bi bi-info-circle"></i><span>New add-ons are automatically available. Archive an add-on to hide it from future selections while keeping historical package and case records intact.</span></div>
        </div>
        <div class="aoc-body">
            <div class="aoc-grid">
                <div>
                    <label class="aoc-label">Add-on Name</label>
                    <input type="text" name="name" value="{{ old('name', $catalog->name) }}" class="form-input" required maxlength="150" pattern="[A-Za-zÀ-ž][A-Za-zÀ-ž\s,'()/&-]*" title="Use letters and normal separators only. Numbers and unnecessary symbols are not allowed." placeholder="e.g. Extra flower arrangement">
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="aoc-label">Add-on Type</label>
                    <div class="aoc-select-wrap">
                        <select name="category" class="form-select" required>
                            @foreach($categoryOptions as $category)
                                <option value="{{ $category }}" @selected(old('category', $catalog->category ?: 'General') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down aoc-select-icon" aria-hidden="true"></i>
                    </div>
                    @error('category') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="aoc-grid">
                <div>
                    <label class="aoc-label">Standard Price</label>
                    <div class="aoc-money"><span>&#8369;</span><input type="number" step="0.01" min="0" inputmode="decimal" name="price" value="{{ old('price', $isEdit ? $catalog->price : '') }}" class="form-input" required placeholder="0.00"></div>
                    @error('price') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="aoc-label">Unit</label>
                    <div class="aoc-select-wrap">
                        <select name="unit" class="form-select" required>
                            @foreach($unitOptions as $unit)
                                <option value="{{ $unit }}" @selected(old('unit', $catalog->unit ?: 'item') === $unit)>{{ ucfirst($unit) }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down aoc-select-icon" aria-hidden="true"></i>
                    </div>
                    @error('unit') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div>
                <label class="aoc-label">Description</label>
                <textarea name="description" rows="3" class="form-input" maxlength="500" placeholder="Optional staff-facing details.">{{ old('description', $catalog->description) }}</textarea>
                @error('description') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="aoc-actions">
            <a href="{{ $returnTo }}" class="aoc-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
            <button class="aoc-primary"><i class="bi bi-check2-circle"></i> {{ $isEdit ? 'Save Changes' : 'Save Add-on' }}</button>
        </div>
    </section>
</form>
