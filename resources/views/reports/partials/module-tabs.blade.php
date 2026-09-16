@php
    $activeModule = $activeModule ?? 'analytics';
    $currentReportType = $currentReportType ?? request('report_type');
    $isOwner = auth()->user()?->isOwner();
    $canUseAnalytics = auth()->user()?->isOwner() || auth()->user()?->isAdmin();
    $analyticsRouteName = $analyticsRouteName ?? ($isOwner ? 'owner.analytics' : 'reports.analytics');
    $analyticsBranchScope = $analyticsBranchScope ?? null;
    $canSelectAllBranches = ! $analyticsBranchScope || ($analyticsBranchScope['can_select_all'] ?? true);
    $branches = $branches ?? collect();
    $users = $users ?? collect();
    $auditOptions = $auditOptions ?? [];
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
        .reports-module-title-block--hidden {
            display: none;
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
            gap: 0.5rem;
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
            background: transparent;
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
        .reports-module-subtabs .reports-module-tab {
            position: relative;
            min-height: 42px;
            min-width: 150px;
            padding: 0 1.05rem;
            border-color: #C1CBB8;
            background: rgba(247, 249, 243, 0.72);
            color: var(--ink-muted, #5F685F);
            box-shadow: none;
        }
        .reports-module-subtabs .reports-module-tab:hover {
            border-color: #AAB9A2;
            background: #E7EEDF;
            color: var(--ink, #263126);
        }
        .reports-module-tab.is-active,
        .reports-module-tab.active {
            background: #2F5233;
            border-color: #2F5233;
            color: #fff;
            box-shadow: none;
        }
        .reports-module-subtabs .reports-module-tab.is-active,
        .reports-module-subtabs .reports-module-tab.active {
            background: #C7D5BE;
            border-color: #2F5233;
            color: #263126;
            box-shadow: none;
        }
        .reports-module-subtabs .reports-module-tab::after {
            content: '';
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 4px;
            border-radius: 999px 999px 0 0;
            background: transparent;
        }
        .reports-module-subtabs .reports-module-tab.is-active::after,
        .reports-module-subtabs .reports-module-tab.active::after {
            background: #3E4A3D;
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
            flex-wrap: wrap;
            gap: 0.55rem;
        }
        .reports-module-control {
            min-height: 38px;
            min-width: 142px;
            border: 1.25px solid #b9cbb1;
            border-radius: 10px;
            background-color: #f7f8f1;
            color: var(--ink, #263126);
            padding: 0 2rem 0 2.15rem;
            font-size: 0.84rem;
            font-weight: 700;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none;
            transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease;
        }
        .reports-module-control:hover {
            border-color: #9faf97;
            background-color: #e5eedc;
        }
        .reports-module-control:focus,
        .reports-module-control:focus-visible {
            border-color: #2f5131;
            background-color: #f7f8f1;
            box-shadow: none;
        }
        .reports-module-control option {
            background-color: #f7f8f1 !important;
            color: #1f2d20 !important;
            font-weight: 650;
        }
        .reports-module-control option:checked {
            background-color: #2f5131 !important;
            background: #2f5131 !important;
            color: #f7f8f1 !important;
            -webkit-text-fill-color: #f7f8f1 !important;
            font-weight: 800;
        }
        .reports-module-control.is-compact {
            min-width: 168px;
            max-width: 220px;
        }
        .reports-module-control.is-branch {
            min-width: 240px;
            max-width: 360px;
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
        .reports-module-control-wrap::after {
            content: "";
            position: absolute;
            right: .85rem;
            top: 50%;
            width: .44rem;
            height: .44rem;
            border-right: 2px solid #566653;
            border-bottom: 2px solid #566653;
            pointer-events: none;
            transform: translateY(-62%) rotate(45deg);
            transition: transform .16s ease, border-color .16s ease;
        }
        .reports-module-control-wrap.is-open::after {
            border-color: #293229;
            transform: translateY(-38%) rotate(225deg);
        }
        .reports-module-export {
            min-height: 38px;
        }
        [data-reports-module-shell] {
            position: relative;
        }
        html.reports-module-soft-loading [data-reports-module-shell] {
            cursor: progress;
            opacity: 0.76;
            pointer-events: none;
            transition: opacity 0.16s ease;
        }
        html.reports-module-soft-loading body::after {
            content: 'Loading module...';
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 80;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            padding: 0 12px;
            border: 1px solid var(--records-border, var(--border));
            border-radius: 999px;
            background: var(--records-card-alt, #fff);
            color: var(--ink, #263126);
            font-size: 12px;
            font-weight: 800;
            box-shadow: none;
        }
        [data-reports-module-shell].is-module-entering {
            opacity: 0.01;
            pointer-events: none;
        }
        [data-reports-module-shell].is-module-ready {
            animation: reportsModuleReady 0.18s ease-out both;
        }
        @keyframes reportsModuleReady {
            from { opacity: 0.01; }
            to { opacity: 1; }
        }
        html[data-theme='dark'] .reports-module-tab.is-active,
        html[data-theme='dark'] .reports-module-tab.active {
            background: #4a7cb5;
            border-color: #4a7cb5;
        }
        html[data-theme='dark'].reports-module-soft-loading body::after {
            background: #1f344d;
            border-color: #2e4560;
            color: #cfe0f5;
        }
        @media (max-width: 820px) {
            .reports-module-header {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .reports-module-tabs {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(min(9rem, 100%), 1fr));
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow: visible;
                scrollbar-width: none;
            }
            .reports-module-subtabs {
                justify-self: start;
                width: 100%;
            }
            .reports-module-controls {
                grid-column: 1;
                grid-row: auto;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(min(12rem, 100%), 1fr));
                width: 100%;
                min-width: 0;
                justify-content: stretch;
                overflow: visible;
                scrollbar-width: none;
            }
            .reports-module-tabs::-webkit-scrollbar {
                display: none;
            }
            .reports-module-controls::-webkit-scrollbar {
                display: none;
            }
            .reports-module-tab {
                flex: 1 1 auto;
                width: 100%;
                min-width: 0 !important;
                max-width: 100%;
                padding-inline: .65rem;
                overflow-wrap: anywhere;
                white-space: normal;
            }
            .reports-module-control-wrap,
            .reports-module-export {
                flex: 1 1 auto;
                width: 100%;
                min-width: 0;
                max-width: 100%;
            }
            .reports-module-control,
            .reports-module-control.is-compact,
            .reports-module-control.is-branch {
                width: 100%;
                min-width: 0;
                max-width: none;
            }
            .reports-module-export .reports-export-options {
                left: 0;
                right: auto;
                width: min(12rem, calc(100vw - 1.5rem));
                min-width: 0;
            }
        }
    </style>

    <div class="reports-module-title-block reports-module-title-block--hidden" aria-hidden="true">
        <h1 class="reports-module-title">Reports &amp; Analytics</h1>
        <p class="reports-module-subtitle">Branch management intelligence dashboard</p>
    </div>

    <nav class="reports-module-tabs reports-module-primary-tabs" aria-label="Reports and analytics module">
        @if($canUseAnalytics)
            <button
                type="button"
                data-reports-module-link
                data-href="{{ route($analyticsRouteName) }}"
                onclick="return window.SabanganReportsModuleNavigation ? window.SabanganReportsModuleNavigation.handleLinkClick(this, event) : false;"
                class="reports-module-tab {{ $activeModule === 'analytics' ? 'is-active' : '' }}"
                aria-current="{{ $activeModule === 'analytics' ? 'page' : 'false' }}"
            >
                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                <span>Analytics</span>
            </button>
        @endif

        <button
            type="button"
            data-reports-module-link
            data-href="{{ $reportsUrl }}"
            onclick="return window.SabanganReportsModuleNavigation ? window.SabanganReportsModuleNavigation.handleLinkClick(this, event) : false;"
            class="reports-module-tab {{ $activeModule === 'reports' ? 'is-active' : '' }}"
            aria-current="{{ $activeModule === 'reports' ? 'page' : 'false' }}"
        >
            <i class="bi bi-clipboard-data" aria-hidden="true"></i>
            <span>Reports</span>
        </button>
    </nav>

    <nav
        class="reports-module-tabs reports-module-subtabs"
        aria-label="{{ $activeModule === 'analytics' ? 'Analytics views' : 'Report types' }}"
        @if($activeModule === 'analytics') role="tablist" @endif
    >
        @if($activeModule === 'analytics')
            @foreach($analyticsViews as $view)
                @php($isActiveAnalyticsView = $analyticsTab === $view['target'])
                <button
                    type="button"
                    class="reports-module-tab ba-tab-btn {{ $isActiveAnalyticsView ? 'active is-active' : '' }}"
                    data-target="{{ $view['target'] }}"
                    role="tab"
                    aria-selected="{{ $isActiveAnalyticsView ? 'true' : 'false' }}"
                >
                    <i class="bi {{ $view['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $view['label'] }}</span>
                </button>
            @endforeach
        @else
            @foreach($reportTypes as $type => $label)
                <button
                    type="button"
                    class="reports-module-tab"
                    :class="{ 'is-active': filters.report_type === @js($type) }"
                    @click.prevent="selectReportType(@js($type))"
                >
                    <i class="bi {{ match ($type) {
                        'owner_branch_analytics' => 'bi-building',
                        'sales' => 'bi-briefcase-fill',
                        'master_cases' => 'bi-clipboard-data',
                        'audit_logs' => 'bi-shield-check',
                        default => 'bi-file-earmark-text',
                    } }}" aria-hidden="true"></i>
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        @endif
    </nav>

    @if($activeModule === 'analytics')
        <div class="reports-module-controls" aria-label="Analytics scope controls">
            <form method="GET" action="{{ route($analyticsRouteName) }}" class="reports-module-control-wrap">
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

            <form method="GET" action="{{ route($analyticsRouteName) }}" class="reports-module-control-wrap">
                <i class="bi bi-building" aria-hidden="true"></i>
                <input type="hidden" name="range" value="{{ in_array($range, ['TODAY', 'THIS_MONTH', 'THIS_YEAR'], true) ? $range : 'THIS_YEAR' }}">
                <input type="hidden" name="analytics_tab" value="{{ $analyticsTab }}">
                <select
                    id="analyticsHeaderBranch"
                    name="branch_id"
                    class="reports-module-control is-branch"
                    aria-label="Branch"
                    onchange="this.form.submit()"
                >
                    @if($canSelectAllBranches)
                        <option value="">All Branches</option>
                    @endif
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
                    <option value="">All Dates</option>
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
                    class="reports-module-control is-branch"
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

            <label class="reports-module-control-wrap" for="reportsHeaderPaymentStatus" x-show="shows('payment_status')" x-cloak>
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                <select
                    id="reportsHeaderPaymentStatus"
                    class="reports-module-control is-compact"
                    aria-label="Payment status"
                    x-model="filters.payment_status"
                    @change="loadPreview()"
                >
                    <option value="">All Payment Statuses</option>
                    <option value="PAID">Paid</option>
                    <option value="PARTIAL">Partial</option>
                    <option value="UNPAID">Unpaid</option>
                </select>
            </label>

            <label class="reports-module-control-wrap" for="reportsHeaderAuditUser" x-show="shows('audit_user') && auditOptions.supports_user" x-cloak>
                <i class="bi bi-person" aria-hidden="true"></i>
                <select
                    id="reportsHeaderAuditUser"
                    class="reports-module-control is-compact"
                    aria-label="Audit user"
                    x-model="filters.user_id"
                    @change="loadPreview()"
                >
                    <option value="">All Users</option>
                    <template x-for="user in users" :key="user.id">
                        <option :value="user.id" x-text="user.name"></option>
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

<script data-reports-module-navigation-script>
window.SabanganReportsModuleNavigation = window.SabanganReportsModuleNavigation || (() => {
    const shellSelector = '[data-reports-module-shell]';
    let isLoading = false;

    const styleFingerprint = (style) => {
        const text = style.textContent || '';
        return `${text.length}:${text.slice(0, 80)}:${text.slice(-80)}`;
    };

    const isModuleStyle = (style) => {
        const text = style.textContent || '';
        return text.includes('.reports-module-header')
            || text.includes('.reports-page')
            || text.includes('.ba-shell');
    };

    const syncPageStyles = (doc) => {
        const incomingStyles = Array.from(doc.querySelectorAll('style')).filter(isModuleStyle);
        const incomingFingerprints = new Set(incomingStyles.map(styleFingerprint));
        const existingFingerprints = new Set(
            Array.from(document.querySelectorAll('style[data-reports-module-style]')).map(styleFingerprint)
        );

        document.querySelectorAll('style[data-reports-module-style]').forEach((style) => {
            if (!incomingFingerprints.has(styleFingerprint(style))) {
                style.remove();
            }
        });

        incomingStyles.forEach((style) => {
            const fingerprint = styleFingerprint(style);
            if (existingFingerprints.has(fingerprint)) {
                return;
            }

            const clone = document.createElement('style');
            clone.dataset.reportsModuleStyle = 'true';
            clone.textContent = style.textContent;
            document.head.appendChild(clone);
            existingFingerprints.add(fingerprint);
        });
    };

    const executePageScripts = (doc) => {
        doc.querySelectorAll('script[data-reports-module-page-script], script[data-ba-analytics-script]').forEach((script) => {
            if (!script.textContent) return;

            try {
                new Function(script.textContent)();
            } catch (error) {
                console.error('Unable to initialize reports module after soft navigation.', error);
            }
        });
    };

    const waitForPaint = () => new Promise((resolve) => {
        requestAnimationFrame(() => requestAnimationFrame(resolve));
    });

    const waitForModuleReady = (shell) => new Promise((resolve) => {
        const needsReadySignal = shell.querySelector('script[data-ba-analytics-script]');
        if (!needsReadySignal) {
            waitForPaint().then(resolve);
            return;
        }

        if (shell.dataset.moduleReady === 'true') {
            resolve();
            return;
        }

        const observer = new MutationObserver(() => {
            if (shell.dataset.moduleReady === 'true') {
                observer.disconnect();
                resolve();
            }
        });

        observer.observe(shell, { attributes: true, attributeFilter: ['data-module-ready'] });
        setTimeout(() => {
            observer.disconnect();
            resolve();
        }, 1400);
    });

    const showLoadingOverlay = () => {
        const overlay = document.createElement('div');
        overlay.dataset.reportsModuleLoadingOverlay = 'true';
        overlay.innerHTML = '<span>Loading module...</span>';
        Object.assign(overlay.style, {
            position: 'fixed',
            inset: '0',
            zIndex: '9999',
            display: 'grid',
            placeItems: 'start end',
            padding: '16px',
            background: 'rgba(234, 241, 226, 0.38)',
            pointerEvents: 'none',
        });

        const pill = overlay.firstElementChild;
        Object.assign(pill.style, {
            minHeight: '34px',
            display: 'inline-flex',
            alignItems: 'center',
            padding: '0 12px',
            border: '1px solid var(--records-border, var(--border))',
            borderRadius: '999px',
            background: 'var(--records-card-alt, #fff)',
            color: 'var(--ink, #263126)',
            fontSize: '12px',
            fontWeight: '800',
            boxShadow: 'none',
        });

        document.body.appendChild(overlay);
        return overlay;
    };

    const load = async (href, mode = 'push') => {
        if (isLoading) return;

        const url = new URL(href, window.location.origin);
        const currentShell = document.querySelector(shellSelector);
        if (!currentShell) {
            console.warn('Reports module shell was not found; soft navigation was cancelled.');
            return;
        }

        isLoading = true;
        const loadingOverlay = showLoadingOverlay();
        document.documentElement.classList.add('reports-module-soft-loading');
        document.documentElement.classList.add('ba-soft-loading');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                console.warn('Reports module soft navigation returned an error response.', response.status);
                return;
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextShell = doc.querySelector(shellSelector);

            if (!nextShell) {
                console.warn('Reports module response did not include a module shell; soft navigation was cancelled.');
                return;
            }

            syncPageStyles(doc);
            nextShell.classList.add('is-module-entering');
            nextShell.style.opacity = '0';
            nextShell.style.visibility = 'hidden';
            nextShell.style.pointerEvents = 'none';
            currentShell.replaceWith(nextShell);
            document.title = doc.title || document.title;
            executePageScripts(doc);
            window.Alpine?.initTree?.(nextShell);
            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
            window.history[mode === 'replace' ? 'replaceState' : 'pushState']({ reportsModule: true }, '', url.toString());
            await waitForModuleReady(nextShell);
            await waitForPaint();
            await new Promise((resolve) => setTimeout(resolve, 80));
            nextShell.classList.remove('is-module-entering');
            nextShell.style.visibility = '';
            nextShell.style.pointerEvents = '';
            nextShell.style.transition = 'opacity 0.18s ease-out';
            nextShell.style.opacity = '1';
            nextShell.classList.add('is-module-ready');
            setTimeout(() => {
                nextShell.classList.remove('is-module-ready');
                nextShell.style.transition = '';
            }, 220);
        } catch (error) {
            console.error('Reports module soft navigation failed.', error);
        } finally {
            loadingOverlay?.remove();
            isLoading = false;
            document.documentElement.classList.remove('reports-module-soft-loading');
            document.documentElement.classList.remove('ba-soft-loading');
        }
    };

    const handleLinkClick = (link, event = null) => {
        if (event?.defaultPrevented) return false;
        if (event && (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) {
            return true;
        }

        const target = link.dataset?.href || link.getAttribute('href') || '';
        if (!target) return false;

        const url = new URL(target, window.location.origin);
        if (url.origin !== window.location.origin) return true;

        event?.preventDefault();
        if (url.toString() !== window.location.href) {
            const label = link.querySelector?.('span')?.textContent?.trim()
                || link.textContent?.trim()
                || 'Reports & Analytics';

            load(url).then(() => {
                window.SabanganPageContextToast?.show?.(label);
            });
        }

        return false;
    };

    const init = () => {
        if (window.__sabanganReportsModuleNavigationBound) return;
        window.__sabanganReportsModuleNavigationBound = true;

        document.addEventListener('click', (event) => {
            const link = event.target.closest?.('[data-reports-module-link]');
            if (!link) {
                return;
            }
            handleLinkClick(link, event);
        });

        window.addEventListener('popstate', () => {
            const currentShell = document.querySelector(shellSelector);
            if (currentShell) {
                load(window.location.href, 'replace');
            }
        });
    };

    return { init, load, handleLinkClick };
})();

window.SabanganReportsModuleNavigation.init();
</script>
