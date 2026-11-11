<style>
/* Modern analytics layer: plain-language hierarchy over the preserved chart workflows. */
html[data-theme] body .ba-shell {
    --analytics-space: 16px;
    --analytics-blue: #315f86;
    --analytics-purple: #67558c;
    --analytics-green: #1f6b4f;
    --analytics-amber: #986018;
    background: var(--page-bg, var(--surface-muted)) !important;
    color: var(--ink) !important;
}

html[data-theme] body .ba-shell .ba-workspace,
html[data-theme] body .ba-shell .ba-card.ba-workspace {
    display: grid !important;
    gap: var(--analytics-space) !important;
    padding: 0 !important;
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    overflow: visible !important;
}

.ra-modern-intro {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--analytics-space);
    padding: 22px 24px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: var(--card);
}

.ra-modern-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--brand);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.ra-modern-intro h2 {
    margin: 5px 0 4px;
    color: var(--ink);
    font: 600 clamp(20px, 1.4vw, 26px)/1.25 var(--font-heading);
}

.ra-modern-intro p {
    max-width: 720px;
    margin: 0;
    color: var(--ink-muted);
    font-size: 13px;
    line-height: 1.6;
}

.ra-modern-scope {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
}

.ra-modern-scope > span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 34px;
    padding: 6px 10px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: var(--surface-muted);
    color: var(--ink);
    font-size: 12px;
    white-space: nowrap;
}

html[data-theme] body .ba-shell .ra-kpi-grid {
    gap: var(--analytics-space) !important;
}

html[data-theme] body .ba-shell .ra-kpi-card {
    position: relative;
    min-height: 174px;
    padding: 18px !important;
    border: 1px solid var(--border) !important;
    border-radius: 16px !important;
    background: var(--card) !important;
    box-shadow: none !important;
    overflow: hidden;
    transition: border-color .16s ease, background-color .16s ease;
}

html[data-theme] body .ba-shell .ra-kpi-card::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--analytics-blue);
}

html[data-theme] body .ba-shell .ra-kpi-card:nth-child(2)::before { background: var(--analytics-purple); }
html[data-theme] body .ba-shell .ra-kpi-card:nth-child(3)::before { background: var(--analytics-green); }
html[data-theme] body .ba-shell .ra-kpi-card:nth-child(4)::before { background: var(--analytics-amber); }

html[data-theme] body .ba-shell .ra-kpi-card:hover {
    border-color: color-mix(in srgb, var(--brand) 48%, var(--border)) !important;
    background: color-mix(in srgb, var(--card) 92%, var(--surface-muted)) !important;
}

html[data-theme] body .ba-shell .ra-kpi-heading {
    align-items: center;
    margin-bottom: 12px;
    color: var(--ink-muted) !important;
}

html[data-theme] body .ba-shell .ra-kpi-heading > span {
    color: var(--ink-muted) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    letter-spacing: .02em !important;
    text-transform: none !important;
}

html[data-theme] body .ba-shell .ra-kpi-heading > i {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--analytics-blue) 13%, transparent);
    color: var(--analytics-blue);
    font-size: 16px !important;
}

html[data-theme] body .ba-shell .ra-kpi-card:nth-child(2) .ra-kpi-heading > i { background: color-mix(in srgb, var(--analytics-purple) 13%, transparent); color: var(--analytics-purple); }
html[data-theme] body .ba-shell .ra-kpi-card:nth-child(3) .ra-kpi-heading > i { background: color-mix(in srgb, var(--analytics-green) 13%, transparent); color: var(--analytics-green); }
html[data-theme] body .ba-shell .ra-kpi-card:nth-child(4) .ra-kpi-heading > i { background: color-mix(in srgb, var(--analytics-amber) 14%, transparent); color: var(--analytics-amber); }

html[data-theme] body .ba-shell .ra-kpi-content > strong {
    margin-bottom: 16px;
    color: var(--ink) !important;
    font-family: var(--font-body) !important;
    font-size: clamp(24px, 2vw, 30px) !important;
    font-weight: 600 !important;
    letter-spacing: 0 !important;
}

html[data-theme] body .ba-shell .ra-kpi-card:nth-child(3) .ra-kpi-content > strong { color: var(--analytics-green) !important; }
html[data-theme] body .ba-shell .ra-kpi-warning .ra-kpi-content > strong { color: var(--analytics-amber) !important; }

html[data-theme] body .ba-shell .ra-kpi-footer {
    align-items: flex-end;
    padding-top: 12px;
    border-color: var(--border);
}

html[data-theme] body .ba-shell .ra-kpi-footer > small {
    color: var(--ink-muted) !important;
    font-size: 11px !important;
    font-weight: 400 !important;
}

html[data-theme] body .ba-shell .ra-kpi-action {
    color: var(--brand) !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
}

html[data-theme] body .ba-shell .ra-kpi-action:hover,
html[data-theme] body .ba-shell .ra-kpi-action:focus-visible {
    color: var(--ink) !important;
    text-decoration: underline !important;
    text-underline-offset: 4px;
}

html[data-theme] body .ba-shell .ba-workspace > .ra-analytics-tabs {
    display: inline-flex !important;
    justify-self: start;
    width: auto !important;
    max-width: 100%;
    gap: 6px !important;
    padding: 5px !important;
    border: 1px solid var(--border) !important;
    border-radius: 14px !important;
    background: var(--surface-muted) !important;
    overflow-x: auto !important;
}

html[data-theme] body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn {
    min-height: 40px !important;
    padding: 8px 14px !important;
    border: 0 !important;
    border-radius: 10px !important;
    background: transparent !important;
    color: var(--ink-muted) !important;
    font-size: 13px !important;
    font-weight: 500 !important;
}

