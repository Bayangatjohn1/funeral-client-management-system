@extends('layouts.panel')

@section('page_title', 'Active Cases')

@section('content')
@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

@if(session('summary'))
    <div class="list-card mb-4 p-5 text-sm text-slate-700">
        <div class="mb-3 text-base font-semibold text-slate-900">Last Saved Summary</div>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            <div><span class="font-semibold text-slate-900">Package:</span> {{ session('summary.package') }}</div>
            <div><span class="font-semibold text-slate-900">Subtotal:</span> {{ number_format(session('summary.subtotal'), 2) }}</div>
            <div><span class="font-semibold text-slate-900">Discount:</span> {{ number_format(session('summary.discount'), 2) }}</div>
            <div><span class="font-semibold text-slate-900">Discount Rule:</span> {{ session('summary.discount_source', 'NONE') }}</div>
            <div><span class="font-semibold text-slate-900">Total:</span> {{ number_format(session('summary.total'), 2) }}</div>
            <div><span class="font-semibold text-slate-900">Payment Status:</span> {{ session('summary.payment_status') }}</div>
        </div>
    </div>
@endif

@if(!empty($canEncodeAnyBranch) && $canEncodeAnyBranch)
    <div class="flash-warning">
        Other-branch records are completed-only. Check them under <strong>Completed Cases</strong> - <strong>Other Branch Completed Reports</strong>.
    </div>
@endif

@if(request()->boolean('open_wizard'))
    <div class="mb-4">
        @php($showCancelButton = false)
        @php($cancelUrl = route('funeral-cases.index', ['record_scope' => $recordScope ?? 'main']))
        @php($formAction = route('intake.main.store'))
        @php($entryMode = 'main')
        @include('staff.intake._form')
    </div>
@endif

