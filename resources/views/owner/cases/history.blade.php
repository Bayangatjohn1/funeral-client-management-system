@extends('layouts.panel')

@section('page_title', 'Global Case History')
@section('page_desc', 'Review completed case records across all branches.')

@section('content')
<div class="owner-page-shell">
@if($errors->any())
    <div class="flash-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="filter-panel mb-5">
    @include('partials.case_filter_toolbar', [
        'action' => route('owner.history'),
        'resetUrl' => route('owner.history'),
        'branchMode' => 'all',
        'branchId' => $branchId,
        'branches' => $branches,
        'datePreset' => $datePreset ?? '',
        'dateFrom' => $dateFrom ?? null,
        'dateTo' => $dateTo ?? null,
        'intermentFrom' => $intermentFrom ?? null,
        'intermentTo' => $intermentTo ?? null,
        'serviceTypes' => $serviceTypes ?? collect(),
        'packages' => $packages ?? collect(),
        'showVerificationStatus' => false,
        'showPackage' => true,
        'showEncodedBy' => false,
    ])
</div>

<div class="list-card">
    <div class="list-card-header">
        <div>
            <div class="list-card-title">Global Case Timeline</div>
            <div class="list-card-copy">Track completed case records, branch output, and payment health across the business.</div>
        </div>
    </div>

    <div class="table-wrapper rounded-none border-0">
        <table class="table-base text-sm">
            <thead>
                <tr>
                    <th class="text-left">Case Code</th>
                    <th class="text-left">Date Encoded</th>
                    <th class="text-left">Branch</th>
                    <th class="text-left">Client</th>
                    <th class="text-left">Deceased</th>
                    <th class="text-left">Service Type</th>
                    <th class="text-left">Interment</th>
                    <th class="text-left">Package</th>
                    <th class="text-left">Total Amount</th>
                    <th class="text-left">Payment</th>
                    <th class="text-left">Case Status</th>
                    <th class="text-left">Total Paid</th>
                    <th class="text-left">Balance</th>
                    <th class="text-left">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr>
                    <td>{{ $case->case_code }}</td>
                    <td>{{ $case->created_at?->format('Y-m-d') }}</td>
                    <td>{{ $case->branch?->branch_code ?? '-' }}</td>
                    <td>{{ $case->client?->full_name ?? '-' }}</td>
                    <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                    <td>{{ $case->service_type ?? '-' }}</td>
                    <td>{{ $case->deceased?->interment_at?->format('Y-m-d H:i') ?? $case->deceased?->interment?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $case->service_package ?? '-' }}</td>
                    <td>{{ number_format((float) $case->total_amount, 2) }}</td>
                    <td>
                        <span class="{{
                            $case->payment_status === 'PAID'
                                ? 'status-pill-success'
                                : ($case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger')
                        }}">
                            {{ $case->payment_status }}
                        </span>
                    </td>
                    <td><x-status-badge :status="$case->case_status" /></td>
                    <td>{{ number_format((float) $case->total_paid, 2) }}</td>
                    <td>{{ number_format((float) $case->balance_amount, 2) }}</td>
                    <td>
                        <button
                            type="button"
                            class="table-action-link open-case-modal"
                            data-url="{{ route('owner.cases.show', $case) }}"
                        >View Details</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="py-6 text-center text-slate-500">No records found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $cases->links() }}
</div>

{{-- Case view modal --}}
<div id="caseViewOverlay" style="display:none; position:fixed; inset:0; z-index:400; background:rgba(0,0,0,0.55); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); align-items:center; justify-content:center;">
    <div id="caseViewSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200"
         style="background:var(--card);border-color:var(--border);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-panel);flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:var(--ink);">Case Details</span>
            <button id="caseViewClose" type="button" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--ink-muted);cursor:pointer;" aria-label="Close">
                <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
            </button>
        </div>
        <div id="caseViewContent" class="overflow-y-auto" style="max-height:calc(92vh - 54px);padding:16px;">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;gap:10px;">
                <div style="width:28px;height:28px;border-radius:50%;border:2px solid var(--border);border-top-color:var(--brand);animation:spin 1s linear infinite;"></div>
                <span style="font-size:13px;color:var(--ink-muted);">Loading…</span>
            </div>
        </div>
    </div>
</div>

</div>

<script>
(function () {
    const overlay  = document.getElementById('caseViewOverlay');
    const sheet    = document.getElementById('caseViewSheet');
    const content  = document.getElementById('caseViewContent');
    const closeBtn = document.getElementById('caseViewClose');

    const loadingHtml = `
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;gap:10px;">
            <div style="width:28px;height:28px;border-radius:50%;border:2px solid var(--border);border-top-color:var(--brand);animation:spin 1s linear infinite;"></div>
            <span style="font-size:13px;color:var(--ink-muted);">Loading…</span>
        </div>`;

    const show = () => {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            sheet.classList.remove('scale-95', 'opacity-0');
            sheet.classList.add('scale-100', 'opacity-100');
        });
    };

    const hide = () => {
        sheet.classList.add('scale-95', 'opacity-0');
        sheet.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
            content.innerHTML = loadingHtml;
        }, 180);
    };

    const load = async (url) => {
        content.innerHTML = loadingHtml;
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const payload = doc.querySelector('#caseViewContent');
            if (payload) {
                content.innerHTML = payload.innerHTML;
                doc.querySelectorAll('script').forEach(oldScript => {
                    const script = document.createElement('script');
                    script.textContent = oldScript.textContent;
                    content.appendChild(script);
                });
            } else {
                content.innerHTML = `<div style="padding:20px;font-size:13px;color:#b91c1c;">Unable to load case details.</div>`;
            }
        } catch {
            content.innerHTML = `<div style="padding:20px;font-size:13px;color:#b91c1c;">Network error. Please try again.</div>`;
        }
    };

    document.querySelectorAll('.open-case-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            show();
            load(btn.dataset.url);
        });
    });

    closeBtn.addEventListener('click', hide);
    overlay.addEventListener('click', e => { if (e.target === overlay) hide(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.style.display !== 'none') hide(); });
})();
</script>
@endsection
