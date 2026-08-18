@php
    $isEdit = $catalog->exists;
    $action = $isEdit ? route('admin.freebie-catalogs.update', $catalog) : route('admin.freebie-catalogs.store');
    $returnTo = old('return_to', request('return_to', route('admin.freebie-catalogs.index')));
    $unitOptions = ['item', 'set', 'piece', 'pcs', 'copy', 'bundle'];
@endphp

<style>
    html:not([data-theme='dark']) .page-content { background:#E8E4DC !important; }
    .fbc-form { max-width:900px; padding:24px 1rem 34px; color:#2F302C; }
    .fbc-card { background:#FAFAF7; border:1px solid #C9C5BB; border-radius:8px; overflow:hidden; }
    .fbc-head { padding:20px; border-bottom:1px solid #E1DDD5; }
    .fbc-title { font-size:1.2rem; font-weight:950; color:#2F302C; display:flex; gap:10px; align-items:center; }
    .fbc-copy { margin-top:5px; color:#5F6B5C; font-size:.9rem; font-weight:650; line-height:1.5; max-width:720px; }
    .fbc-note { margin-top:12px; display:flex; gap:10px; border:1px solid #D8D0C4; background:#F8F4EC; border-radius:8px; padding:12px; color:#5b4537; font-size:.82rem; font-weight:750; line-height:1.5; }
    .fbc-body { padding:20px; display:grid; gap:16px; }
    .fbc-grid { display:grid; grid-template-columns:1fr 220px; gap:16px; }
    .fbc-label { display:block; margin-bottom:8px; font-size:.74rem; font-weight:950; text-transform:uppercase; letter-spacing:.07em; color:#53604F; }
    .fbc-form .form-input { width:100%; min-height:44px; border:1px solid #C9C5BB; border-radius:7px; background:#fff; color:#2F302C; font-size:.92rem; font-weight:650; }
    .fbc-actions { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #E1DDD5; background:#F8F4EC; }
    .fbc-primary { min-height:44px; border:1px solid #2F392E; background:#3E4A3D; color:#fff; border-radius:8px; font-weight:950; padding:0 16px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; }
    .fbc-secondary { min-height:44px; border:1px solid #BDB5A8; background:#fff; color:#3E4A3D; border-radius:8px; font-weight:950; padding:0 16px; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .form-error { margin-top:6px; color:#9E4B3F; font-size:.78rem; font-weight:800; }
    html[data-theme='dark'] .page-content { background:#0f1a2e !important; }
    html[data-theme='dark'] .fbc-form { color:#e2ecf9; }
    html[data-theme='dark'] .fbc-card { background:#111e33; border-color:#243954; }
    html[data-theme='dark'] .fbc-head, html[data-theme='dark'] .fbc-actions { background:#13263d; border-color:#243954; }
    html[data-theme='dark'] .fbc-title { color:#e2ecf9; }
    html[data-theme='dark'] .fbc-copy { color:#9fb6d2; }
    html[data-theme='dark'] .fbc-note { background:#16263b; border-color:#2a3f5f; color:#c7d8ee; }
    html[data-theme='dark'] .fbc-label { color:#7fa6cf; }
    html[data-theme='dark'] .fbc-form .form-input { background:#1a2b41 !important; border-color:#2a3f5f !important; color:#d8ecff !important; }
    html[data-theme='dark'] .fbc-primary { background:#3b82f6; border-color:#60a5fa; }
    html[data-theme='dark'] .fbc-secondary { background:#152035; border-color:#2a3f5f; color:#d8ecff; }
    html:not([data-theme='dark']) .page-content {
        background:
            linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
            #C4D2BE !important;
        background-size:44px 44px,44px 44px,16px 16px,auto;
    }
    .fbc-form { color:var(--ink); }
    .fbc-card { background:#D3DEC9 !important; border-color:var(--border) !important; box-shadow:none !important; }
    .fbc-head, .fbc-actions { background:#C7D5BE !important; border-color:var(--border) !important; }
    .fbc-title { color:var(--ink) !important; font-weight:750 !important; }
    .fbc-copy { color:var(--ink-muted) !important; font-weight:600 !important; }
    .fbc-note { background:#E1E7D9 !important; border-color:var(--border) !important; color:var(--ink-muted) !important; box-shadow:none !important; }
    .fbc-label { color:#566653 !important; font-weight:650 !important; }
    .fbc-form .form-input {
        background:#E1E7D9 !important;
        border-color:var(--border) !important;
        color:var(--ink) !important;
        box-shadow:none !important;
    }
    .fbc-form .form-input:hover, .fbc-form .form-input:focus {
        background:#EEF3E8 !important;
        border-color:#8EA083 !important;
        box-shadow:none !important;
    }
    .fbc-primary, .fbc-secondary { box-shadow:none !important; }
    .fbc-primary { background:#344333 !important; border-color:#344333 !important; color:#fff !important; font-weight:700 !important; }
    .fbc-primary:hover { background:#2F3A2E !important; border-color:#2F3A2E !important; }
    .fbc-secondary { background:#E1E7D9 !important; border-color:var(--border) !important; color:var(--ink) !important; font-weight:700 !important; }
    .fbc-secondary:hover { background:#D3DEC9 !important; border-color:#8EA083 !important; }
    @media (max-width:720px) { .fbc-grid { grid-template-columns:1fr; } .fbc-actions { display:grid; } }
</style>

<form method="POST" action="{{ $action }}" class="fbc-form mx-auto">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    <section class="fbc-card">
        <div class="fbc-head">
            <div class="fbc-title"><i class="bi bi-gift"></i> {{ $isEdit ? 'Edit Freebie' : 'Add Freebie' }}</div>
            <p class="fbc-copy">Reusable freebies for service package setup. Admins select from this list when defining package inclusions.</p>
            <div class="fbc-note"><i class="bi bi-info-circle"></i><span>Configured freebies appear in Service Package setup. Archive a freebie to hide it from future package selections while keeping historical package records intact.</span></div>
        </div>
        <div class="fbc-body">
            <div class="fbc-grid">
                <div>
                    <label class="fbc-label">Freebie Name</label>
                    <input type="text" name="name" value="{{ old('name', $catalog->name) }}" class="form-input" required maxlength="150" pattern="[A-Za-zÀ-ž][A-Za-zÀ-ž\s,'()/&-]*" title="Use letters and normal separators only. Numbers and unnecessary symbols are not allowed." placeholder="e.g. Memorial folder">
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="fbc-label">Default Unit</label>
                    <input list="freebie_unit_options" type="text" name="default_unit" value="{{ old('default_unit', $catalog->default_unit ?: 'item') }}" class="form-input" required maxlength="40" pattern=".*[A-Za-z].*" title="Enter a unit with letters." placeholder="item, set, copy">
                    <datalist id="freebie_unit_options">
                        @foreach($unitOptions as $unit)
                            <option value="{{ $unit }}"></option>
                        @endforeach
                    </datalist>
                    @error('default_unit') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="fbc-actions">
            <a href="{{ $returnTo }}" class="fbc-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
            <button class="fbc-primary"><i class="bi bi-check2-circle"></i> {{ $isEdit ? 'Save Changes' : 'Save Freebie' }}</button>
        </div>
    </section>
</form>
