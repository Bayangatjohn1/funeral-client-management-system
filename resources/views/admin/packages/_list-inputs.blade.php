@php
    $initialInclusions = array_values(array_filter($initialInclusions ?? [], fn ($item) => trim((string) $item) !== ''));
    $initialFreebies   = array_values(array_filter($initialFreebies  ?? [], fn ($item) => trim((string) $item) !== ''));
@endphp

{{-- Inclusions --}}
<div class="md:col-span-2" data-tag-field="inclusions">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
        <label class="pkg-label mb-0">Inclusions <span class="text-[#9E4B3F]">*</span></label>
        <span class="text-[11px] font-bold text-[#5F685F]" data-tag-count>{{ count($initialInclusions) }} item(s)</span>
    </div>
    <p class="pkg-help mb-3">Type an item then press Enter or click Add.</p>
    <div class="flex gap-2 mb-3">
        <input type="text" class="form-input flex-1 min-w-0" data-tag-input
               placeholder="e.g., Embalming service" maxlength="255" autocomplete="off">
        <button type="button" class="pkg-add-button shrink-0" data-tag-add>
            <i class="bi bi-plus-circle"></i> Add
        </button>
    </div>
    <ul class="space-y-1.5" data-tag-list>
        @foreach($initialInclusions as $i => $item)
            <li class="flex items-center gap-2 rounded-lg border border-[#C9C5BB] bg-white px-3 py-2" data-tag-row>
                <span class="shrink-0 w-5 h-5 flex items-center justify-center rounded-full bg-[#3E4A3D] text-white text-[10px] font-black">{{ $i + 1 }}</span>
                <span class="flex-1 text-sm font-medium text-[#333] break-all">{{ $item }}</span>
                <button type="button" class="shrink-0 text-[#9E4B3F] hover:bg-rose-50 rounded-lg p-1 transition" data-tag-remove aria-label="Remove">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
                <input type="hidden" name="inclusions[]" value="{{ $item }}">
            </li>
        @endforeach
    </ul>
    @if(empty($initialInclusions))
        <div class="rounded-lg border border-dashed border-[#C9C5BB] bg-[#FAFAF7] px-3 py-3 text-sm text-[#5F685F] font-medium" data-tag-empty>
            No inclusions added yet.
        </div>
    @endif
    @error('inclusions') <div class="form-error mt-2">{{ $message }}</div> @enderror
    @foreach($errors->get('inclusions.*') as $msgs)
        @foreach($msgs as $msg) <div class="form-error mt-1">{{ $msg }}</div> @endforeach
    @endforeach
    <div class="form-error hidden mt-1" data-field-error="inclusions"></div>
</div>

{{-- Freebies --}}
<div class="md:col-span-2" data-tag-field="freebies">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
        <label class="pkg-label mb-0">Freebies <span class="text-[11px] font-normal text-[#5F685F] normal-case tracking-normal">(optional)</span></label>
        <span class="text-[11px] font-bold text-[#5F685F]" data-tag-count>{{ count($initialFreebies) }} item(s)</span>
    </div>
    <p class="pkg-help mb-3">Type a free item then press Enter or click Add.</p>
    <div class="flex gap-2 mb-3">
        <input type="text" class="form-input flex-1 min-w-0" data-tag-input
               placeholder="e.g., Flower arrangement" maxlength="255" autocomplete="off">
        <button type="button" class="pkg-add-button shrink-0" data-tag-add>
            <i class="bi bi-plus-circle"></i> Add
        </button>
    </div>
    <ul class="space-y-1.5" data-tag-list>
        @foreach($initialFreebies as $i => $item)
            <li class="flex items-center gap-2 rounded-lg border border-[#C9C5BB] bg-white px-3 py-2" data-tag-row>
                <span class="shrink-0 w-5 h-5 flex items-center justify-center rounded-full bg-amber-500 text-white text-[10px] font-black">{{ $i + 1 }}</span>
                <span class="flex-1 text-sm font-medium text-[#333] break-all">{{ $item }}</span>
                <button type="button" class="shrink-0 text-[#9E4B3F] hover:bg-rose-50 rounded-lg p-1 transition" data-tag-remove aria-label="Remove">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
                <input type="hidden" name="freebies[]" value="{{ $item }}">
            </li>
        @endforeach
    </ul>
    @if(empty($initialFreebies))
        <div class="rounded-lg border border-dashed border-[#C9C5BB] bg-[#FAFAF7] px-3 py-3 text-sm text-[#5F685F] font-medium" data-tag-empty>
            No freebies added yet.
        </div>
    @endif
    @error('freebies') <div class="form-error mt-2">{{ $message }}</div> @enderror
    @foreach($errors->get('freebies.*') as $msgs)
        @foreach($msgs as $msg) <div class="form-error mt-1">{{ $msg }}</div> @endforeach
    @endforeach
    <div class="form-error hidden mt-1" data-field-error="freebies"></div>
