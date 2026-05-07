@php
    $initialAddOns = collect($initialAddOns ?? [])->values();
@endphp

<div class="pkg-section pkg-section-alt md:col-span-2" id="addOnsSection">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <div class="pkg-section-title">Optional Add-ons</div>
            <p class="pkg-section-help">Paid extras selectable during Case Intake — added on top of the base package price.</p>
        </div>
        <button type="button" class="pkg-add-button pkg-add-button-primary" id="addOnAddBtn">
            <i class="bi bi-plus-circle"></i> Add Add-on
        </button>
    </div>

    <div class="space-y-2" id="addOnsList">
        @forelse($initialAddOns as $index => $addOn)
            <div class="rounded-xl border border-[#C9C5BB] bg-[#FAFAF7] overflow-hidden" data-addon-row>
                <button type="button" class="addon-toggle w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-[#F3F0E8] transition" aria-expanded="false">
                    <div class="flex items-center gap-3 min-w-0">
                        <i class="bi bi-list-ul text-[#C9C5BB] shrink-0"></i>
                        <span class="font-semibold text-sm text-[#333] truncate addon-name-preview">{{ $addOn['name'] ?? 'Unnamed Add-on' }}</span>
                        <span class="shrink-0 text-xs font-bold text-[#5F685F] bg-[#E8E4DC] px-2 py-0.5 rounded-full addon-price-preview">
                            &#8369;{{ isset($addOn['price']) ? number_format((float)$addOn['price'], 2) : '0.00' }}
                        </span>
                        @if(isset($addOn['is_active']) && !$addOn['is_active'])
                            <span class="addon-inactive-badge shrink-0 text-[10px] font-black uppercase tracking-wide text-[#9E4B3F] bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">Inactive</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <i class="bi bi-chevron-down addon-chevron text-[#5F685F] text-xs transition-transform duration-200"></i>
                        <span class="addon-remove flex items-center justify-center w-7 h-7 text-[#9E4B3F] hover:bg-rose-50 rounded-lg transition cursor-pointer">
                            <i class="bi bi-trash text-xs"></i>
                        </span>
                    </div>
                </button>
                <div class="addon-body hidden border-t border-[#C9C5BB] px-4 py-4">
                    <input type="hidden" name="add_ons[{{ $index }}][id]" value="{{ $addOn['id'] ?? '' }}">
                    <div class="grid gap-3 md:grid-cols-[1fr_160px]">
                        <div>
                            <label class="pkg-label">Add-on Name <span class="text-[#9E4B3F]">*</span></label>
                            <input type="text" name="add_ons[{{ $index }}][name]" value="{{ $addOn['name'] ?? '' }}" class="form-input addon-name-input" maxlength="100" placeholder="e.g., Video presentation with LED screen">
                        </div>
                        <div>
                            <label class="pkg-label">Price <span class="text-[#9E4B3F]">*</span></label>
                            <div class="pkg-money-input">
                                <span>&#8369;</span>
                                <input type="number" step="0.01" min="0" name="add_ons[{{ $index }}][price]" value="{{ $addOn['price'] ?? '' }}" class="form-input addon-price-input" placeholder="0.00">
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="pkg-label">Description <span class="pkg-label-optional">(optional)</span></label>
                            <input type="text" name="add_ons[{{ $index }}][description]" value="{{ $addOn['description'] ?? '' }}" class="form-input" maxlength="255" placeholder="Optional details shown during Case Intake">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-[#5F685F] cursor-pointer">
                            <input type="hidden" name="add_ons[{{ $index }}][is_active]" value="0">
                            <input type="checkbox" name="add_ons[{{ $index }}][is_active]" value="1" class="h-4 w-4 rounded border-[#C9C5BB] text-[#3E4A3D] focus:ring-[#3E4A3D] addon-active-check" {{ ($addOn['is_active'] ?? true) ? 'checked' : '' }}>
                            Active (selectable in Case Intake)
                        </label>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-[#C9C5BB] bg-[#FAFAF7] px-4 py-4 text-sm text-[#5F685F] font-semibold" id="addOnsEmpty">
                No optional add-ons yet. Add paid extras such as video presentation, music band, or singers.
            </div>
        @endforelse
    </div>

    @foreach($errors->get('add_ons.*.name') as $msgs)
        @foreach($msgs as $msg) <div class="form-error text-[#9E4B3F] mt-1">{{ $msg }}</div> @endforeach
    @endforeach
    @foreach($errors->get('add_ons.*.price') as $msgs)
        @foreach($msgs as $msg) <div class="form-error text-[#9E4B3F] mt-1">{{ $msg }}</div> @endforeach
    @endforeach
    <div class="form-error hidden text-[#9E4B3F] mt-1" data-field-error="add_ons"></div>
</div>

<script>
(function () {
    var list   = document.getElementById('addOnsList');
    var addBtn = document.getElementById('addOnAddBtn');
    if (!list || !addBtn) return;

    function rowCount() { return list.querySelectorAll('[data-addon-row]').length; }

    function syncEmpty() {
        var empty = document.getElementById('addOnsEmpty');
        if (empty) empty.style.display = rowCount() > 0 ? 'none' : '';
    }

    function reindex() {
        list.querySelectorAll('[data-addon-row]').forEach(function (row, idx) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/add_ons\[\d+\]/, 'add_ons[' + idx + ']');
            });
        });
    }

    function fmtPrice(val) {
        var p = parseFloat(val);
        return isNaN(p) ? '₱0.00' : '₱' + p.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updatePreview(row) {
        var n = row.querySelector('.addon-name-input');
        var p = row.querySelector('.addon-price-input');
        var np = row.querySelector('.addon-name-preview');
        var pp = row.querySelector('.addon-price-preview');
        if (np && n) np.textContent = n.value.trim() || 'Unnamed Add-on';
        if (pp && p) pp.textContent = fmtPrice(p.value);
    }

    function wireToggle(row) {
        var btn     = row.querySelector('.addon-toggle');
        var body    = row.querySelector('.addon-body');
        var chevron = row.querySelector('.addon-chevron');
        if (!btn || !body) return;
        btn.addEventListener('click', function (e) {
            if (e.target.closest('.addon-remove')) return;
            var hidden = body.classList.toggle('hidden');
            if (chevron) chevron.style.transform = hidden ? '' : 'rotate(180deg)';
            btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        });
    }

    function wireRemove(row) {
        var btn = row.querySelector('.addon-remove');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            row.remove(); reindex(); syncEmpty();
        });
    }

    function wirePreview(row) {
        ['.addon-name-input', '.addon-price-input'].forEach(function (sel) {
            var el = row.querySelector(sel);
            if (el) el.addEventListener('input', function () { updatePreview(row); });
        });
    }

    function wireActiveBadge(row) {
        var chk    = row.querySelector('.addon-active-check');
        var header = row.querySelector('.addon-toggle .flex.items-center.gap-3');
        if (!chk || !header) return;
        chk.addEventListener('change', function () {
            var ex = header.querySelector('.addon-inactive-badge');
            if (!chk.checked && !ex) {
                var b = document.createElement('span');
                b.className = 'addon-inactive-badge shrink-0 text-[10px] font-black uppercase tracking-wide text-[#9E4B3F] bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded';
                b.textContent = 'Inactive';
                header.appendChild(b);
            } else if (chk.checked && ex) { ex.remove(); }
        });
    }

    list.querySelectorAll('[data-addon-row]').forEach(function (row) {
        wireToggle(row); wireRemove(row); wirePreview(row); wireActiveBadge(row);
    });
    syncEmpty();

    addBtn.addEventListener('click', function () {
        var idx = rowCount();
        var row = document.createElement('div');
        row.className = 'rounded-xl border border-[#C9C5BB] bg-[#FAFAF7] overflow-hidden';
        row.setAttribute('data-addon-row', '');
        row.innerHTML =
            '<button type="button" class="addon-toggle w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-[#F3F0E8] transition" aria-expanded="true">' +
            '<div class="flex items-center gap-3 min-w-0"><i class="bi bi-list-ul text-[#C9C5BB] shrink-0"></i>' +
            '<span class="font-semibold text-sm text-[#333] truncate addon-name-preview">New Add-on</span>' +
            '<span class="shrink-0 text-xs font-bold text-[#5F685F] bg-[#E8E4DC] px-2 py-0.5 rounded-full addon-price-preview">₱0.00</span></div>' +
            '<div class="flex items-center gap-2 shrink-0">' +
            '<i class="bi bi-chevron-down addon-chevron text-[#5F685F] text-xs transition-transform duration-200" style="transform:rotate(180deg)"></i>' +
            '<span class="addon-remove flex items-center justify-center w-7 h-7 text-[#9E4B3F] hover:bg-rose-50 rounded-lg transition cursor-pointer"><i class="bi bi-trash text-xs"></i></span>' +
            '</div></button>' +
            '<div class="addon-body border-t border-[#C9C5BB] px-4 py-4">' +
            '<input type="hidden" name="add_ons[' + idx + '][id]" value="">' +
            '<div class="grid gap-3 md:grid-cols-[1fr_160px]">' +
            '<div><label class="pkg-label">Add-on Name <span class="text-[#9E4B3F]">*</span></label>' +
            '<input type="text" name="add_ons[' + idx + '][name]" class="form-input addon-name-input" maxlength="100" placeholder="e.g., Video presentation with LED screen"></div>' +
            '<div><label class="pkg-label">Price <span class="text-[#9E4B3F]">*</span></label>' +
            '<div class="pkg-money-input"><span>&#8369;</span>' +
            '<input type="number" step="0.01" min="0" name="add_ons[' + idx + '][price]" class="form-input addon-price-input" placeholder="0.00"></div></div>' +
            '<div class="md:col-span-2"><label class="pkg-label">Description <span class="pkg-label-optional">(optional)</span></label>' +
            '<input type="text" name="add_ons[' + idx + '][description]" class="form-input" maxlength="255" placeholder="Optional details shown during Case Intake"></div>' +
            '</div><div class="mt-3"><label class="inline-flex items-center gap-2 text-sm font-medium text-[#5F685F] cursor-pointer">' +
            '<input type="hidden" name="add_ons[' + idx + '][is_active]" value="0">' +
            '<input type="checkbox" name="add_ons[' + idx + '][is_active]" value="1" class="h-4 w-4 rounded border-[#C9C5BB] text-[#3E4A3D] focus:ring-[#3E4A3D] addon-active-check" checked>' +
            'Active (selectable in Case Intake)</label></div></div>';
        list.appendChild(row);
        wireToggle(row); wireRemove(row); wirePreview(row); wireActiveBadge(row);
        syncEmpty();
        var ni = row.querySelector('.addon-name-input');
        if (ni) ni.focus();
    });
})();
</script>
