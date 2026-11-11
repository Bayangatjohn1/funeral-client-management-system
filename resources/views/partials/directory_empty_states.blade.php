<style>
    html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) {
        --directory-results-height: clamp(20rem,40vh,30rem);
    }
    html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) [data-record-results="cards"] > .grid {
        min-height: var(--directory-results-height);
        align-content: start;
    }
    html[data-theme] body.panel-shell-body .directory-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        box-sizing: border-box;
        min-height: var(--directory-results-height);
        padding: 24px;
        text-align: center;
        border: 1px solid var(--directory-border,var(--border));
        border-radius: 6px;
        background: var(--directory-card,var(--card));
        color: var(--ink);
    }
    html[data-theme] body.panel-shell-body .directory-empty-icon {
        display: grid; place-items: center; width: 56px; height: 56px;
        border-radius: 50%; background: var(--surface-muted); color: var(--ink-muted);
    }
    html[data-theme] body.panel-shell-body .directory-empty-state h3 { margin:0; font-size:15px; font-weight:600; color:var(--ink); }
    html[data-theme] body.panel-shell-body .directory-empty-state p { margin:4px 0 0; max-width:32rem; font-size:13px; font-weight:400; color:var(--ink-muted); }
    html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) .table-system-wrap {
        min-height: var(--directory-results-height); container-type: inline-size;
        background: var(--directory-card,var(--card)); box-sizing:border-box;
    }
    html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) .table-system-wrap:has(td.table-system-empty) { height:var(--directory-results-height); }
    html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) .table-system-wrap:has(td.table-system-empty) > table { height:100%; }
    html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) td.table-system-empty { padding:0 !important; vertical-align:middle; }
    html[data-theme] body.panel-shell-body .directory-table-empty {
        position:sticky; left:0; display:flex; align-items:center; justify-content:center;
        width:min(100%,100cqw); box-sizing:border-box; padding:24px;
        font-size:15px; font-weight:400; text-align:center; color:var(--ink-muted);
    }
    html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) .table-system-pagination {
        min-height:64px; box-sizing:border-box; margin-top:12px; padding:12px 16px;
        display:flex; align-items:center;
    }
    @media(max-width:767px) {
        html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) { --directory-results-height:18rem; }
    }
    @media print {
        html[data-theme] body.panel-shell-body .directory-empty-state,
        html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) .table-system-wrap { min-height:0; height:auto !important; }
        html[data-theme] body.panel-shell-body :is([data-record-results-page="branches"],[data-record-results-page="users"]) table:has(td.table-system-empty) { height:auto; }
    }
</style>