</div>

{{-- Tag-field JS (self-contained) --}}
<script>
(function () {
    document.querySelectorAll('[data-tag-field]').forEach(function (wrap) {
        var field  = wrap.dataset.tagField;
        var input  = wrap.querySelector('[data-tag-input]');
        var addBtn = wrap.querySelector('[data-tag-add]');
        var list   = wrap.querySelector('[data-tag-list]');
        var countEl= wrap.querySelector('[data-tag-count]');
        var errEl  = wrap.querySelector('[data-field-error="' + field + '"]');
        var isGreen= field === 'inclusions';

        function syncCount() {
            var rows = list.querySelectorAll('[data-tag-row]');
            if (countEl) countEl.textContent = rows.length + ' item(s)';
            var empty = wrap.querySelector('[data-tag-empty]');
            if (empty) empty.style.display = rows.length > 0 ? 'none' : '';
            rows.forEach(function (row, i) {
                var badge = row.querySelector('span:first-child');
                if (badge) badge.textContent = i + 1;
            });
        }

        function addItem(raw) {
            var val = raw.replace(/\s+/g, ' ').trim();
            if (!val) return;
            var li = document.createElement('li');
            li.setAttribute('data-tag-row', '');
            li.className = 'flex items-center gap-2 rounded-lg border border-[#C9C5BB] bg-white px-3 py-2';

            var badge = document.createElement('span');
            badge.className = 'shrink-0 w-5 h-5 flex items-center justify-center rounded-full text-white text-[10px] font-black ' + (isGreen ? 'bg-[#3E4A3D]' : 'bg-amber-500');
            badge.textContent = list.querySelectorAll('[data-tag-row]').length + 1;

            var label = document.createElement('span');
            label.className = 'flex-1 text-sm font-medium text-[#333] break-all';
            label.textContent = val;

            var rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.setAttribute('data-tag-remove', '');
            rmBtn.setAttribute('aria-label', 'Remove');
            rmBtn.className = 'shrink-0 text-[#9E4B3F] hover:bg-rose-50 rounded-lg p-1 transition';
            rmBtn.innerHTML = '<i class="bi bi-x-lg text-xs"></i>';
            rmBtn.addEventListener('click', function () { li.remove(); syncCount(); });

            var hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = field + '[]';
            hidden.value = val;

            li.appendChild(badge); li.appendChild(label); li.appendChild(rmBtn); li.appendChild(hidden);
            list.appendChild(li);
            syncCount();
            if (errEl) { errEl.textContent = ''; errEl.classList.add('hidden'); }
        }

        list.querySelectorAll('[data-tag-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('[data-tag-row]').remove(); syncCount();
            });
        });
        syncCount();

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addItem(input.value); input.value = ''; }
        });
        addBtn.addEventListener('click', function () {
            addItem(input.value); input.value = ''; input.focus();
        });
    });
})();
</script>
