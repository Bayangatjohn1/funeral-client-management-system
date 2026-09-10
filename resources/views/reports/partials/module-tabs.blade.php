@php
    $activeModule = $activeModule ?? 'analytics';
    $currentReportType = $currentReportType ?? request('report_type');
    $isOwner = auth()->user()?->isOwner();
    $branches = $branches ?? collect();
    $branchId = $branchId ?? request('branch_id');
    $range = $range ?? request('range', 'TODAY');
    $analyticsTab = request('analytics_tab', 'ba-panel-performance');
    $analyticsViews = $analyticsViews ?? [
        ['label' => 'Overview', 'icon' => 'bi-bar-chart-line', 'target' => 'ba-panel-performance'],
        ['label' => 'Payments', 'icon' => 'bi-wallet2', 'target' => 'ba-panel-payment'],
        ['label' => 'Collections', 'icon' => 'bi-cash-stack', 'target' => 'ba-panel-collection'],
        ['label' => 'Revenue Trend', 'icon' => 'bi-graph-up-arrow', 'target' => 'ba-panel-trend'],
    ];
    $reportsUrl = route('reports.index', array_filter([
        'report_type' => $currentReportType ?: 'owner_branch_analytics',
    ]));
@endphp

<section class="reports-module-header no-print" aria-label="Reports and analytics header">
    <style>
        .reports-module-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 0.75rem 1rem;
            padding: 0.25rem 0 0.75rem;
        }
        .reports-module-title-block {
            grid-column: 1;
        }
        .reports-module-title {
            margin: 0;
            color: var(--ink, #263126);
            font-family: var(--font-heading);
            font-size: clamp(1.25rem, 1.1vw + 1rem, 1.75rem);
            line-height: 1.1;
            font-weight: 800;
        }
        .reports-module-subtitle {
            margin: 0.35rem 0 0;
            color: var(--ink-muted, #6B7568);
            font-size: 0.83rem;
            line-height: 1.35;
        }
        .reports-module-tabs {
            grid-column: 1;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem;
            border: 1px solid transparent;
            border-radius: 10px;
            background: transparent;
        }
        .reports-module-primary-tabs {
            justify-self: start;
        }
        .reports-module-subtabs {
            grid-column: 1 / -1;
            justify-self: center;
            margin-top: 0.15rem;
        }
        .reports-module-tab {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0 1rem;
            border: 1px solid transparent;
            border-radius: 8px;
            color: var(--ink-muted, #5F685F);
            font-size: 0.83rem;
            font-weight: 800;
            font-family: inherit;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            appearance: none;
        }
        .reports-module-tab:hover {
            background: var(--records-hover, #C5D3BC);
            color: var(--ink, #263126);
        }
        .reports-module-tab.is-active,
        .reports-module-tab.active {
            background: #2F5233;
            border-color: #2F5233;
            color: #fff;
            box-shadow: 0 6px 14px rgba(30, 54, 33, 0.16);
        }
        .reports-module-tab i {
            font-size: 0.95rem;
        }
        .reports-module-controls {
            grid-column: 2;
            grid-row: 1 / span 2;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.55rem;
        }
        .reports-module-control {
            min-height: 38px;
            min-width: 142px;
            border: 1.25px solid var(--records-border, var(--border));
            border-radius: 10px;
            background: var(--surface-muted, #F7F9F3);
            color: var(--ink, #263126);
            padding: 0 2rem 0 2.15rem;
            font-size: 0.84rem;
            font-weight: 700;
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg width='14' height='14' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%235F685F' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-position: right .8rem center;
            background-repeat: no-repeat;
            background-size: 14px 14px;
        }
        .reports-module-control-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .reports-module-control-wrap > i {
            position: absolute;
            left: 0.82rem;
            color: var(--ink-muted, #5F685F);
            font-size: 0.9rem;
            pointer-events: none;
        }
        .reports-module-export {
            min-height: 38px;
        }
        html[data-theme='dark'] .reports-module-tab.is-active,
        html[data-theme='dark'] .reports-module-tab.active {
            background: #4a7cb5;
            border-color: #4a7cb5;
        }
        @media (max-width: 820px) {
            .reports-module-header {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .reports-module-tabs {
                overflow-x: auto;
                scrollbar-width: none;
            }
            .reports-module-subtabs {
                justify-self: start;
                width: 100%;
            }
            .reports-module-controls {
                grid-column: 1;
                grid-row: auto;
                justify-content: flex-start;
                overflow-x: auto;
                scrollbar-width: none;
            }
            .reports-module-tabs::-webkit-scrollbar {
                display: none;
            }
            .reports-module-controls::-webkit-scrollbar {
                display: none;
            }
            .reports-module-tab {
                flex: 0 0 auto;
            }
            .reports-module-control-wrap,
            .reports-module-export {
                flex: 0 0 auto;
            }
        }
    </style>

    <div class="reports-module-title-block">
        <h1 class="reports-module-title">Reports &amp; Analytics</h1>
        <p class="reports-module-subtitle">Branch management intelligence dashboard</p>
    </div>

    <nav class="reports-module-tabs reports-module-primary-tabs" aria-label="Reports and analytics module">
        @if($isOwner)
            <a
                href="{{ route('owner.analytics') }}"
                class="reports-module-tab {{ $activeModule === 'analytics' ? 'is-active' : '' }}"
                aria-current="{{ $activeModule === 'analytics' ? 'page' : 'false' }}"
            >
                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                <span>Analytics</span>
            </a>
        @endif

        <a
            href="{{ $reportsUrl }}"
            class="reports-module-tab {{ $activeModule === 'reports' ? 'is-active' : '' }}"
            aria-current="{{ $activeModule === 'reports' ? 'page' : 'false' }}"
        >
            <i class="bi bi-clipboard-data" aria-hidden="true"></i>
            <span>Reports</span>
        </a>
    </nav>

    <nav
        class="reports-module-tabs reports-module-subtabs"
        aria-label="{{ $activeModule === 'analytics' ? 'Analytics views' : 'Report types' }}"
        @if($activeModule === 'analytics') role="tablist" @endif
    >
        @if($activeModule === 'analytics')
            @foreach($analyticsViews as $view)
                <button
                    type="button"
                    class="reports-module-tab ba-tab-btn {{ $loop->first ? 'active is-active' : '' }}"
                    data-target="{{ $view['target'] }}"
                    role="tab"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                >
                    <i class="bi {{ $view['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $view['label'] }}</span>
                </button>
            @endforeach
        @else
            @foreach($reportTypes as $type => $label)
                <a
                    href="{{ route('reports.index', ['report_type' => $type]) }}"
                    class="reports-module-tab {{ $currentReportType === $type ? 'is-active' : '' }}"
                    :class="{ 'is-active': filters.report_type === @js($type) }"
                    @click.prevent="
                        filters.report_type = @js($type);
                        reportType = @js($type);
                        selectedMetric = '';
                        resetReportSpecificFilters();
                        loadPreview();
                    "
                >
                    <i class="bi {{ match ($type) {
                        'owner_branch_analytics' => 'bi-building',
                        'sales' => 'bi-briefcase-fill',
                        'master_cases' => 'bi-clipboard-data',
                        'audit_logs' => 'bi-shield-check',
                        default => 'bi-file-earmark-text',
                    } }}" aria-hidden="true"></i>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        @endif
    </nav>

    @if($activeModule === 'analytics')
        <div class="reports-module-controls" aria-label="Analytics scope controls">
            <form method="GET" action="{{ route('owner.analytics') }}" class="reports-module-control-wrap">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                @if($branchId)
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                @endif
                <input type="hidden" name="analytics_tab" value="{{ $analyticsTab }}">
                <select
                    id="analyticsHeaderRange"
                    name="range"
                    class="reports-module-control"
                    aria-label="Date range"
                    onchange="this.form.submit()"
                >
                    @foreach (['TODAY', 'THIS_MONTH', 'THIS_YEAR'] as $rangeKey)
                        <option value="{{ $rangeKey }}" @selected($range === $rangeKey)>
                            {{ ucwords(strtolower(str_replace('_', ' ', $rangeKey))) }}
                        </option>
                    @endforeach
                </select>
            </form>

            <form method="GET" action="{{ route('owner.analytics') }}" class="reports-module-control-wrap">
                <i class="bi bi-building" aria-hidden="true"></i>
                <input type="hidden" name="range" value="{{ in_array($range, ['TODAY', 'THIS_MONTH', 'THIS_YEAR'], true) ? $range : 'THIS_YEAR' }}">
                <input type="hidden" name="analytics_tab" value="{{ $analyticsTab }}">
                <select
                    id="analyticsHeaderBranch"
                    name="branch_id"
                    class="reports-module-control"
                    aria-label="Branch"
                    onchange="this.form.submit()"
                >
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    @elseif($activeModule === 'reports')
        <div class="reports-module-controls" aria-label="Report scope controls">
            <label class="reports-module-control-wrap" for="reportsHeaderDatePreset">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <select
                    id="reportsHeaderDatePreset"
                    class="reports-module-control"
                    aria-label="Date range"
                    x-model="datePreset"
                    @change="selectReportDatePreset(datePreset); loadPreview();"
                >
                    <option value="">Any Time</option>
                    <option value="TODAY">Today</option>
                    <option value="THIS_MONTH">This Month</option>
                    <option value="THIS_YEAR">This Year</option>
                    <option value="CUSTOM">Custom Range</option>
                </select>
            </label>

            <label class="reports-module-control-wrap" for="reportsHeaderBranch">
                <i class="bi bi-building" aria-hidden="true"></i>
                <select
                    id="reportsHeaderBranch"
                    class="reports-module-control"
                    aria-label="Branch"
                    x-model="filters.branch_id"
                    :disabled="isBranchAdmin"
                    @change="loadPreview()"
                >
                    <template x-if="!isBranchAdmin">
                        <option value="">All Branches</option>
                    </template>
                    <template x-for="branch in branches" :key="branch.id">
                        <option :value="branch.id" x-text="`${branch.branch_code} - ${branch.branch_name}`"></option>
                    </template>
                </select>
            </label>

            <details
                x-ref="headerExportMenu"
                class="reports-export-menu reports-module-export"
                :aria-disabled="loading || rows.length === 0 ? 'true' : 'false'"
                @toggle="if (exportDisabled()) closeExportMenu()"
            >
                <summary class="reports-btn reports-btn-secondary" @click="if (exportDisabled()) { $event.preventDefault(); closeExportMenu(); }">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Export</span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </summary>
                <div class="reports-export-options">
                    <button type="button" @click="openPrint">
                        <i class="bi bi-filetype-pdf" aria-hidden="true"></i>
                        <span>Print / Save as PDF</span>
                    </button>
                    <button type="button" @click="openCsv">
                        <i class="bi bi-filetype-csv" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </button>
                </div>
            </details>
        </div>
    @endif
</section>
