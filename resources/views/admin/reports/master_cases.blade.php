@extends('layouts.panel')

@section('page_title', 'Master Case Records')
@section('page_desc', 'Monitor branch activity, case status, and payment health from one admin worklist.')

@section('content')
<div class="admin-table-page">
    @php
        $isBranchAdmin = auth()->user()?->isBranchAdmin() ?? false;
        $resolvedDatePreset = $datePreset ?? 'ANY';
        $isCustomDate = $resolvedDatePreset === 'CUSTOM';
        $sort = $sort ?? 'newest';
        $secondaryFiltersActive = filled($paymentStatus ?? null)
            || $sort !== 'newest'
            || $resolvedDatePreset !== 'ANY'
            || filled($intermentFrom ?? null)
            || filled($intermentTo ?? null);
        $activeFilterCount = collect([
            filled($q ?? null),
            filled($branchId ?? null),
            filled($caseStatus ?? null),
            filled($paymentStatus ?? null),
            $sort !== 'newest',
            $resolvedDatePreset !== 'ANY',
            filled($intermentFrom ?? null),
            filled($intermentTo ?? null),
        ])->filter()->count();
        $presetLabel = match ($resolvedDatePreset) {
            'TODAY' => 'Today',
            'LAST_7_DAYS' => 'Last 7 Days',
            'LAST_30_DAYS' => 'Last 30 Days',
            'THIS_MONTH' => 'This Month',
            'CUSTOM' => 'Custom Range',
            default => 'Any Time',
        };
        $adminMasterChips = collect();
        if ($isBranchAdmin && $branchId) {
            $adminBranch = $branches->firstWhere('id', (int) $branchId) ?? $branches->first();
            $adminMasterChips->push([
                'icon' => 'bi-lock-fill',
                'label' => 'Branch: ' . ($adminBranch ? trim(($adminBranch->branch_code ?? '') . ' - ' . ($adminBranch->branch_name ?? '')) : 'Assigned Branch'),
                'locked' => true,
            ]);
        } elseif (filled($branchId)) {
            $adminBranch = $branches->firstWhere('id', (int) $branchId);
            $adminMasterChips->push([
                'icon' => 'bi-building',
                'label' => 'Branch: ' . ($adminBranch ? trim(($adminBranch->branch_code ?? '') . ' - ' . ($adminBranch->branch_name ?? '')) : 'Selected Branch'),
            ]);
        }
        if (filled($q ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-search', 'label' => 'Search: ' . $q]);
        }
        if (filled($caseStatus ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-clipboard-check', 'label' => 'Case: ' . \Illuminate\Support\Str::headline(strtolower($caseStatus))]);
        }
        if (filled($paymentStatus ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-wallet2', 'label' => 'Payment: ' . \Illuminate\Support\Str::headline(strtolower($paymentStatus))]);
        }
        if (filled(request('service_type'))) {
            $adminMasterChips->push(['icon' => 'bi-tag', 'label' => 'Service: ' . request('service_type')]);
        }
        if (filled(request('package_id'))) {
            $selectedPackage = ($packages ?? collect())->firstWhere('id', (int) request('package_id'));
            $adminMasterChips->push(['icon' => 'bi-box', 'label' => 'Package: ' . ($selectedPackage?->name ?? 'Selected Package')]);
        }
        if ($resolvedDatePreset !== 'ANY') {
            $adminMasterChips->push(['icon' => 'bi-calendar3', 'label' => 'Date: ' . $presetLabel]);
        }
        if (filled($intermentFrom ?? null) || filled($intermentTo ?? null)) {
            $adminMasterChips->push(['icon' => 'bi-calendar-event', 'label' => 'Interment: ' . (($intermentFrom ?? null) ?: 'Start') . ' - ' . (($intermentTo ?? null) ?: 'Today')]);
        }
    @endphp
    <style>
        .admin-master-toolbar-row-primary {
            grid-template-columns: minmax(280px, 2.2fr) repeat(2, minmax(180px, 1fr)) auto;
        }

        .admin-master-primary-actions {
            justify-content: flex-end;
            align-items: flex-end;
            gap: 8px;
        }

        .admin-master-more-btn {
            min-height: 40px;
            white-space: nowrap;
            min-width: 132px;
        }

        .admin-master-more-btn i {
            font-size: 13px;
        }

        .admin-master-more-filters {
            margin-top: 8px;
        }

        .admin-master-more-filters .admin-master-toolbar-row-bottom {
            grid-template-columns: repeat(4, minmax(150px, 1fr)) repeat(2, minmax(150px, 180px));
        }

        .admin-master-active-summary {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #5F685F;
        }

        .admin-master-active-summary strong {
            color: #333333;
            font-weight: 700;
        }

        .admin-master-active-summary a {
            color: #333333;
            font-weight: 600;
        }

        .admin-master-active-summary a:hover {
            color: #3E4A3D;
            text-decoration: underline;
        }

        @media (max-width: 1200px) {
            .admin-master-toolbar-row-primary {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }

            .admin-master-primary-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 680px) {
            .admin-master-toolbar-row-primary {
                grid-template-columns: 1fr;
            }

            .admin-master-primary-actions {
                width: 100%;
                flex-wrap: wrap;
            }
        }
    </style>

    @if($errors->any())
        <div class="flash-error">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="table-system-card admin-table-card">
        <div class="table-system-toolbar admin-table-toolbar">
            @include('partials.case_filter_toolbar', [
                'action' => route('admin.cases.index'),
                'resetUrl' => route('admin.cases.index'),
                'branchMode' => $isBranchAdmin ? 'locked' : 'all',
                'assignedBranch' => $isBranchAdmin ? $branches->first() : null,
                'branchId' => $branchId,
                'branches' => $branches,
                'datePreset' => $datePreset ?? '',
                'dateFrom' => $dateFrom ?? null,
                'dateTo' => $dateTo ?? null,
                'intermentFrom' => $intermentFrom ?? null,
                'intermentTo' => $intermentTo ?? null,
                'serviceTypes' => $serviceTypes ?? collect(),
                'packages' => $packages ?? collect(),
                'encoders' => $encoders ?? collect(),
                'showVerificationStatus' => false,
                'showPackage' => true,
                'showEncodedBy' => true,
                'showInlineChips' => false,
                'hiddenInputs' => array_filter(['sort' => request('sort')], fn ($value) => filled($value)),
            ])

            <form method="GET" action="{{ route('admin.cases.index') }}" class="admin-master-toolbar hidden" data-table-toolbar data-search-debounce="400">
                <div class="admin-master-toolbar-row admin-master-toolbar-row-top admin-master-toolbar-row-primary">
                    <div class="table-toolbar-field">
                        <input
                            name="q"
                            value="{{ $q }}"
                            class="table-toolbar-search"
                            placeholder="Search case, client, or deceased..."
                            data-table-search
                        >
                    </div>

                    <div class="table-toolbar-field">
                        @if($isBranchAdmin && $branchId)
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        @endif
                        <select name="branch_id" class="table-toolbar-select" data-table-auto-submit @if($isBranchAdmin) disabled @endif>
                            @unless($isBranchAdmin)
                                <option value="">All Branches</option>
                            @endunless
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_code }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-toolbar-field">
                        <select name="case_status" class="table-toolbar-select" data-table-auto-submit>
                            <option value="">All Case Status</option>
                            <option value="DRAFT" {{ $caseStatus === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                            <option value="ACTIVE" {{ $caseStatus === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                            <option value="COMPLETED" {{ $caseStatus === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>

                    <div class="table-toolbar-reset-wrap admin-master-toolbar-actions admin-master-primary-actions">
                        <button
                            type="button"
                            class="btn btn-secondary admin-master-more-btn"
                            data-more-filters-toggle
                            data-more-filters-label
                            aria-controls="admin-master-more-filters"
                            aria-expanded="{{ $secondaryFiltersActive ? 'true' : 'false' }}"
                        >
                            <i class="bi bi-sliders"></i>
                            <span>{{ $secondaryFiltersActive ? 'Hide Filters' : 'More Filters' }}</span>
                        </button>
                        <a href="{{ route('admin.cases.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>

                <div id="admin-master-more-filters" class="admin-master-more-filters @if(!$secondaryFiltersActive) hidden @endif" data-more-filters-panel>
                    <div class="admin-master-toolbar-row admin-master-toolbar-row-bottom">
                        <div class="table-toolbar-field">
                            <select name="payment_status" class="table-toolbar-select" data-table-auto-submit>
                                <option value="">All Payment Status</option>
                                <option value="PAID" {{ $paymentStatus === 'PAID' ? 'selected' : '' }}>Paid</option>
                                <option value="PARTIAL" {{ $paymentStatus === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                                <option value="UNPAID" {{ $paymentStatus === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field">
                            <select name="sort" class="table-toolbar-select" data-table-sort>
                                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field">
                            <select name="date_preset" class="table-toolbar-select">
                                <option value="ANY" {{ $resolvedDatePreset === 'ANY' ? 'selected' : '' }}>Any Time</option>
                                <option value="TODAY" {{ $resolvedDatePreset === 'TODAY' ? 'selected' : '' }}>Today</option>
                                <option value="LAST_7_DAYS" {{ $resolvedDatePreset === 'LAST_7_DAYS' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="LAST_30_DAYS" {{ $resolvedDatePreset === 'LAST_30_DAYS' ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="THIS_MONTH" {{ $resolvedDatePreset === 'THIS_MONTH' ? 'selected' : '' }}>This Month</option>
                                <option value="CUSTOM" {{ $resolvedDatePreset === 'CUSTOM' ? 'selected' : '' }}>Custom Range</option>
                            </select>
                        </div>

                        <div class="table-toolbar-field" data-custom-date-field @if(!$isCustomDate) hidden @endif>
                            <input
                                type="date"
                                name="interment_from"
                                value="{{ $intermentFrom ?? '' }}"
                                class="table-toolbar-select"
                                data-custom-date-input
                                title="Date from"
                                @if(!$isCustomDate) disabled @endif
                            >
                        </div>

                        <div class="table-toolbar-field" data-custom-date-field @if(!$isCustomDate) hidden @endif>
                            <input
                                type="date"
                                name="interment_to"
                                value="{{ $intermentTo ?? '' }}"
                                class="table-toolbar-select"
                                data-custom-date-input
                                title="Date to"
                                @if(!$isCustomDate) disabled @endif
                            >
                        </div>
                    </div>
                </div>

                <div class="admin-master-active-summary">
                    <span>Active filters: <strong>{{ $activeFilterCount }}</strong></span>
                    @if($activeFilterCount > 0)
                        <a href="{{ route('admin.cases.index') }}">Clear all filters</a>
                    @endif
                    <span>Date Range: <strong>{{ $presetLabel }}</strong></span>
                    @if($isBranchAdmin)
                        <span><strong>Assigned Branch Only</strong></span>
                    @endif
                </div>
            </form>
        </div>

        <div class="case-records-master-chip-row">
            <div class="case-compact-inline-chips case-records-quick-chips" aria-label="Applied branch and filters">
                @forelse($adminMasterChips as $chip)
                    <span class="case-compact-chip {{ !empty($chip['locked']) ? 'case-compact-chip-locked' : '' }}">
                        <i class="bi {{ $chip['icon'] }}"></i>{{ $chip['label'] }}
                    </span>
                @empty
                    <span class="case-compact-chip">
                        <i class="bi bi-funnel"></i>All records
                    </span>
                @endforelse
            </div>
        </div>

        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table admin-master-table records-worklist-table">
                    <colgroup>
                        <col class="records-col-case">
                        <col class="records-col-branch">
                        <col class="records-col-family">
                        <col class="records-col-service">
                        <col class="records-col-schedule">
                        <col class="records-col-financials">
                        <col class="records-col-case-status">
                        <col class="records-col-payment-status">
                        <col class="records-col-actions">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-left">Case</th>
                            <th class="text-left">Branch</th>
                            <th class="text-left">Family / Client</th>
                            <th class="text-left">Service</th>
                            <th class="text-left">Interment</th>
                            <th class="table-col-number">Financials</th>
                            <th class="table-status-col">Case Status</th>
                            <th class="table-status-col table-payment-status-col">Payment Status</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cases as $case)
                        @php
                            $isOtherBranch = $case->entry_source === 'OTHER_BRANCH';
                            $needsAttention = $case->payment_status === 'UNPAID'
                                || (float) $case->balance_amount > 0;
                            $intermentDate = $case->interment_at
                                ?? $case->deceased?->interment_at
                                ?? $case->deceased?->interment;
                        @endphp

                        <tr
                            class="{{ $needsAttention ? 'row-needs-attention' : '' }}"
                            data-clickable-row
                            data-row-href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"
                            tabindex="0"
                            role="link"
                            aria-label="Open full case details for {{ $case->case_code }}"
                        >
                            <td>
                                <div class="table-primary whitespace-nowrap records-case-code">{{ $case->case_code }}</div>
                                <div class="table-secondary">Encoded {{ $case->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <div class="table-primary whitespace-nowrap">{{ $case->branch?->branch_code ?? '-' }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->branch?->branch_name ?? '-', 24) }}</div>
                            </td>
                            <td>
                                <div class="table-primary">{{ \Illuminate\Support\Str::limit($case->deceased?->full_name ?? '-', 30) }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->client?->full_name ?? '-', 28) }}</div>
                            </td>
                            <td>
                                <div class="table-primary">{{ $case->service_type ?? '-' }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->package?->name ?? $case->service_package ?? '-', 30) }}</div>
                            </td>
                            <td>
                                <div class="table-primary whitespace-nowrap">{{ $intermentDate ? $intermentDate->format('M d, Y') : '-' }}</div>
                                <div class="table-secondary">{{ $intermentDate && $intermentDate->format('H:i') !== '00:00' ? $intermentDate->format('h:i A') : 'Scheduled date' }}</div>
                            </td>
                            <td class="table-col-number">
                                <div class="table-primary table-financial-total whitespace-nowrap">{{ number_format((float) $case->total_amount, 2) }}</div>
                                <div class="table-secondary table-financial-breakdown whitespace-nowrap">Paid {{ number_format((float) $case->total_paid, 2) }} &middot; Bal {{ number_format((float) $case->balance_amount, 2) }}</div>
                            </td>
                            <td class="table-status-cell">
                                <x-status-badge :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" />
                            </td>
                            <td class="table-status-cell table-payment-status-cell">
                                <x-status-badge :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" class="table-payment-status-badge" />
                            </td>
                            <td class="table-col-actions">
                                <div class="table-row-actions">
                                    <div class="row-action-menu" data-row-menu>
                                        <button
                                            type="button"
                                            class="row-action-trigger"
                                            data-row-menu-trigger
                                            data-no-row-click
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Open row actions"
                                        >
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>

                                        <div class="row-action-dropdown" role="menu">
                                        <a
                                            class="row-action-item"
                                            data-row-menu-item
                                            href="{{ route('admin.cases.index', array_merge(request()->query(), ['q' => $case->case_code])) }}"
                                        >
                                            <i class="bi bi-search"></i>
                                            <span>Focus this case</span>
                                        </a>

                                        <a
                                            href="{{ route('funeral-cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}"
                                            class="row-action-item"
                                            data-row-menu-item
                                        >
                                            <i class="bi bi-eye"></i>
                                            <span>Open full details</span>
                                        </a>

                                        @if(!$isOtherBranch)
                                            <form method="POST" action="{{ route('funeral-cases.destroy', $case) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="row-action-item text-red-600"
                                                    data-row-menu-item
                                                    onclick="return confirm('Delete this case? Cases with payment records cannot be deleted.')"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                    <span>Delete case</span>
                                                </button>
                                            </form>
                                        @else
                                            <span class="row-action-item opacity-60 cursor-default">
                                                <i class="bi bi-lock"></i>
                                                <span>Delete locked (Other Branch)</span>
                                            </span>
                                        @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-system-empty">No case records found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-system-pagination">
                {{ $cases->links() }}
            </div>
        </div>
    </section>
</div>

{{-- Case view modal --}}
<div id="adminCaseViewOverlay" style="display:none; position:fixed; inset:0; z-index:400; background:rgba(0,0,0,0.55); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); align-items:center; justify-content:center;">
    <div id="adminCaseViewSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border"
         style="background:var(--card);border-color:var(--border);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-panel);flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:var(--ink);">Case Details</span>
            <button id="adminCaseViewClose" type="button" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--ink-muted);cursor:pointer;" aria-label="Close">
                <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
            </button>
        </div>
        <div id="adminCaseViewContent" class="overflow-y-auto" style="max-height:calc(92vh - 54px);padding:16px;">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 0;gap:10px;">
                <div style="width:28px;height:28px;border-radius:50%;border:2px solid var(--border);border-top-color:var(--brand);animation:spin 1s linear infinite;"></div>
                <span style="font-size:13px;color:var(--ink-muted);">Loading…</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const toggle = document.querySelector('[data-more-filters-toggle]');
        const panel = document.querySelector('[data-more-filters-panel]');
        const labelWrap = document.querySelector('[data-more-filters-label] span');
        if (!toggle || !panel || !labelWrap) return;

        const setOpen = (open) => {
            panel.classList.toggle('hidden', !open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            labelWrap.textContent = open ? 'Hide Filters' : 'More Filters';
        };

        setOpen(toggle.getAttribute('aria-expanded') === 'true');
        toggle.addEventListener('click', () => {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });
    })();

    (() => {
        const form = document.querySelector('form.admin-master-toolbar');
        const preset = document.querySelector('select[name="date_preset"]');
        const from = document.querySelector('input[name="interment_from"]');
        const to = document.querySelector('input[name="interment_to"]');
        const customInputs = document.querySelectorAll('[data-custom-date-input]');
        const customFields = document.querySelectorAll('[data-custom-date-field]');
        if (!form || !preset || !from || !to || !customFields.length) return;
        const customDateDebounce = 800;
        let customDateTimer = null;

        const syncCustomDateVisibility = () => {
            const isCustom = preset.value === 'CUSTOM';
            customFields.forEach((field) => {
                field.hidden = !isCustom;
            });
            from.disabled = !isCustom;
            to.disabled = !isCustom;
        };

        const isCompleteDate = (value) => value === '' || /^\d{4}-\d{2}-\d{2}$/.test(value);

        const canSubmitCustomRange = () => {
            if (preset.value !== 'CUSTOM') {
                return false;
            }

            const fromValue = (from.value || '').trim();
            const toValue = (to.value || '').trim();

            if (!isCompleteDate(fromValue) || !isCompleteDate(toValue)) {
                return false;
            }

            if (fromValue === '' && toValue === '') {
                return false;
            }

            return true;
        };

        const submitForm = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }
            form.submit();
        };

        const forceCustom = (event) => {
            if (event?.target && !isCompleteDate(event.target.value || '')) {
                return;
            }

            if (from.value || to.value) {
                preset.value = 'CUSTOM';
                syncCustomDateVisibility();
            }
        };

        const queueCustomDateSubmit = () => {
            if (customDateTimer) {
                clearTimeout(customDateTimer);
            }

            customDateTimer = setTimeout(() => {
                if (canSubmitCustomRange()) {
                    submitForm();
                }
            }, customDateDebounce);
        };

        preset.addEventListener('change', () => {
            syncCustomDateVisibility();
            if (preset.value !== 'CUSTOM') {
                submitForm();
            }
        });

        from.addEventListener('change', forceCustom);
        to.addEventListener('change', forceCustom);
        customInputs.forEach((input) => {
            input.addEventListener('change', queueCustomDateSubmit);
        });

        form.addEventListener('submit', () => {
            if (customDateTimer) {
                clearTimeout(customDateTimer);
            }
        });

        syncCustomDateVisibility();
    })();

    (() => {
        const overlay  = document.getElementById('adminCaseViewOverlay');
        const sheet    = document.getElementById('adminCaseViewSheet');
        const content  = document.getElementById('adminCaseViewContent');
        const closeBtn = document.getElementById('adminCaseViewClose');
        if (!overlay || !sheet || !content || !closeBtn) return;

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
                    content.innerHTML = `<div style="padding:20px;font-size:13px;color:#9E4B3F;">Unable to load case details.</div>`;
                }
            } catch {
                content.innerHTML = `<div style="padding:20px;font-size:13px;color:#9E4B3F;">Network error. Please try again.</div>`;
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
