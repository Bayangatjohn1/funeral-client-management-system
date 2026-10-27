<style>
.oe { padding:12px var(--panel-content-inline,20px) 32px; display:grid; gap:24px; font:400 14px/1.5 var(--font-body); color:var(--ink); }
.oe * { box-sizing:border-box; min-width:0; }
.oe-card.oe-executive-banner { padding:16px 20px; overflow:visible; }
.oe-header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding-bottom:16px; border-bottom:1px solid var(--border); }
.oe-header-actions { display:flex; align-items:center; flex-wrap:wrap; justify-content:flex-end; gap:10px; }
.oe-suite-label { text-transform:uppercase; letter-spacing:.08em; font-weight:600; }
.oe-header h1 { margin:4px 0; font-size:26px !important; font-weight:600 !important; line-height:1.25; }
.oe-header p { margin:4px 0 0; font-size:13px; color:var(--ink-muted); max-width:700px; }
.oe-eyebrow { display:inline-flex; align-items:center; gap:8px; font-size:12px; color:var(--ink-muted); }
.oe-card { border:1px solid var(--border); border-radius:16px; background:var(--card); overflow:hidden; }
.oe-filter-card { display:flex; align-items:center; flex-wrap:wrap; gap:16px; padding:20px 0 0; }
.oe-filter-card form { display:flex; align-items:flex-end; flex-wrap:wrap; gap:12px; flex:0 1 auto; max-width:100%; }
.oe-filter-card label { display:grid; gap:6px; flex:0 1 240px; width:240px; max-width:100%; color:var(--ink-muted); font-size:12px; }
.oe :is(select,input),.oe-dialog :is(select,input) { width:100%; height:44px; padding:0 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--ink); font:400 14px var(--font-body); }
.oe-filter-card label:has([name="range"]) { flex-basis:200px; width:200px; }
.oe-filter-context { display:flex; align-items:center; flex-wrap:wrap; gap:8px 16px; order:3; flex:1 0 100%; color:var(--ink-muted); font-size:12px; }
.oe-scope-chip { display:inline-flex; align-items:center; gap:8px; padding:4px 8px 4px 12px; border:1px solid var(--border); border-radius:999px; background:var(--surface-muted); color:var(--ink); }
.oe-scope-date { display:inline-flex; align-items:center; flex-wrap:wrap; gap:8px; }
.oe-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
.oe-button,.oe-icon-button { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px; padding:8px 12px; background:var(--surface-muted); color:var(--ink); border:1px solid var(--border); border-radius:8px; font:500 13px var(--font-body); cursor:pointer; text-decoration:none; white-space:nowrap; }
.oe-primary { background:#1B4332; color:#fff; border-color:#1B4332; }
.oe-button:hover,.oe-icon-button:hover { background:var(--owner-hover); }
.oe-primary:hover { background:#123325; color:#fff; }
.oe-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
.oe-kpi { text-align:left; padding:20px; display:flex; flex-direction:column; gap:18px; color:var(--ink); cursor:pointer; font-family:var(--font-body); transition:background-color .15s,border-color .15s; }
.oe-kpi:hover { background:var(--surface-muted); border-color:var(--brand); }
.oe-kpi-top,.oe-kpi-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.oe-kpi-top { justify-content:flex-start; min-height:40px; align-items:center; font-size:13px; color:var(--ink); }
.oe-kpi-top > div { display:grid; gap:4px; }
.oe-kpi-question { color:var(--ink-muted); font-size:11px; }
.oe-kpi-label { font-size:14px; line-height:1.5; font-weight:500; }
.oe-kpi-measure { display:grid; grid-template-rows:auto minmax(20px,auto); gap:8px; padding:0; }
.oe-kpi-top i { display:grid; place-items:center; background:var(--surface-muted); border-radius:8px; width:36px; height:36px; font-size:18px; }
.oe-kpi-value { font-size:clamp(24px,2.1vw,32px); line-height:1.3; font-weight:600; font-variant-numeric:tabular-nums; overflow-wrap:anywhere; }
.oe-comparison { color:var(--ink-muted); font-size:12px; line-height:1.6; }
.oe-comparison.is-up { color:var(--brand); }
.oe-comparison.is-down { color:var(--ink-muted); }
.oe-kpi-footer { padding-top:12px; border-top:1px solid var(--border); margin-top:auto; font-size:12px; color:var(--ink-muted); }
.oe-basis { font-size:12px; color:var(--ink-muted); margin:0; }
.oe-section-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; padding:20px; border-bottom:1px solid var(--border); }
.oe h2,.oe-dialog h2 { font:600 18px/1.4 var(--font-body); margin:0; color:var(--ink); }
.oe-section-head p,.oe-risk p,.oe-dialog header p { font-size:13px; color:var(--ink-muted); margin:6px 0 0; }
.oe-segment { display:flex; gap:4px; background:var(--surface-muted); border:1px solid var(--border); padding:4px; border-radius:10px; }
.oe-segment button { padding:8px 12px; min-height:40px; border-radius:6px; color:var(--ink); font-size:13px; }
.oe-segment button[aria-pressed=true] { background:#1B4332; color:#fff; }
.oe-trend-panel[hidden] { display:none; }
.oe-title-icon { display:inline-grid; place-items:center; width:30px; height:30px; margin-right:8px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--brand); font-size:16px; vertical-align:middle; }
html[data-theme] body.panel-shell-body.owner-ui .oe .oe-shared-select { position:relative; display:flex; align-items:center; width:100%; min-width:0; height:44px; min-height:44px; padding:0; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); overflow:visible; }
.oe-shared-select > i:first-child { position:absolute; left:12px; z-index:1; pointer-events:none; color:var(--filter-ui-muted); }
#ownerExecutiveFilters .oe-shared-select > i.bi-chevron-down { position:absolute; right:12px; top:50%; transform:translateY(-50%) rotate(0deg) !important; transform-origin:center; pointer-events:none; }
#ownerExecutiveFilters .oe-shared-select.is-open > i.bi-chevron-down { transform:translateY(-50%) rotate(180deg) !important; }