html[data-theme] body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn:hover {
    background: color-mix(in srgb, var(--card) 72%, transparent) !important;
    color: var(--ink) !important;
}

html[data-theme] body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn.active,
html[data-theme] body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn.is-active {
    background: var(--brand) !important;
    color: #fff !important;
}

html[data-theme] body .ba-shell .ba-panel.active,
html[data-theme] body .ba-shell .ba-performance-card,
html[data-theme] body .ba-shell .ba-compare-card,
html[data-theme] body .ba-shell .ba-drilldown {
    background: var(--card) !important;
    border: 1px solid var(--border) !important;
    border-radius: 16px !important;
    box-shadow: none !important;
}

html[data-theme] body .ba-shell .ba-panel.active,
html[data-theme] body .ba-shell .ba-performance-card {
    padding: 20px !important;
}

html[data-theme] body .ba-shell .ba-panel-head,
html[data-theme] body .ba-shell .ba-performance-card > .ba-panel-head {
    gap: var(--analytics-space) !important;
    margin: 0 0 var(--analytics-space) !important;
}

html[data-theme] body .ba-shell .ba-payment-layout,
html[data-theme] body .ba-shell .ba-drilldown-kpis,
html[data-theme] body .ba-shell .ba-payment-status-cards {
    gap: var(--analytics-space) !important;
}

html[data-theme] body .ba-shell .ba-panel.active > :is(.ba-chart-frame, .ba-payment-layout, .ba-payment-settlement, .ba-compare-card) + :is(.ba-chart-frame, .ba-payment-layout, .ba-payment-settlement, .ba-compare-card),
html[data-theme] body .ba-shell .ba-performance-card > :is(.ba-chart-frame, .ba-compare-card) + :is(.ba-chart-frame, .ba-compare-card) {
    margin-top: var(--analytics-space) !important;
}

html[data-theme] body .ba-shell .ba-panel-title,
html[data-theme] body .ba-shell .ba-compare-title {
    color: var(--ink) !important;
    font-size: 17px !important;
    font-weight: 600 !important;
}

html[data-theme] body .ba-shell .ba-panel-subtitle,
html[data-theme] body .ba-shell .ba-panel-note,
html[data-theme] body .ba-shell .ba-table-scroll-hint {
    color: var(--ink-muted) !important;
    font-weight: 400 !important;
}

html[data-theme] body .ba-shell .ba-panel-note {
    padding: 0 !important;
    background: transparent !important;
    border-radius: 0 !important;
}

html[data-theme] body .ba-shell .ba-chart-frame,
html[data-theme] body .ba-shell .ba-performance-card .ba-chart-frame,
html[data-theme] body .ba-shell .ba-panel.active > .ba-chart-frame {
    background: var(--surface-muted) !important;
    background-image: none !important;
    border: 1px solid var(--border) !important;
    border-radius: 14px !important;
    box-shadow: none !important;
}

html[data-theme] body .ba-shell .ba-chart-mode-toggle {
    padding: 4px !important;
    border: 1px solid var(--border) !important;
    border-radius: 10px !important;
    background: var(--surface-muted) !important;
}

html[data-theme] body .ba-shell .ba-chart-mode-btn {
    min-height: 34px !important;
    padding: 6px 10px !important;
    border: 0 !important;
    border-radius: 7px !important;
    background: transparent !important;
    color: var(--ink-muted) !important;
}

html[data-theme] body .ba-shell .ba-chart-mode-btn.active,
html[data-theme] body .ba-shell .ba-chart-mode-btn[aria-pressed='true'] {
    background: var(--brand) !important;
    color: #fff !important;
}

html[data-theme] body .ba-shell .ra-period-breakdown {
    padding: 16px 18px !important;
}

html[data-theme] body .ba-shell .ra-period-breakdown > summary {
    padding: 0 !important;
}

html[data-theme] body .ba-shell .ba-compare-table-wrap {
    border-color: var(--border) !important;
    border-radius: 12px !important;
    background: var(--card) !important;
}

html[data-theme] body .ba-shell .ba-compare-table,
html[data-theme] body .ba-shell .ba-compare-table tbody td {
    background: var(--card) !important;
    color: var(--ink) !important;
}

html[data-theme] body .ba-shell .ba-compare-table thead th {
    background: var(--surface-muted) !important;
    color: var(--ink-muted) !important;
}

html[data-theme] body .ba-shell .ba-compare-table tbody tr:hover td {
    background: color-mix(in srgb, var(--surface-muted) 78%, var(--card)) !important;
}

html[data-theme='dark'] body .ba-shell {
    --analytics-blue: #86bcea;
    --analytics-purple: #c1aae8;
    --analytics-green: #78d6ad;
    --analytics-amber: #efbd70;
}

@media (max-width: 900px) {
    .ra-modern-intro { flex-direction: column; }
    .ra-modern-scope { justify-content: flex-start; }
    html[data-theme] body .ba-shell .ba-workspace > .ra-analytics-tabs { width: 100% !important; }
}

@media (max-width: 639px) {
    .ra-modern-intro { padding: 18px; border-radius: 14px; }
    .ra-modern-scope { width: 100%; }
    .ra-modern-scope > span { max-width: 100%; white-space: normal; }
    html[data-theme] body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn { padding-inline: 12px !important; }
    html[data-theme] body .ba-shell .ba-panel.active,
    html[data-theme] body .ba-shell .ba-performance-card { padding: 16px !important; }
}

@media (prefers-reduced-motion: reduce) {
    html[data-theme] body .ba-shell .ra-kpi-card { transition: none; }
}
</style>
