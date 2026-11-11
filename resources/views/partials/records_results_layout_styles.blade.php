<style>
    /* Short and empty result sets reserve the same space. Long lists grow naturally. */
    html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page,.payment-monitoring-page) {
        --records-results-min-height: clamp(20rem, 40vh, 30rem);
    }
    html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .table-system-wrap {
        min-height: var(--records-results-min-height) !important;
        background: var(--records-card, var(--card)) !important;
        container-type: inline-size;
    }
    html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .table-system-wrap:has(.table-system-empty) > table {
        height: var(--records-results-min-height);
    }
    html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) td.table-system-empty {
        padding: 0 !important;
        vertical-align: middle;
        background: transparent !important;
    }
    html[data-theme] body.panel-shell-body .records-empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        padding: 24px;
        text-align: center;
        color: var(--records-muted, var(--ink-muted));
        font-size: 15px;
        line-height: 1.6;
        font-weight: 400 !important;
        background: transparent !important;
    }
    html[data-theme] body.panel-shell-body td.table-system-empty > .records-empty-state {
        position: sticky;
        left: 0;
        width: min(100%, 100cqw);
        min-height: 0;
    }
    html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page) .table-system-pagination {
        display: flex;
        align-items: center;
        box-sizing: border-box;
        min-height: 64px;
        position: static !important;
        margin-top: 12px !important;
        padding: 12px 16px !important;
        background: var(--records-card, var(--card)) !important;
        border: 1px solid var(--records-border, var(--border)) !important;
        border-radius: 6px !important;
    }
    html[data-theme] body.panel-shell-body .payment-monitoring-page.pm-page .pm-panel.ops-list-panel {
        background: var(--card) !important;
        border-radius: 6px !important;
    }
    html[data-theme] body.panel-shell-body .payment-monitoring-page.pm-page .pm-records-body.ops-list-body {
        min-height: var(--records-results-min-height);
        background: var(--card) !important;
    }
    html[data-theme] body.panel-shell-body .payment-monitoring-page.pm-page :is(.pm-row-list,.pm-trans-list):has(> .records-empty-state) {
        display: flex;
        min-height: var(--records-results-min-height);
        background: var(--card) !important;
    }
    html[data-theme] body.panel-shell-body .payment-monitoring-page.pm-page :is(.pm-row-list,.pm-trans-list) > .records-empty-state {
        flex: 1;
        min-height: var(--records-results-min-height);
        border: 0;
        border-radius: 0;
    }
    html[data-theme] body.panel-shell-body .payment-monitoring-page.pm-page .pm-panel.ops-list-panel > .pm-foot {
        position: static;
        margin-top: auto;
        padding: 12px 16px;
        min-height: 64px;
        box-sizing: border-box;
        background: var(--card) !important;
        border-top: 1px solid var(--border) !important;
    }
    html[data-theme] body.panel-shell-body :is(.table-system-pagination,.pm-foot) .table-paginator {
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
        min-width: 0;
        gap: 12px;
        flex-wrap: wrap;
    }
    @media(max-width:767px) {
        html[data-theme] body.panel-shell-body :is(.records-page,.master-records-page,.owner-master-records-page,.payment-monitoring-page) { --records-results-min-height: 18rem; }
    }
    @media print {
        html[data-theme] body.panel-shell-body :is(.table-system-wrap,.pm-records-body.ops-list-body) { min-height: 0 !important; }
        html[data-theme] body.panel-shell-body .table-system-wrap:has(.table-system-empty) > table { height: auto !important; }
        html[data-theme] body.panel-shell-body .records-empty-state { min-height: 0 !important; }
    }
</style>