#ownerExecutiveFilters .oe-shared-select > select { display:block; flex:1; width:100%; min-width:0; max-width:100%; height:42px; margin:0; padding:0 40px 0 36px; background:transparent !important; border:0 !important; border-radius:inherit !important; box-shadow:none !important; appearance:none; font:500 14px var(--font-body); color:var(--ink) !important; cursor:pointer; text-overflow:ellipsis; }
html[data-theme] body.panel-shell-body.owner-ui .oe .oe-shared-select:focus-within { outline:2px solid var(--brand); outline-offset:2px; }
html[data-theme] body.panel-shell-body.owner-ui .oe .oe-shared-select > i { color:var(--ink) !important; }
.oe-filter-card { overflow:visible; }
.oe-clear-branch { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; color:var(--ink); text-decoration:none; }
.oe-clear-branch:hover { background:var(--owner-hover); }
.oe-clear-branch:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
.oe-trend-comparison { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:0; margin:20px 20px 0; padding:18px 0; background:var(--surface); border:1px solid var(--border); border-radius:12px; }
.oe-trend-comparison > div { display:grid; grid-template-rows:auto 28px auto; align-content:start; gap:8px; padding:0 20px; }
.oe-trend-comparison > div + div { border-left:1px solid var(--border); }
.oe-trend-comparison :is(small,span) { font-size:12px; color:var(--ink-muted); }
html[data-theme] body.panel-shell-body.owner-ui .oe-trend-comparison small { color:var(--ink-muted) !important; }
.oe-trend-comparison strong { font-size:20px; line-height:28px; font-weight:500; font-variant-numeric:tabular-nums; color:var(--ink); }
.oe-bar-scroll { overflow-x:auto; margin:0 20px 16px; }
.oe-bars { display:flex; gap:8px; min-width:calc(var(--bar-count) * 52px); height:264px; padding:64px 12px 8px; border-bottom:1px solid var(--border); }
.oe-bar-item { position:relative; flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; background:transparent; border:0; padding:0; font-family:var(--font-body); color:var(--ink-muted); }
.oe-bar-track { display:flex; align-items:flex-end; justify-content:center; height:170px; width:100%; }
.oe-chart-frame { display:grid; grid-template-columns:20px 72px minmax(0,1fr); margin-inline:20px; }
.oe-chart-frame .oe-bar-scroll { margin-inline:0; }
.oe-axis-title { align-self:center; justify-self:center; writing-mode:vertical-rl; transform:rotate(180deg); margin:0; font-size:12px; color:var(--ink-muted); }
.oe-axis-caption { margin:16px 20px 0; font-size:12px; color:var(--ink-muted); }
.oe-axis-caption { text-align:center; margin-top:0; margin-bottom:16px; }
.oe-y-axis { position:relative; height:170px; margin-top:64px; padding-right:10px; text-align:right; font-size:10px; color:var(--ink-muted); font-variant-numeric:tabular-nums; }
.oe-y-axis > span { position:absolute; right:10px; line-height:1; transform:translateY(-50%); }
.oe-bars { position:relative; height:264px; }
.oe-bars::before { content:''; position:absolute; left:0; right:0; top:64px; height:171px; background:repeating-linear-gradient(to bottom,var(--border) 0,var(--border) 1px,transparent 1px,transparent 42.5px); pointer-events:none; }
.oe-bar-track { flex-shrink:0; }
.oe-bar-pair { display:flex; align-items:flex-end; gap:5px; }
.oe-bar-pair .oe-chart-bar { position:relative; width:calc(50% - 2.5px); max-width:42px; flex:none; border:0; padding:0; cursor:pointer; font:inherit; }
.oe-bar-pair > .oe-chart-bar:disabled { display:none; }
.oe-bar-item:has(.oe-chart-bar:is(:hover,:focus-visible)) { z-index:5; }
.oe-chart-bar:is(:hover,:focus-visible) { z-index:6; }
.oe-chart-bar:focus-visible { outline:2px solid var(--ink) !important; outline-offset:3px; }
.oe-bar-previous { display:block; background:#91A49A; border-radius:6px 6px 0 0; }
.oe-bar-previous[hidden] { display:none; }
.oe-bar-previous.is-zero { height:0 !important; }
.oe-chart-legend { display:flex; align-items:center; flex-wrap:wrap; gap:10px 20px; padding:16px 20px 0; font-size:12px; line-height:1.6; color:var(--ink-muted); }
.oe-chart-legend [data-comparison-status] { margin-left:auto; text-align:right; }
.oe-chart-legend > span { display:inline-flex; align-items:center; gap:6px; }
.oe-chart-legend i { width:10px; height:10px; border-radius:2px; background:var(--brand); }
.oe-chart-legend .oe-legend-previous { background:#91A49A; }
html[data-theme="dark"] .oe-bar-previous,html[data-theme="dark"] .oe-chart-legend .oe-legend-previous { background:#A0B4A8; }

.oe-branch-chart { display:grid; grid-template-columns:minmax(0,1fr); padding:8px 24px; gap:0; }
.oe-branch-row.is-top { background:#EFF6FF; border:1px solid #BFDBFE; border-radius:10px; padding:20px 14px; margin-block:8px; }
.oe-branch-row:has(.oe-ranking-context[hidden]) { background:rgba(100,116,139,.05); border-radius:8px; padding-inline:14px; margin-block:6px; }
.oe-branch-row.is-top .oe-rank { color:#1D4ED8; font-weight:600; }
html[data-theme='dark'] .oe-branch-row.is-top { background:#14283F; border-color:#385A80; }
html[data-theme='dark'] .oe-branch-row.is-top .oe-rank { color:#93C5FD; }
.oe-branch-row { display:grid; grid-template-columns:58px minmax(0,1fr) auto; gap:12px 16px; align-items:center; padding:20px 0; border-bottom:1px solid var(--border); }
.oe-ranking-name { display:flex; flex-wrap:wrap; align-items:center; gap:6px 10px; font-size:15px; font-weight:500; }
.oe-ranking-name > small { flex-basis:100%; font-size:12px; font-weight:400; color:var(--ink-muted); }
.oe-top-label { font-size:11px; padding:2px 8px; border:1px solid var(--border); border-radius:6px; }
.oe-top-label[hidden],.oe-horizontal-track[hidden],.oe-ranking-context[hidden] { display:none !important; }
.oe-ranking-metric { grid-column:3; grid-row:1; text-align:right; display:grid; gap:4px; }
.oe-ranking-metric > small { font-size:12px; color:var(--ink-muted); }
.oe-ranking-value { font-size:22px; font-weight:600; font-variant-numeric:tabular-nums; }
.oe-branch-bar { grid-column:2 / -1; grid-row:2; }
.oe-ranking-bar-wrap { display:grid; gap:6px; width:100%; }
.oe-ranking-bar-wrap > small { font-size:12px; color:var(--ink-muted); }
.oe-ranking-bar-wrap .oe-horizontal-track { width:100%; height:8px; }
.oe-horizontal-track.is-stacked { display:flex; }
.oe-remaining-fill { display:block; height:100%; background:var(--border); }
.oe-remaining-fill[hidden] { display:none; }
.oe-ranking-context { grid-column:2 / -1; grid-row:3; display:flex; flex-wrap:wrap; gap:16px 32px; padding-top:4px; }
.oe-ranking-detail { display:grid; gap:4px; font-size:14px; font-variant-numeric:tabular-nums; }
.oe-ranking-detail small { color:var(--ink-muted); font-size:12px; }
.oe-branch-row:has(.oe-ranking-context[hidden]) .oe-ranking-metric { display:none; }
.oe-branch-row:has(.oe-ranking-context[hidden]) .oe-branch-bar { grid-column:3; grid-row:1; }
.oe-ranking-scope { font-size:12px !important; }
@media(max-width:600px) {
 .oe-branch-row { grid-template-columns:48px minmax(0,1fr); gap:10px; padding:18px 0; }
 .oe-ranking-metric { grid-column:2; grid-row:2; text-align:left; }
 .oe-branch-bar { grid-column:2; grid-row:3; }
 .oe-ranking-context { grid-column:2; grid-row:4; gap:12px 20px; }
 .oe-ranking-value { font-size:20px; }
 .oe-branch-row:has(.oe-ranking-context[hidden]) .oe-branch-bar { grid-column:2; grid-row:2; }
}
.oe-branch-row:last-child { border-bottom:0; }
.oe-branch-heading { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px 16px; margin-bottom:12px; }
.oe-branch-heading strong { font-size:20px; font-weight:500; white-space:nowrap; }
.oe-rank { display:inline-block; margin-right:8px; color:var(--ink-muted); font-variant-numeric:tabular-nums; }
.oe-branch-bar { display:flex; align-items:center; }
.oe-horizontal-track { flex:1; height:14px; background:var(--surface-muted); border-radius:6px; overflow:hidden; }
.oe-horizontal-fill { display:block; height:100%; background:linear-gradient(to right,#1B4332,#2E6B50); border-radius:6px; }
html[data-theme='dark'] .oe-horizontal-fill { background:linear-gradient(to right,#047857,#10B981); }
.oe-branch-context { display:flex; flex-wrap:wrap; gap:8px 20px; color:var(--ink-muted); font-size:12px; margin-top:8px; }
.oe-branch-footnote { margin:0; padding:0 20px 20px; font-size:12px; color:var(--ink-muted); }
.oe-rank-label { display:grid; gap:6px; font-size:12px; min-width:230px; }
.oe-compare-select > select { appearance:none; padding:0 40px 0 36px; border:0; background:transparent; cursor:pointer; }
.oe-compare-select > i.bi-chevron-down { position:absolute; right:12px; top:50%; transform:translateY(-50%); pointer-events:none; }
.oe-compare-select.is-open > i.bi-chevron-down { transform:translateY(-50%) rotate(180deg); }
.oe-detail-summary { display:flex; justify-content:space-between; gap:16px; padding:16px 0; border-bottom:1px solid var(--border); }
.oe-detail-summary > div { display:grid; gap:4px; }
.oe-detail-summary .oe-result-count { margin:0; }
.oe-detail-summary strong { font-size:18px; font-weight:500; }
html[data-theme] body.panel-shell-body.owner-ui .oe-detail-summary small { color:var(--ink-muted) !important; font-size:12px; }
.oe-dialog[open]:not(.oe-date-dialog) { display:grid; grid-template-rows:auto auto minmax(0,1fr) auto; height:auto; max-height:min(760px,calc(100dvh - 48px)); overflow:hidden; }
.oe-dialog-results { overflow:auto; overscroll-behavior:contain; }
.oe-dialog footer { flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
.oe-pagination { padding-top:0; }
.oe-button:disabled { opacity:.5; cursor:default; }
.oe .oe-kpi { cursor:default; }
.oe .oe-kpi:hover { background:var(--card); border-color:var(--border); }
.oe-kpi-footer { flex-wrap:wrap; }
.oe-kpi-footer .oe-button { min-height:32px; padding:0; border:0; background:transparent; font-weight:500; color:var(--ink); }
.oe-kpi-footer > span { max-width:70%; line-height:1.6; }
html[data-theme] body.panel-shell-body.owner-ui .oe-summary-dialog[open] { display:flex; flex-direction:column; position:fixed; inset:0 0 0 auto; margin:0; width:min(560px,100vw); height:100dvh; max-height:100dvh; max-width:100vw; border-radius:20px 0 0 20px; overflow:hidden; box-shadow:-16px 0 48px rgba(0,0,0,.18); animation:oeSummaryEnter .2s ease-out; }
@keyframes oeSummaryEnter { from { transform:translateX(32px); opacity:0; } to { transform:translateX(0); opacity:1; } }
@media(prefers-reduced-motion:reduce) { html[data-theme] body.panel-shell-body.owner-ui .oe-summary-dialog[open] { animation:none; } }
.oe-dialog.oe-summary-dialog header { background:var(--surface-muted); padding:24px; }
.oe-summary-dialog h2 { font-size:20px; line-height:1.4; }
.oe-dialog.oe-summary-dialog footer { margin-top:auto; padding:20px 24px; background:var(--surface-muted); }
.oe-summary-dialog footer .oe-primary { flex:1; white-space:normal; text-align:center; }
.oe-dialog.oe-summary-dialog footer { display:grid; grid-template-columns:1fr; }
.oe-summary-dialog footer [data-close-details] { order:2; background:transparent; border:0; }
.oe-summary-explanation { margin:0 0 20px; font-size:14px; line-height:1.7; }
.oe-summary-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin:20px 0; }
.oe-summary-stat { padding:16px; border:1px solid var(--border); border-radius:12px; background:var(--surface-muted); }
html[data-theme] body.panel-shell-body.owner-ui .oe-summary-dialog .oe-summary-stat > small { display:block; font-size:12px; color:var(--ink) !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe-summary-dialog footer .oe-primary { color:#fff !important; background:#1B4332 !important; }
.oe-summary-stat > strong { display:block; margin-top:8px; font-size:22px; line-height:1.4; font-weight:500; font-variant-numeric:tabular-nums; }
.oe-summary-dialog .oe-summary-note { line-height:1.7; padding:16px; border-radius:12px; }
.oe .oe-kpi { position:relative; border-radius:16px; cursor:pointer; }
.oe-kpi-action { position:absolute; inset:0; z-index:1; width:100%; height:100%; padding:0; border:0; border-radius:inherit; background:transparent !important; cursor:pointer; }
.oe .oe-kpi:hover { background:var(--surface-muted); border-color:var(--brand); }
.oe .oe-kpi:focus-within { outline:2px solid var(--brand); outline-offset:3px; }
.oe-kpi-top > i { display:flex; align-items:center; justify-content:center; width:40px; height:40px; background:var(--surface-muted); border:1px solid var(--border); border-radius:10px; font-size:18px; }
@media(max-width:600px) { .oe-executive-banner { padding:20px; } .oe-header { flex-direction:column; align-items:flex-start; } .oe-header-actions { justify-content:flex-start; } .oe-branch-chart { grid-template-columns:1fr; padding:20px; } html[data-theme] body.panel-shell-body.owner-ui .oe-summary-dialog[open] { border-radius:0; } .oe-summary-stats { grid-template-columns:1fr; } .oe-dialog.oe-summary-dialog header { padding:20px; } }

.oe-summary-body { padding:24px; overflow:auto; min-height:0; flex:0 1 auto; }
.oe-summary-dialog > :is(header,footer) { flex-shrink:0; }
.oe-drawer-scope { margin-bottom:28px; }
.oe-drawer-scope p { margin:0; font-size:13px; line-height:1.8; white-space:pre-line; color:var(--ink); }
.oe-summary-section-title { display:flex; align-items:center; gap:8px; margin:0 0 14px; padding-bottom:12px; border-bottom:1px solid var(--border); font-size:13px; font-weight:500; color:var(--ink); }
.oe-summary-record-count { color:var(--ink-muted); font-size:12px; margin:12px 0 24px; }
.oe-summary-label { font-size:13px; color:var(--ink-muted); }
.oe-summary-total { display:block; margin:8px 0 24px; overflow-wrap:anywhere; font-size:32px; font-weight:600; line-height:1.25; font-variant-numeric:tabular-nums; }
.oe-summary-secondary { display:flex; justify-content:space-between; gap:12px; padding:12px 0; border-top:1px solid var(--border); font-size:13px; }
.oe-summary-note { display:flex; align-items:flex-start; gap:10px; padding:12px; margin-top:20px; background:var(--surface-muted); border:1px solid var(--border); border-radius:8px; color:var(--ink-muted); font-size:12px; }
.oe-dialog.oe-summary-dialog footer { justify-content:flex-end; }
.oe-summary-heading { display:flex; align-items:center; gap:8px; margin-top:6px; }
.oe-summary-heading .oe-title-icon { margin-right:0; flex-shrink:0; }
.oe-records-page { padding:0 20px 20px; }
.owner-collections-filters { display:grid; grid-template-columns:minmax(180px,1.5fr) minmax(180px,1fr) repeat(2,minmax(140px,1fr)) auto auto; gap:12px; align-items:end; padding:16px; }
.owner-collections-filters label { display:grid; gap:6px; font-size:12px; min-width:0; }
.owner-collections-filters :is(input,select) { width:100%; }
html[data-theme] body.panel-shell-body.owner-ui .owner-collections-filters label { color:var(--ink-muted) !important; }
html[data-theme] body.panel-shell-body.owner-ui:has(.owner-collections-page) .panel-title-surface p { color:var(--ink-muted) !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe-records-page tbody tr { background:var(--card) !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe-records-page td { background:transparent !important; }
html[data-theme] body.panel-shell-body.owner-ui .owner-collections-page .btn-outline { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px; background:var(--surface-muted) !important; border:1px solid var(--border) !important; border-radius:8px; color:var(--ink) !important; padding:8px 12px; }
html[data-theme] body.panel-shell-body.owner-ui .owner-collections-page .btn-primary { min-height:44px; background:#1B4332 !important; color:#fff !important; border-radius:8px; padding:8px 16px; }
@media(max-width:1100px) { .owner-collections-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:600px) { .owner-collections-filters { grid-template-columns:minmax(0,1fr); }.owner-collections-filters label { width:100%; } }
.oe-summary-dialog [hidden] { display:none !important; }
@media(max-width:600px) { .oe-summary-body { padding:20px; }.oe-summary-total { font-size:28px; }.oe-dialog.oe-summary-dialog footer { flex-wrap:wrap; } }
.oe-dialog-search .oe-search-hint,.oe-date-help { font-size:12px; color:var(--ink-muted); margin:8px 0 0; }
.oe-date-error { font-size:13px; margin:0; }
html[data-theme] body.panel-shell-body.owner-ui .oe-date-dialog .oe-date-error { color:#B91C1C !important; }
html[data-theme='dark'] body.panel-shell-body.owner-ui .oe-date-dialog .oe-date-error { color:#FCA5A5 !important; }
.oe-dialog-search > div { flex-wrap:wrap; }
.oe-dialog-search input { flex:1 1 180px; width:auto; }
@media(max-width:600px) { .oe-chart-frame { margin-inline:16px; grid-template-columns:18px 60px minmax(0,1fr); }.oe-branch-chart { padding:16px; }.oe-branch-heading { align-items:flex-start; }.oe-branch-heading > div { max-width:100%; }.oe-detail-summary { flex-wrap:wrap; }.oe-dialog footer { padding:12px; }.oe-pagination { gap:8px; }.oe-dialog-search { padding:12px; } }
.oe-bar-fill { display:block; width:100%; max-width:42px; border-radius:6px 6px 0 0; background:linear-gradient(to top,#1B4332,#2E6B50); transition:filter .15s; }
html[data-theme='dark'] .oe-bar-fill { background:linear-gradient(to top,#047857,#10B981); }
.oe-bar-fill.is-zero { background:var(--surface-muted); }
.oe-chart-bar:not(:disabled):is(:hover,:focus-visible) { filter:brightness(1.15); }
.oe-bar-label { display:block; font-size:10px; margin-top:8px; white-space:nowrap; }
.oe-bar-tooltip { position:absolute; bottom:calc(100% + 8px); left:50%; transform:translateX(-50%); padding:6px 8px; border-radius:6px; background:#081D13; color:#fff; font-size:11px; white-space:nowrap; visibility:hidden; pointer-events:none; z-index:2; }
.oe-bar-item:first-child .oe-bar-tooltip { left:0; transform:none; }
.oe-bar-item:last-child .oe-bar-tooltip { left:auto; right:0; transform:none; }
.oe-chart-bar:not(:disabled):is(:hover,:focus-visible) .oe-bar-tooltip { visibility:visible; }
.oe-bar-tooltip > span,.oe-bar-tooltip > strong { display:block; }
.oe-bar-tooltip > strong { margin-top:4px; font-size:13px; font-weight:500; }
html[data-theme] body.panel-shell-body.owner-ui .oe .oe-bar-tooltip .oe-amount { color:#fff !important; }
@media(max-width:600px) { .oe-trend-comparison { grid-template-columns:minmax(0,1fr); margin-inline:16px; gap:0; padding:0 16px; }.oe-trend-comparison > div { padding:16px 0; }.oe-trend-comparison > div + div { border-left:0; border-top:1px solid var(--border); }.oe-chart-legend { padding:14px 16px 0; }.oe-chart-legend [data-comparison-status] { flex-basis:100%; margin-left:0; text-align:left; }.oe-bar-scroll { margin-inline:16px; } }
.oe-trend-data { padding:0 20px 20px; }
.oe-trend-data summary { cursor:pointer; font-size:13px; color:var(--ink-muted); }
.oe-trend-data table { min-width:0; }
.oe-table-scroll { overflow-x:auto; max-width:100%; }
.oe table,.oe-dialog table { width:100%; border-collapse:collapse; font-size:13px; }
.oe table { min-width:820px; }
.oe :is(th,td),.oe-dialog :is(th,td) { padding:14px 16px; border-bottom:1px solid var(--border); text-align:left; vertical-align:middle; font-weight:400; }
.oe th,.oe-dialog th { background:var(--surface-muted); font-weight:500; color:var(--ink-muted); }
.oe td small { display:block; color:var(--ink-muted); margin-top:4px; }
.oe :is(td,th).oe-number,.oe-dialog :is(td,th).oe-number { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
.oe tbody tr:hover { background:var(--surface-muted); }
.oe-risk { display:flex; align-items:center; justify-content:space-between; gap:24px; padding:20px; flex-wrap:wrap; }
.oe-risk-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; font-variant-numeric:tabular-nums; }
.oe-risk-stat { display:grid; gap:6px; }
.oe-risk-stat small { font-size:12px; color:var(--ink-muted); }
.oe-risk-stat .oe-amount { font-size:22px; font-weight:500; }
.oe-risk-summary strong { font-size:22px; font-weight:600; }
.oe-empty { text-align:center; padding:40px 20px; color:var(--ink-muted); }
.oe-empty i { font-size:24px; }.oe-empty p { margin:12px 0 4px; }.oe-empty span { font-size:13px; }
.oe-dialog { width:min(1000px,calc(100vw - 32px)); max-height:calc(100dvh - 48px); padding:0; margin:auto; border:1px solid var(--border); border-radius:16px; background:var(--card); color:var(--ink); font-family:var(--font-body); }
.oe-dialog::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
.oe-dialog header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px; border-bottom:1px solid var(--border); }
.oe-dialog header { position:sticky; top:0; z-index:2; background:var(--card); }
.oe-dialog :is(th,td) { color:var(--ink); }
.oe-dialog tbody tr { background:var(--card) !important; }
.oe-dialog td { background:transparent !important; }
.oe-dialog th { background:var(--surface-muted) !important; }
.oe-icon-button { width:44px; padding:0; flex-shrink:0; }
.oe-dialog-search { padding:16px 20px; border-bottom:1px solid var(--border); }
.oe-dialog-search label { display:block; font-size:12px; margin-bottom:8px; color:var(--ink-muted); }
.oe-dialog-search > div { display:flex; gap:8px; }
.oe-dialog-results { min-height:160px; padding:0 20px 20px; }
.oe-dialog-results table { min-width:680px; }
.oe-dialog footer { display:flex; justify-content:flex-end; padding:16px 20px; border-top:1px solid var(--border); }
.oe-dialog footer { position:sticky; bottom:0; z-index:1; background:var(--card); }
.oe-pagination { display:flex; justify-content:flex-end; align-items:center; gap:12px; padding-top:16px; }
.oe-result-count { color:var(--ink-muted); font-size:12px; margin:16px 0; }
.oe-date-dialog { width:min(440px,calc(100vw - 32px)); }
.oe-date-dialog form { display:grid; gap:16px; padding:20px; }
.oe-date-dialog label { display:grid; gap:6px; font-size:13px; }
body.owner-executive-dialog-open { overflow:hidden; }
body.owner-executive-dialog-open .page-content { overflow:hidden !important; }
.oe :is(a,button,select,input,summary):focus-visible,.oe-dialog :is(a,button,input):focus-visible { outline:2px solid var(--brand); outline-offset:3px; }
/* Component ownership over legacy panel rules that force light text/control colors. */
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) :is(.oe-summary-section-title,.oe-summary-section-title i,.oe-risk-stat small) { color:var(--ink) !important; }
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) :is(h1,h2,td,input,select,.oe-kpi-value,.oe-risk-summary) { color:var(--ink) !important; }
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) :is(p,label,th,.oe-basis,.oe-result-count,.oe-eyebrow,.oe-comparison,.oe-kpi-top,.oe-kpi-footer) { color:var(--ink-muted) !important; }
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) :is(input,select,th) { background:var(--surface-muted) !important; border-color:var(--border) !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe-dialog tbody tr { background:var(--card) !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe-dialog td { background:transparent !important; }
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) h2 { font-family:var(--font-body) !important; font-weight:600 !important; }
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) .oe-button { color:var(--ink) !important; }
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) .oe-primary { color:#fff !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe-header :is(.dashboard-header-date,.dashboard-header-theme) { background:var(--surface-muted) !important; border-color:var(--border) !important; color:var(--ink) !important; }
html[data-theme] body.panel-shell-body.owner-ui :is(.oe,.oe-dialog) :is(.oe-amount,.oe-risk-summary strong) { color:var(--ink) !important; }
@media(max-width:1100px) { .oe-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }.oe-header { flex-wrap:wrap; }.oe-filter-context { flex:1 0 100%; } }
@media(max-width:600px) { .oe { gap:12px; }.oe-header { padding-top:48px!important; }.oe-filter-card { padding:12px; }.oe-filter-card form { flex-direction:column; align-items:stretch; flex-basis:100%; }.oe-filter-card label { width:100%; }.oe-kpis { grid-template-columns:minmax(0,1fr); gap:12px; }.oe-section-head,.oe-risk { padding:16px; }.oe-chart { padding:16px; }.oe-dialog header { padding:16px; }.oe-dialog-results { padding-inline:12px; }.oe-pagination { flex-wrap:wrap; }.oe-segment { width:100%; }.oe-segment button { flex:1; }.oe-header h1 { padding-left:0!important; } }
@media(prefers-reduced-motion:reduce) { .oe-kpi { transition:none; } }
@media(max-width:600px) { html[data-theme] body.panel-shell-body.owner-ui .oe-header { padding-top:48px !important; } }

/* Metric meaning belongs to the value, with theme-specific contrast. */
html[data-theme] body.panel-shell-body.owner-ui .oe [data-kpi="cases"] .oe-kpi-value { color:#2563EB !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe [data-kpi="service"] .oe-kpi-value { color:#4F46E5 !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe [data-kpi="collections"] .oe-kpi-value { color:#15803D !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe [data-kpi="outstanding"] .oe-kpi-value { color:#B45309 !important; }
html[data-theme='dark'] body.panel-shell-body.owner-ui .oe [data-kpi="cases"] .oe-kpi-value { color:#93C5FD !important; }
html[data-theme='dark'] body.panel-shell-body.owner-ui .oe [data-kpi="service"] .oe-kpi-value { color:#A5B4FC !important; }
html[data-theme='dark'] body.panel-shell-body.owner-ui .oe [data-kpi="collections"] .oe-kpi-value { color:#86EFAC !important; }
html[data-theme='dark'] body.panel-shell-body.owner-ui .oe [data-kpi="outstanding"] .oe-kpi-value { color:#FCD34D !important; }
html[data-theme] body.panel-shell-body.owner-ui .oe [data-zero-outstanding] .oe-kpi-value { color:var(--ink) !important; }
@media(max-width:600px) {
 .oe-filter-card form { width:100%; }
 .oe-filter-card form > label, .oe-filter-card form > label:has([name="range"]) { flex:0 0 auto; width:100%; }
 .oe-filter-card form > .oe-actions { align-self:flex-start; }
}
@media(max-width:700px) { .oe-header h1 { font-size:22px !important; } }
</style>
