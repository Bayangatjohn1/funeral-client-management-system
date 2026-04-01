@extends('layouts.panel')

@section('page_title','Edit Funeral Case')

@section('content')
<form id="caseEditForm" method="POST" action="{{ route('funeral-cases.update', $funeral_case) }}" class="max-w-4xl w-full mx-auto space-y-6">
@csrf
@method('PUT')

<div class="p-5 md:p-6 space-y-5 w-full">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Edit Funeral Case</h1>
            <p class="text-sm text-slate-500">Update client, deceased, package and status. Totals refresh automatically.</p>
        </div>
        <div class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-100">
            Case {{ $funeral_case->case_code }}
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="label-section">Case Code</label>
            <input type="text" value="{{ $funeral_case->case_code }}" class="form-input bg-slate-100 font-semibold" readonly>
        </div>

        <div>
            <label class="label-section">Client</label>
            <select name="client_id" id="client_id" class="form-select" required>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id', $funeral_case->client_id)==$client->id ? 'selected' : '' }}>
                        {{ $client->full_name }}
                    </option>
                @endforeach
            </select>
            @error('client_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Deceased</label>
            <select name="deceased_id" id="deceased_id" class="form-select" required>
                @foreach($deceaseds as $deceased)
                    <option
                        value="{{ $deceased->id }}"
                        data-client-id="{{ $deceased->client_id }}"
                        data-age="{{ $deceased->age }}"
                        {{ old('deceased_id', $funeral_case->deceased_id)==$deceased->id ? 'selected' : '' }}
                    >
                        {{ $deceased->full_name }}
                    </option>
                @endforeach
            </select>
            @error('deceased_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="label-section">Package</label>
            <select name="package_id" id="package_id" class="form-select" required>
                @foreach($packages as $pkg)
                    @php($promoNow = $pkg->promo_is_active && (!$pkg->promo_starts_at || $pkg->promo_starts_at->lte(now())) && (!$pkg->promo_ends_at || $pkg->promo_ends_at->gte(now())))
                    <option
                        value="{{ $pkg->id }}"
                        data-price="{{ $pkg->price }}"
                        data-promo-now="{{ $promoNow ? '1' : '0' }}"
                        data-promo-type="{{ $pkg->promo_value_type }}"
                        data-promo-value="{{ $pkg->promo_value }}"
                        data-promo-label="{{ $pkg->promo_label }}"
                        {{ (string) old('package_id', $funeral_case->package_id) === (string) $pkg->id ? 'selected' : '' }}
                    >
                        {{ $pkg->name }} — {{ number_format($pkg->price, 2) }}
                    </option>
                @endforeach
            </select>
            @error('package_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2 items-start">
        <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">
            <div class="text-[11px] font-black uppercase tracking-widest mb-2">Discount Policy</div>
            <p>System auto-applies the higher discount between Senior and active Package Promo (no stacking).</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm space-y-1">
            <div class="text-[11px] font-black uppercase tracking-widest text-slate-500">Estimates</div>
            <div class="flex justify-between"><span>Subtotal</span> <span id="summary_subtotal">0.00</span></div>
            <div class="flex justify-between"><span>Discount Source</span> <span id="summary_source">None</span></div>
            <div class="flex justify-between"><span>Discount</span> <span id="summary_discount">0.00</span></div>
            <div class="pt-2 border-t border-slate-200 flex justify-between text-base font-bold text-slate-900">
                <span>Estimated Total</span> <span id="summary_total">0.00</span>
            </div>
        </div>
    </div>

    <div class="md:w-1/2 space-y-2">
        <div class="flex items-center justify-between">
            <label class="label-section mb-0">Case Status</label>
            <span class="text-[11px] uppercase tracking-wide font-bold text-slate-400">Set this first</span>
        </div>
        <select name="case_status" class="form-select ring-2 ring-offset-2 ring-[var(--brand-mid)] focus:ring-[var(--brand-mid)] shadow-md" required>
            <option value="DRAFT" {{ old('case_status', $funeral_case->case_status)=='DRAFT' ? 'selected' : '' }}>Draft</option>
            <option value="ACTIVE" {{ old('case_status', $funeral_case->case_status)=='ACTIVE' ? 'selected' : '' }}>Active</option>
            <option value="COMPLETED" {{ old('case_status', $funeral_case->case_status)=='COMPLETED' ? 'selected' : '' }}>Completed</option>
        </select>
        @error('case_status') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="flex flex-wrap gap-2 pt-1">
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save Changes
        </button>
    </div>
</div>
</form>

<script>
    (function () {
        const packageSelect = document.getElementById('package_id');
        const clientSelect = document.getElementById('client_id');
        const deceasedSelect = document.getElementById('deceased_id');
        const seniorDiscountPercent = Number(@json((float) config('funeral.senior_discount_percent', 20)));
        const subtotalEl = document.getElementById('summary_subtotal');
        const sourceEl = document.getElementById('summary_source');
        const discountEl = document.getElementById('summary_discount');
        const totalEl = document.getElementById('summary_total');

        function toNumber(value) {
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : 0;
        }

        function format(n) {
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function resolveDiscount(subtotal) {
            const deceased = deceasedSelect.options[deceasedSelect.selectedIndex];
            const age = toNumber(deceased ? deceased.dataset.age : 0);
            const selectedPackage = packageSelect.options[packageSelect.selectedIndex];

            let seniorAmount = 0;
            if (age >= 60) {
                seniorAmount = subtotal * Math.min(Math.max(seniorDiscountPercent, 0), 100) / 100;
            }

            let promoAmount = 0;
            let promoSource = '';
            if (selectedPackage && selectedPackage.dataset.promoNow === '1') {
                const promoType = String(selectedPackage.dataset.promoType || '').toUpperCase();
                const promoValue = toNumber(selectedPackage.dataset.promoValue);
                if (promoType === 'PERCENT') {
                    promoAmount = subtotal * Math.min(Math.max(promoValue, 0), 100) / 100;
                } else {
                    promoAmount = Math.min(promoValue, subtotal);
                }
                if (promoAmount > 0) {
                    promoSource = selectedPackage.dataset.promoLabel
                        ? `Promo: ${selectedPackage.dataset.promoLabel}`
                        : 'Promo';
                }
            }

            if (seniorAmount >= promoAmount && seniorAmount > 0) {
                return { amount: seniorAmount, source: `Senior (${seniorDiscountPercent}%)` };
            }
            if (promoAmount > 0) {
                return { amount: promoAmount, source: promoSource || 'Promo' };
            }
            return { amount: 0, source: 'None' };
        }

        function render() {
            const selected = packageSelect.options[packageSelect.selectedIndex];
            const subtotal = toNumber(selected ? selected.dataset.price : 0);
            const resolvedDiscount = resolveDiscount(subtotal);
            const discount = Math.min(resolvedDiscount.amount, subtotal);

            const total = Math.max(subtotal - discount, 0);
            subtotalEl.textContent = format(subtotal);
            sourceEl.textContent = resolvedDiscount.source;
            discountEl.textContent = format(discount);
            totalEl.textContent = format(total);
        }

        function filterDeceasedByClient() {
            const clientId = clientSelect ? clientSelect.value : '';
            Array.from(deceasedSelect.options).forEach((opt) => {
                const belongsTo = opt.dataset.clientId;
                opt.hidden = clientId && belongsTo !== clientId;
            });
        }

        [packageSelect, deceasedSelect].forEach((el) => {
            if (el) {
                el.addEventListener('change', render);
            }
        });

        if (clientSelect && deceasedSelect) {
            clientSelect.addEventListener('change', filterDeceasedByClient);
            filterDeceasedByClient();
        }

        render();
    })();
</script>
@endsection

