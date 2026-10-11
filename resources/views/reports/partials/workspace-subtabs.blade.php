@php
    $analyticsTab = request('analytics_tab', 'ba-panel-performance');
    $analyticsViews = $analyticsViews ?? [
        ['label' => '1. Overview (Performance & Branch Bar Chart)', 'icon' => 'bi-bar-chart-line', 'target' => 'ba-panel-performance'],
        ['label' => '2. Payments (Payment Doughnut Chart)', 'icon' => 'bi-pie-chart', 'target' => 'ba-panel-payment'],
        ['label' => '3. Collections (Snapshot & Period Bar Chart)', 'icon' => 'bi-credit-card', 'target' => 'ba-panel-collection'],
        ['label' => '4. Revenue Trend (Line Chart & Pacing)', 'icon' => 'bi-graph-up-arrow', 'target' => 'ba-panel-trend'],
    ];
@endphp
    <nav
        class="reports-module-tabs reports-module-subtabs {{ $activeModule === 'analytics' ? 'ra-analytics-tabs' : '' }}"
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
