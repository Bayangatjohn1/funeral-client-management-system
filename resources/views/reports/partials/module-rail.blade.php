@php
    $activeModule = $activeModule ?? 'analytics';
    $quickStats = $quickStats ?? [];
    $analyticsViews = $analyticsViews ?? [
        ['label' => 'Overview', 'icon' => 'bi-bar-chart-line', 'target' => 'ba-panel-performance'],
        ['label' => 'Payments', 'icon' => 'bi-wallet2', 'target' => 'ba-panel-payment'],
        ['label' => 'Collections', 'icon' => 'bi-cash-stack', 'target' => 'ba-panel-collection'],
        ['label' => 'Revenue Trend', 'icon' => 'bi-graph-up-arrow', 'target' => 'ba-panel-trend'],
    ];
    $reportTypes = $reportTypes ?? [];
    $currentReportType = $currentReportType ?? request('report_type');
@endphp

<style>
        .reports-module-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: start;
            min-height: calc(100vh - var(--topbar-h, 62px) - 6rem);
            border-top: 1px solid var(--records-border, var(--border));
            margin-inline: calc(var(--panel-content-inline, 20px) * -1);
        }
        .reports-module-main {
            min-width: 0;
            display: grid;
            gap: 14px;
            padding: 18px var(--panel-content-inline, 20px) 0;
        }
        .reports-module-main > * {
            max-width: 100%;
            box-sizing: border-box;
        }
        .reports-module-rail {
            display: none !important;
        }
        .reports-rail-heading {
            margin: 0 0 0.65rem;
            padding: 0 0.6rem;
            color: var(--ink-muted, #6B7568);
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .reports-rail-nav {
            display: grid;
            gap: 0.3rem;
        }
        .reports-rail-link {
            min-height: 42px;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 0.7rem;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            color: var(--ink, #163B25);
            font: inherit;
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.15;
            text-align: left;
            text-decoration: none;
            cursor: pointer;
        }
        .reports-rail-link:hover,
        .reports-rail-link:focus-visible {
            background: rgba(47, 82, 51, 0.1);
            color: var(--ink, #163B25);
            outline: none;
        }
        .reports-rail-link.active,
        .reports-rail-link.is-active {
            background: #2F5233 !important;
            border-color: #2F5233 !important;
            color: #fff !important;
            box-shadow: none !important;
        }
        .reports-rail-link i {
            width: 1.1rem;
            flex: 0 0 1.1rem;
            text-align: center;
            font-size: 0.95rem;
        }
        .reports-rail-stats {
            margin-top: 2rem;
            display: grid;
            gap: 0.55rem;
        }
        .reports-rail-stats-top {
            margin-top: 0;
        }
        .reports-rail-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.8rem;
            padding: 0 0.6rem;
            color: var(--ink-muted, #6B7568);
            font-size: 0.78rem;
        }
        .reports-rail-stat strong {
            color: var(--ink, #143A25);
            font-weight: 800;
            text-align: right;
        }
        html body .reports-module-layout .ba-head-row-top {
            display: none !important;
        }
        html body .reports-module-layout .ba-filter-row {
            display: none !important;
        }
        html body .reports-module-layout .ba-workspace {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            margin: 0 !important;
        }
        @media (max-width: 980px) {
            .reports-module-layout {
                grid-template-columns: 1fr;
                margin-inline: 0;
                border-top: 0;
            }
            .reports-module-rail {
                position: static;
                min-height: auto;
                padding: 0.75rem;
                border: 1px solid var(--records-border, var(--border));
                border-radius: 12px;
            }
            .reports-rail-nav {
                display: flex;
                gap: 0.4rem;
                overflow-x: auto;
                scrollbar-width: none;
            }
            .reports-rail-nav::-webkit-scrollbar {
                display: none;
            }
            .reports-rail-link {
                flex: 0 0 auto;
                width: auto;
                white-space: nowrap;
            }
            .reports-rail-stats {
                display: none;
            }
            .reports-module-main {
                padding: 0;
            }
        }
    </style>