@unless(request()->boolean('open_wizard'))
    <div class="filter-panel">
        <form method="GET" action="{{ route('funeral-cases.index') }}" class="space-y-4">
            <input type="hidden" name="record_scope" value="{{ $recordScope ?? 'main' }}">

            <div class="filter-grid">
                <input
                    name="q"
                    value="{{ request('q') }}"
                    class="form-input w-full md:w-80"
                    placeholder="Search client, deceased, or case..."
                    pattern="[A-Za-z0-9.'\\- ]+"
                    title="Letters, numbers, spaces, apostrophes, periods, and hyphens only"
                    onchange="this.form.submit()">

                <select name="case_status" class="form-select w-full md:w-52" onchange="this.form.submit()">
                    <option value="">All Draft / Active</option>
                    <option value="DRAFT" {{ request('case_status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="ACTIVE" {{ request('case_status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                </select>

                <select name="payment_status" class="form-select w-full md:w-52" onchange="this.form.submit()">
                    <option value="">All Payment Status</option>
                    <option value="UNPAID" {{ request('payment_status') === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                    <option value="PARTIAL" {{ request('payment_status') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                    <option value="PAID" {{ request('payment_status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                </select>

                <div></div>

                <div></div>

                <input type="date" name="request_date_from" value="{{ request('request_date_from') }}" class="form-input w-full md:w-44" title="Request date from" onchange="this.form.submit()">
                <input type="date" name="request_date_to" value="{{ request('request_date_to') }}" class="form-input w-full md:w-44" title="Request date to" onchange="this.form.submit()">
            </div>

            <div class="filter-actions">
                <a href="{{ route('funeral-cases.index', ['record_scope' => $recordScope ?? 'main']) }}" class="btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <div class="list-card mt-4">
        <div class="list-card-header">
            <div>
                <div class="list-card-title">Active Case Records</div>
                <div class="list-card-copy">Browse draft and ongoing main-branch cases with live payment totals.</div>
            </div>
        </div>

        <div class="table-wrapper rounded-none border-0">
            <table class="table-base text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Case ID</th>
                        <th class="text-left">Request Date</th>
                        <th class="text-left">Client</th>
                        <th class="text-left">Contact</th>
                        <th class="text-left">Deceased</th>
                        <th class="text-left">Interment</th>
                <th class="text-left">Package</th>
                <th class="text-left">Total</th>
                <th class="text-left">Total Paid</th>
                        <th class="text-left">Balance</th>
                        <th class="text-left">Case Status</th>
                        <th class="text-left">Payment Status</th>
                        <th class="text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td>{{ $case->case_code }}</td>
                        <td>{{ $case->service_requested_at?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d') }}</td>
                        <td>{{ $case->client?->full_name ?? '-' }}</td>
                        <td>{{ $case->client?->contact_number ?? '-' }}</td>
                        <td>{{ $case->deceased?->full_name ?? '-' }}</td>
                        <td>{{ $case->deceased?->interment_at?->format('Y-m-d H:i') ?? $case->deceased?->interment?->format('Y-m-d') ?? '-' }}</td>
                        <td>{{ $case->service_package ?? '-' }}</td>
                        <td>{{ number_format($case->total_amount, 2) }}</td>
                        <td>{{ number_format((float) $case->total_paid, 2) }}</td>
                        <td>{{ number_format((float) $case->balance_amount, 2) }}</td>
                        <td>
                            <span class="{{ in_array($case->case_status, ['DRAFT', 'ACTIVE']) ? 'status-pill-warning' : 'status-pill-success' }}">
                                {{ in_array($case->case_status, ['DRAFT', 'ACTIVE']) ? 'ONGOING' : 'COMPLETED' }}
                            </span>
                        </td>
                        <td>
                            <span class="{{
                                $case->payment_status === 'PAID'
                                    ? 'status-pill-success'
                                    : ($case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger')
                            }}">
                                {{ $case->payment_status }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}" class="action-chip open-view-modal" data-url="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}">
                                    <i class="bi bi-eye"></i>
                                    <span>View</span>
                                </a>
                                <a href="{{ route('funeral-cases.edit', $case) }}" class="action-chip action-chip-primary open-edit-modal" data-url="{{ route('funeral-cases.edit', $case) }}">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Edit</span>
                                </a>
                                @if($case->payment_status !== 'PAID')
                                    <a
                                        href="{{ route('payments.index', ['case_id' => $case->id, 'open_payment' => 1]) }}"
                                        class="action-chip action-chip-success"
                                        title="Record a payment for this case"
                                    >
                                        <i class="bi bi-cash-stack"></i>
                                        <span>Add Payment</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="py-6 text-center text-slate-500">
                            No case records found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $cases->links() }}
    </div>

    <!-- Edit modal overlay -->
    <div id="caseEditOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity duration-200">
        <div id="caseEditSheet" class="relative w-[90vw] max-w-4xl max-h-[94vh] bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-100">
            <button id="caseEditClose" type="button" class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white shadow border text-slate-200 text-slate-500 hover:text-black focus:outline-none">
                <i class="bi bi-x-lg"></i>
            </button>
            <div id="caseEditContent" class="overflow-y-auto max-h-[84vh] p-5 md:p-6 bg-slate-50">
                <div class="flex items-center justify-center py-10 text-slate-500 gap-2 text-sm">
                    <i class="bi bi-arrow-repeat animate-spin"></i>
                    <span>Loading case...</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const overlay = document.getElementById('caseEditOverlay');
            const sheet = document.getElementById('caseEditSheet');
            const content = document.getElementById('caseEditContent');
            const closeBtn = document.getElementById('caseEditClose');
            const openLinks = [...document.querySelectorAll('.open-edit-modal, .open-view-modal')];

            const showShell = () => {
                if (!overlay || !sheet) return;
                overlay.classList.remove('hidden');
                requestAnimationFrame(() => {
                    sheet.classList.remove('scale-95', 'opacity-0');
                    sheet.classList.add('scale-100', 'opacity-100');
                    overlay.classList.add('opacity-100');
                });
            };

            const hideShell = () => {
                if (!overlay || !sheet || !content) return;
                sheet.classList.add('scale-95', 'opacity-0');
                sheet.classList.remove('scale-100', 'opacity-100');
                overlay.classList.remove('opacity-100');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                    content.innerHTML = `
                        <div class="flex items-center justify-center py-10 text-slate-500 gap-2 text-sm">
                            <i class="bi bi-arrow-repeat animate-spin"></i>
                            <span>Loading case...</span>
                        </div>`;
                }, 180);
            };

            const loadContent = async (url) => {
                if (!content) return;
                content.innerHTML = `
                    <div class="flex items-center justify-center py-10 text-slate-500 gap-2 text-sm">
                        <i class="bi bi-arrow-repeat animate-spin"></i>
                        <span>Loading case...</span>
                    </div>`;
                try {
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const form = doc.querySelector('#caseEditForm');
                    const view = doc.querySelector('#caseViewContent');
                    const payload = form || view;
                    if (payload) {
                        content.innerHTML = payload.outerHTML;
                        const scripts = [...doc.querySelectorAll('script')];
                        scripts.forEach((oldScript) => {
                            const script = document.createElement('script');
                            if (oldScript.src) {
                                script.src = oldScript.src;
                            } else {
                                script.textContent = oldScript.textContent;
                            }
                            content.appendChild(script);
                        });
                    } else {
                        content.innerHTML = html;
                    }
                } catch (err) {
                    content.innerHTML = `<div class="p-6 text-sm text-rose-600">Unable to load. Please try again.</div>`;
                }
            };

            const openModal = (url) => {
                showShell();
                loadContent(url);
            };

            const closeModal = () => hideShell();

            openLinks.forEach((link) => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = link.dataset.url || link.href;
                    openModal(url);
                });
            });

            if (closeBtn) closeBtn.addEventListener('click', closeModal);

            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) closeModal();
                });
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !overlay.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
@endunless

@endsection

