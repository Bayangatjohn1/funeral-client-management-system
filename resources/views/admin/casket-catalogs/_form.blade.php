@php
    $isEdit = $catalog->exists;
    $action = $isEdit ? route('admin.casket-catalogs.update', $catalog) : route('admin.casket-catalogs.store');
    $returnTo = old('return_to', request('return_to', route('admin.casket-catalogs.index')));
@endphp

<style>
    html:not([data-theme='dark']) .page-content { background:#E8E4DC !important; }
    .cc-form { max-width:860px; padding:24px 1rem 34px; color:#2F302C; }
    .cc-card { background:#FAFAF7; border:1px solid #C9C5BB; border-radius:8px; overflow:hidden; }
    .cc-head { padding:20px; border-bottom:1px solid #E1DDD5; }
    .cc-title { font-size:1.2rem; font-weight:950; color:#2F302C; display:flex; gap:10px; align-items:center; }
    .cc-copy { margin-top:5px; color:#5F6B5C; font-size:.9rem; font-weight:650; line-height:1.5; }
    .cc-help { margin-top:7px; color:#6C7467; font-size:.78rem; font-weight:700; line-height:1.4; }
    .cc-body { padding:20px; display:grid; gap:16px; }
    .cc-grid { display:grid; grid-template-columns:1fr 180px; gap:16px; }
    .cc-label { display:block; margin-bottom:8px; font-size:.74rem; font-weight:950; text-transform:uppercase; letter-spacing:.07em; color:#53604F; }
    .cc-form .form-input, .cc-form .form-select, .cc-form textarea { width:100%; min-height:44px; border:1px solid #C9C5BB; border-radius:7px; background:#fff; color:#2F302C; font-size:.92rem; font-weight:650; }
    .cc-money { display:flex; align-items:center; border:1px solid #C9C5BB; border-radius:7px; background:#fff; overflow:hidden; }
    .cc-money span { padding-left:12px; font-weight:950; color:#5F685F; }
    .cc-money .form-input { border:0; border-radius:0; }
    .cc-actions { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #E1DDD5; background:#F8F4EC; }
    .cc-primary { min-height:44px; border:1px solid #2F392E; background:#3E4A3D; color:#fff; border-radius:8px; font-weight:950; padding:0 16px; display:inline-flex; align-items:center; gap:8px; }
    .cc-secondary { min-height:44px; border:1px solid #BDB5A8; background:#fff; color:#3E4A3D; border-radius:8px; font-weight:950; padding:0 16px; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .form-error { margin-top:6px; color:#9E4B3F; font-size:.78rem; font-weight:800; }
    html[data-theme='dark'] .page-content { background:#0f1a2e !important; }
    html[data-theme='dark'] .cc-form { color:#e2ecf9; }
    html[data-theme='dark'] .cc-card { background:#111e33; border-color:#243954; }
    html[data-theme='dark'] .cc-head, html[data-theme='dark'] .cc-actions { background:#13263d; border-color:#243954; }
    html[data-theme='dark'] .cc-title { color:#e2ecf9; }
    html[data-theme='dark'] .cc-copy { color:#9fb6d2; }
    html[data-theme='dark'] .cc-help { color:#9fb6d2; }
    html[data-theme='dark'] .cc-label { color:#7fa6cf; }
    html[data-theme='dark'] .cc-form .form-input, html[data-theme='dark'] .cc-form .form-select, html[data-theme='dark'] .cc-form textarea, html[data-theme='dark'] .cc-money { background:#1a2b41 !important; border-color:#2a3f5f !important; color:#d8ecff !important; }
    html[data-theme='dark'] .cc-money span { color:#7fa6cf; }
    html[data-theme='dark'] .cc-primary { background:#3b82f6; border-color:#60a5fa; }
    html[data-theme='dark'] .cc-secondary { background:#152035; border-color:#2a3f5f; color:#d8ecff; }
    html:not([data-theme='dark']) .page-content {
        background:
            linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
            #C4D2BE !important;
        background-size:44px 44px,44px 44px,16px 16px,auto;
    }
    .cc-form { color:var(--ink); }
    .cc-card { background:#D3DEC9 !important; border-color:var(--border) !important; box-shadow:none !important; }
    .cc-head, .cc-actions { background:#C7D5BE !important; border-color:var(--border) !important; }
    .cc-title { color:var(--ink) !important; font-weight:750 !important; }
    .cc-copy, .cc-help { color:var(--ink-muted) !important; font-weight:600 !important; }
    .cc-label { color:#566653 !important; font-weight:650 !important; }
    .cc-form .form-input, .cc-form .form-select, .cc-form textarea, .cc-money {
        background:#E1E7D9 !important;
        border-color:var(--border) !important;
        color:var(--ink) !important;
        box-shadow:none !important;
    }
    .cc-form .form-input:hover, .cc-form .form-select:hover, .cc-form textarea:hover, .cc-money:hover,
    .cc-form .form-input:focus, .cc-form .form-select:focus, .cc-form textarea:focus {
        background:#EEF3E8 !important;
        border-color:#8EA083 !important;
        box-shadow:none !important;
    }
    .cc-primary, .cc-secondary { cursor:pointer; box-shadow:none !important; }
    .cc-primary { background:#344333 !important; border-color:#344333 !important; color:#fff !important; font-weight:700 !important; }
    .cc-primary:hover { background:#2F3A2E !important; border-color:#2F3A2E !important; }
    .cc-secondary { background:#E1E7D9 !important; border-color:var(--border) !important; color:var(--ink) !important; font-weight:700 !important; }
    .cc-secondary:hover { background:#D3DEC9 !important; border-color:#8EA083 !important; }
    @media (max-width:680px) { .cc-grid { grid-template-columns:1fr; } .cc-actions { display:grid; } }
</style>

<form method="POST" action="{{ $action }}" class="cc-form mx-auto">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    <section class="cc-card">
        <div class="cc-head">
            <div class="cc-title"><i class="bi bi-box2-heart"></i> {{ $isEdit ? 'Edit Casket' : 'Add Casket' }}</div>
            <p class="cc-copy">Casket or coffin option used when building service packages.</p>
        </div>
        <div class="cc-body">
            <div class="cc-grid">
                <div>
                    <label class="cc-label">Casket Name</label>
                    <input type="text" name="name" value="{{ old('name', $catalog->name) }}" class="form-input" required maxlength="150" pattern=".*[A-Za-z].*" title="Enter a casket name with letters." placeholder="e.g. Premium Hardwood Casket">
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="cc-label">Reference Value</label>
                    <div class="cc-money"><span>&#8369;</span><input type="number" step="0.01" min="0.01" inputmode="decimal" name="standard_price" value="{{ old('standard_price', $isEdit ? $catalog->standard_price : '') }}" class="form-input" required placeholder="0.00"></div>
                    <p class="cc-help">Reference value for this catalog item. It helps keep package casket selection consistent.</p>
                    @error('standard_price') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div>
                <label class="cc-label">Type or Material</label>
                <input type="text" name="type_or_material" value="{{ old('type_or_material', $catalog->type_or_material) }}" class="form-input" maxlength="100" placeholder="e.g. Hardwood, Metal, Glass">
                @error('type_or_material') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="cc-label">Description</label>
                <textarea name="description" rows="3" class="form-input" maxlength="500" placeholder="Optional details for staff.">{{ old('description', $catalog->description) }}</textarea>
                @error('description') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="cc-actions">
            <a href="{{ $returnTo }}" class="cc-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
            <button class="cc-primary"><i class="bi bi-check2-circle"></i> {{ $isEdit ? 'Save Changes' : 'Save Casket' }}</button>
        </div>
    </section>
</form>
