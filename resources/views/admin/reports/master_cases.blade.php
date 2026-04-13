@extends('layouts.panel')

@section('page_title', 'Master Case Records')
@section('page_desc', 'Monitor branch activity, payment health, and verification status from one admin worklist.')

@section('content')
<div class="admin-table-page">
    @php
        $resolvedDatePreset = $datePreset ?? 'ANY';
        $isCustomDate = $resolvedDatePreset === 'CUSTOM';
        $sort = $sort ?? 'newest';
        $secondaryFiltersActive = filled($verificationStatus ?? null)
            || filled($paymentStatus ?? null)
            || $sort !== 'newest'
            || $resolvedDatePreset !== 'ANY'
            || filled($intermentFrom ?? null)
            || filled($intermentTo ?? null);
        $activeFilterCount = collect([
            filled($q ?? null),
            filled($branchId ?? null),
            filled($caseStatus ?? null),
            filled($verificationStatus ?? null),
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
            color: #64748b;
        }

        .admin-master-active-summary strong {
            color: #1f2937;
            font-weight: 700;
        }

        .admin-master-active-summary a {
            color: #334155;
            font-weight: 600;
        }

        .admin-master-active-summary a:hover {
            color: #0f172a;
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
        <div class="table-system-head">
            <div class="admin-table-head-row">
                <div>
                    <h2 class="table-system-title">All Recorded Cases</h2>
                    <p class="admin-table-head-copy">Monitor branch activity, payment health, and verification status from one admin worklist.</p>
                </div>
            </div>
        </div>

        <div class="table-system-toolbar admin-table-toolbar">
            <form method="GET" action="{{ route('admin.cases.index') }}" class="admin-master-toolbar" data-table-toolbar data-search-debounce="400">
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
                        <select name="branch_id" class="table-toolbar-select" data-table-auto-submit>
                            <option value="">All Branches</option>
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
                            <select name="verification_status" class="table-toolbar-select" data-table-auto-submit>
                                <option value="">All Verification</option>
                                <option value="PENDING" {{ ($verificationStatus ?? null) === 'PENDING' ? 'selected' : '' }}>Pending Review</option>
                                <option value="VERIFIED" {{ ($verificationStatus ?? null) === 'VERIFIED' ? 'selected' : '' }}>Verified</option>
                                <option value="DISPUTED" {{ ($verificationStatus ?? null) === 'DISPUTED' ? 'selected' : '' }}>Disputed</option>
                            </select>
                        </div>

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
                </div>
            </form>
        </div>

        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table admin-master-table">
                    <thead>
                        <tr>
                            <th class="text-left">Case ID</th>
                            <th class="text-left">Service Date</th>
                            <th class="text-left">Branch</th>
                            <th class="text-left">Client</th>
                            <th class="text-left">Deceased</th>
                            <th class="text-left">Verification</th>
                            <th class="text-left">Case Status</th>
                            <th class="text-left">Payment Status</th>
                            <th class="table-col-number">Balance</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cases as $case)
                        @php
                            $verificationStatusValue = strtoupper((string) ($case->verification_status ?? 'VERIFIED'));
                            $isOtherBranch = $case->entry_source === 'OTHER_BRANCH';

                            if (!$isOtherBranch) {
                                $verificationLabel = 'Auto-Verified';
                                $verificationClass = 'status-badge status-badge-info';
                            } elseif ($verificationStatusValue === 'PENDING') {
                                $verificationLabel = 'Pending Review';
                                $verificationClass = 'status-badge status-badge-warning';
                            } elseif ($verificationStatusValue === 'DISPUTED') {
                                $verificationLabel = 'Manual Review';
                                $verificationClass = 'status-badge status-badge-danger';
                            } else {
                                $verificationLabel = 'Manual Review';
                                $verificationClass = 'status-badge status-badge-success';
                            }

                            $caseStatusClass = match ($case->case_status) {
                                'COMPLETED' => 'status-badge status-badge-success',
                                'ACTIVE' => 'status-badge status-badge-warning',
                                'DRAFT' => 'status-badge status-badge-neutral',
                                default => 'status-badge status-badge-neutral',
                            };

                            $paymentStatusClass = match ($case->payment_status) {
                                'PAID' => 'status-badge status-badge-success',
                                'PARTIAL' => 'status-badge status-badge-warning',
                                'UNPAID' => 'status-badge status-badge-danger',
                                default => 'status-badge status-badge-neutral',
                            };
                        @endphp

                        <tr>
                            <td class="table-primary whitespace-nowrap">{{ $case->case_code }}</td>
                            <td class="whitespace-nowrap">{{ $case->service_requested_at?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="table-primary whitespace-nowrap">{{ $case->branch?->branch_code ?? '-' }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->branch?->branch_name ?? '-', 18) }}</div>
                            </td>
                            <td class="table-primary">{{ \Illuminate\Support\Str::limit($case->client?->full_name ?? '-', 24) }}</td>
                            <td class="table-primary">{{ \Illuminate\Support\Str::limit($case->deceased?->full_name ?? '-', 24) }}</td>
                            <td>
                                <span class="{{ $verificationClass }}">{{ $verificationLabel }}</span>
                            </td>
                            <td>
                                <span class="{{ $caseStatusClass }}">{{ \Illuminate\Support\Str::headline(strtolower((string) $case->case_status)) }}</span>
                            </td>
                            <td>
                                <span class="{{ $paymentStatusClass }}">{{ \Illuminate\Support\Str::headline(strtolower((string) $case->payment_status)) }}</span>
                            </td>
                            <td class="table-col-number whitespace-nowrap">{{ number_format((float) $case->balance_amount, 2) }}</td>
                            <td class="table-col-actions">
                                <div class="row-action-menu" data-row-menu>
                                    <button
                                        type="button"
                                        class="row-action-trigger"
                                        data-row-menu-trigger
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

                                        @if(!$isOtherBranch)
                                            <a
                                                class="row-action-item"
                                                data-row-menu-item
                                                href="{{ route('funeral-cases.edit', $case) }}"
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit case</span>
                                            </a>

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
                                                <span>Edit locked (Other Branch)</span>
                                            </span>
                                            <span class="row-action-item opacity-60 cursor-default">
                                                <i class="bi bi-lock"></i>
                                                <span>Delete locked (Other Branch)</span>
                                            </span>
                                        @endif

                                        @if($isOtherBranch)
                                            <button
                                                type="button"
                                                class="row-action-item"
                                                data-row-menu-item
                                                data-verify-panel-toggle
                                                data-verify-panel-target="verify-panel-{{ $case->id }}"
                                            >
                                                <i class="bi bi-shield-check"></i>
                                                <span>Review verification</span>
                                            </button>
                                        @else
                                            <span class="row-action-item opacity-60 cursor-default">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>Auto-verified record</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>

                        @if($isOtherBranch)
                            <tr id="verify-panel-{{ $case->id }}" class="hidden">
                                <td colspan="10" class="!py-0">
                                    <div class="admin-master-inline-review">
                                        <form method="POST" action="{{ route('admin.cases.verification', $case) }}" class="admin-master-inline-review-form">
                                            @csrf
                                            @method('PATCH')
                                            <select name="verification_status" class="form-select text-xs">
                                                <option value="VERIFIED">Mark Verified</option>
                                                <option value="DISPUTED">Mark Disputed</option>
                                            </select>
                                            <input type="text" name="verification_note" class="form-input text-xs" placeholder="Verification note (required for disputed)">
                                            <button class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white" type="submit">
                                                Save Review
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="table-system-empty">No case records found.</td>
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

<script>
    (() => {
        const toggles = document.querySelectorAll('[data-verify-panel-toggle]');
        if (!toggles.length) return;

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.getAttribute('data-verify-panel-target');
                if (!targetId) return;

                const row = document.getElementById(targetId);
                if (!row) return;

                row.classList.toggle('hidden');
            });
        });
    })();

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
</script>
@endsection
