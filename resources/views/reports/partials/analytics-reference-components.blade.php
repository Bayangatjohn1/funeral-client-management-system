<style>
.ba-shell .ba-panel { scroll-margin-top:20px; }
.ba-shell .ra-period-breakdown { margin-top:16px; }
.ba-shell .ra-period-breakdown > summary { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:4px 0; cursor:pointer; list-style:none; color:var(--ra-text); }
.ba-shell .ra-period-breakdown > summary::-webkit-details-marker { display:none; }
.ba-shell .ra-period-breakdown > summary > span { display:grid; grid-template-columns:18px minmax(0,1fr); align-items:center; gap:4px 10px; }
.ba-shell .ra-period-breakdown > summary strong { font-size:14px; }
.ba-shell .ra-period-breakdown > summary small { grid-column:2; font-size:12px; color:var(--ra-secondary); }
.ba-shell .ra-period-chevron { transition:transform .2s ease; }
.ba-shell .ra-period-breakdown[open] .ra-period-chevron { transform:rotate(180deg); }
.ba-shell .ra-period-breakdown[open] > summary { margin-bottom:12px; }
.ba-shell .ra-period-breakdown > .ba-compare-table-wrap { margin-top:0; }
.ba-shell .ra-period-breakdown > .ba-breakdown-note { margin:0 0 12px; padding:0; line-height:1.5; }
.ba-shell .ra-period-breakdown[open] > summary:has(+ .ba-breakdown-note) { margin-bottom:8px; }
.ba-shell .ra-period-breakdown > summary:focus-visible { outline:2px solid var(--ra-secondary); outline-offset:4px; border-radius:4px; }
/* Empty chart content is an overlay, not a second light-colored graph surface. */
html[data-theme] body .ba-shell .ba-chart-empty { background:transparent !important; background-image:none !important; border:0 !important; box-shadow:none !important; color:var(--ra-secondary) !important; }
html[data-theme] body .ba-shell .ba-chart-empty strong { color:var(--ra-text) !important; }
html[data-theme] body .ba-shell .ba-chart-empty :is(i,span) { color:var(--ra-secondary) !important; }
html[data-theme='dark'] body .ba-shell .ba-chart-frame { background:var(--ra-muted) !important; background-image:none !important; border-color:var(--ra-border) !important; box-shadow:none !important; }
html[data-theme] body .ba-shell .ra-period-table :is(th,td):not(:first-child) { text-align:right !important; font-variant-numeric:tabular-nums; }
html[data-theme] body .ba-shell .ra-period-table td:nth-child(n+3) { font-family:ui-monospace,SFMono-Regular,Consolas,monospace !important; }
html[data-theme] body .ba-shell .ra-period-table .has-activity td { font-weight:700 !important; }
html[data-theme] body .ba-shell .ra-period-table .no-activity td { color:var(--ra-secondary) !important; }
@media(prefers-reduced-motion:reduce) { .ba-shell .ra-period-chevron { transition:none; } }
/* Submit button styles must not make every date preset appear selected. */
html body #analyticsDatePresets > button[aria-pressed='false'] { background:transparent !important; color:#1B3E2E !important; border:1px solid transparent !important; box-shadow:none !important; }
html body #analyticsDatePresets > button[aria-pressed='true'] { background:#1B4332 !important; color:#FFFFFF !important; border:1px solid #1B4332 !important; box-shadow:none !important; }
html body #analyticsDatePresets > button[aria-pressed='false']:hover { background:#EAF2ED !important; color:#1B4332 !important; border-color:#AEC7B7 !important; }
html body #analyticsDatePresets > button[aria-pressed='true']:hover { background:#123325 !important; color:#FFFFFF !important; }
html[data-theme='dark'] body #analyticsDatePresets > button[aria-pressed='false'] { color:#D5EDE1 !important; }
html[data-theme='dark'] body #analyticsDatePresets > button[aria-pressed='false']:hover { background:#213D2E !important; border-color:#42785A !important; }
/* Reference TSX: p-4/sm:p-5, text-2xl/sm:text-3xl, divided footer. */
.ba-shell .ra-kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin:0; }
.ba-shell .ra-kpi-card { display:flex; flex-direction:column; justify-content:space-between; min-width:0; padding:20px; border:1px solid var(--ra-border); border-radius:16px; background:var(--ra-surface); box-shadow:0 1px 2px rgba(8,29,19,.05); }
.ba-shell .ra-kpi-card:hover { border-color:#1B4332; }
.ba-shell .ra-kpi-content { display:flex; flex-direction:column; min-width:0; height:100%; }
.ba-shell .ra-kpi-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:4px; color:var(--ra-secondary); }
.ba-shell .ra-kpi-heading > span { font-size:10px; font-weight:700; line-height:1.5; letter-spacing:.05em; text-transform:uppercase; }
.ba-shell .ra-kpi-heading > i { font-size:16px; line-height:16px; flex:0 0 16px; }
.ba-shell .ra-kpi-content > strong { display:block; font-size:30px; font-weight:900; line-height:1.2; color:var(--ra-text); font-variant-numeric:tabular-nums; letter-spacing:0; overflow-wrap:anywhere; margin:0 0 12px; }
.ba-shell .ra-kpi-card:not(:first-child) .ra-kpi-content > strong { font-family:ui-monospace,SFMono-Regular,Consolas,monospace; }
.ba-shell .ra-kpi-card:nth-child(3) .ra-kpi-content > strong { color:#1B4332; }
.ba-shell .ra-kpi-warning .ra-kpi-content > strong { color:#78350F; }
.ba-shell .ra-kpi-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:auto; padding-top:10px; border-top:1px solid var(--ra-border); }
.ba-shell .ra-kpi-footer > small { font-size:11px; font-weight:400; line-height:1.5; color:var(--ra-secondary); text-transform:none; }
.ba-shell .ra-kpi-action { display:inline-flex; align-items:center; justify-content:flex-end; gap:4px; flex-shrink:0; padding:0; margin:0; min-height:28px; border:0; border-radius:0; background:transparent; color:var(--ra-secondary); font-family:inherit; font-size:11px; font-weight:700; text-decoration:none; cursor:pointer; }
.ba-shell .ra-kpi-action:hover { text-decoration:underline; }
html[data-theme='dark'] .ba-shell .ra-kpi-card:nth-child(3) .ra-kpi-content > strong { color:#6EE7B7; }
html[data-theme='dark'] .ba-shell .ra-kpi-warning .ra-kpi-content > strong { color:#FBBF24; }
/* Match the reference tab strip: 12px labels, 16px horizontal padding, no pills. */
html body .ba-shell .ba-workspace > .ra-analytics-tabs { display:flex !important; gap:8px !important; justify-content:flex-start !important; width:100% !important; margin:0 !important; padding:0 !important; border:0 !important; border-bottom:1px solid var(--ra-border) !important; border-radius:0 !important; background:transparent !important; overflow-x:auto !important; }
html body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn { display:flex !important; flex:0 0 auto !important; align-items:center !important; justify-content:flex-start !important; gap:6px !important; min-width:0 !important; min-height:40px !important; padding:8px 16px !important; margin:0 !important; border:0 !important; border-bottom:2px solid transparent !important; border-radius:12px 12px 0 0 !important; background:transparent !important; box-shadow:none !important; color:var(--ra-secondary) !important; font-size:12px !important; font-weight:700 !important; line-height:1.5 !important; white-space:nowrap !important; }
html body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn::after { display:none !important; }
html body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn.active { border-bottom-color:#1B4332 !important; background:var(--ra-surface) !important; color:var(--ra-text) !important; }
html body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn > i { font-size:14px !important; }
html[data-theme='dark'] body .ba-shell .ba-workspace > .ra-analytics-tabs > .ba-tab-btn.active { border-bottom-color:#6EE7B7 !important; }
/* Reference tables: 10px uppercase header, 12px rows, 10px/14px padding. */
html[data-theme] body .ba-shell .ba-compare-table-wrap { max-height:none !important; overflow-x:auto !important; overflow-y:hidden !important; scrollbar-gutter:auto !important; border:1px solid var(--ra-border) !important; border-radius:14px !important; }
html[data-theme] body .ba-shell .ba-compare-table { width:100% !important; border-collapse:collapse !important; border-spacing:0 !important; font-family:inherit !important; }
html[data-theme] body .ba-shell .ba-compare-table thead th { position:static !important; padding:10px 14px !important; background:var(--ra-muted) !important; color:var(--ra-secondary) !important; font-size:10px !important; font-weight:700 !important; line-height:1.5 !important; letter-spacing:.05em !important; text-transform:uppercase !important; border:0 !important; border-bottom:1px solid var(--ra-border) !important; }
html[data-theme] body .ba-shell .ba-compare-table tbody td { padding:10px 14px !important; font-size:12px !important; line-height:1.5 !important; font-weight:400 !important; background:var(--ra-surface) !important; color:var(--ra-text) !important; border:0 !important; border-bottom:1px solid var(--ra-border) !important; }
html[data-theme] body .ba-shell .ba-compare-table tbody tr:nth-child(even) td { background:var(--ra-surface) !important; }
html[data-theme] body .ba-shell .ba-compare-table tbody tr:hover td { background:#EAF2ED !important; }
html[data-theme='dark'] body .ba-shell .ba-compare-table tbody tr:hover td { background:#12241A !important; }
html[data-theme] body .ba-shell .ba-compare-table tbody tr:last-child td { border-bottom:0 !important; }
html[data-theme] body .ba-shell .ba-cases-table td:is(:first-child,:nth-child(8),:nth-child(9)) { font-family:ui-monospace,SFMono-Regular,Consolas,monospace !important; font-weight:700 !important; font-variant-numeric:tabular-nums; }
html[data-theme] body .ba-shell .ba-compare-table td :is(.ba-status-pill,.ba-status-badge) { padding:3px 8px !important; border-radius:4px !important; font-size:10px !important; line-height:1.4 !important; }
html[data-theme] body .ba-shell .ba-drilldown > .ba-compare-card { border:0 !important; border-radius:0 !important; padding:0 !important; background:transparent !important; }
@media(max-width:1023px) { .ba-shell .ra-kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:639px) { .ba-shell .ra-kpi-grid { grid-template-columns:minmax(0,1fr); }.ba-shell .ra-kpi-card { padding:16px; }.ba-shell .ra-kpi-content > strong { font-size:24px; } }
</style>
