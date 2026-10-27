{{-- Shared layout ownership for the two case worklists, independent of role palette. --}}
<style>
    /* Keep record values easy to scan without making every row bold. */
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody :is(.table-primary, .table-financial-total, .records-case-code) {
        font-weight: 500 !important;
        line-height: 1.45;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody .table-secondary {
        font-size: 13px !important;
        font-weight: 400 !important;
        line-height: 1.5;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-table tbody .status-badge {
        font-weight: 500 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) {
        overflow: visible !important;
    }
    /* Sticky filters must use the page scroller, not a clipped card ancestor. */
    html[data-theme] body.panel-shell-body .master-records-page > .table-system-card {
        overflow: visible !important;
    }
    /* Focus belongs to the composite field, not a second box around its select. */
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-record-filters .case-compact-seg :is(.case-compact-date-select, .case-compact-sort-select):is(:focus, :focus-visible) {
        outline: none !important;
        box-shadow: none !important;
        border: 0 !important;
        background: transparent !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .case-compact-seg:has(select:focus-visible) {
        outline: 2px solid var(--brand) !important;
        outline-offset: 2px !important;
    }
    html[data-theme] body.panel-shell-body .records-page > .case-records-top-wrapper.is-stuck,
    html[data-theme] body.panel-shell-body .master-records-page .admin-table-toolbar.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .payment-monitoring-page .pm-toolbar-shell.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .directory-page .table-system-toolbar.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .service-management-hub .service-hub-toolbar.is-stuck,
    html[data-theme] body.panel-shell-body:is(.branch-admin-ui,.system-admin-ui) .audit-ref-page .admin-table-toolbar.is-stuck {
        box-shadow: 0 8px 24px rgba(8, 29, 19, .22), 0 0 0 1px var(--border-strong, var(--border)) !important;
        border-radius: 12px;
    }
    html[data-theme] body.panel-shell-body .records-page > .case-records-top-wrapper,
    html[data-theme] body.panel-shell-body .master-records-page .admin-table-toolbar {
        position: sticky !important;
        top: 16px !important;
        z-index: 30;
        align-self: start;
        overflow: visible !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-list {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        overflow: visible !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-wrap {
        /* Contain the global sticky header's z-index below the filter toolbar. */
        position: relative;
        z-index: 0;
        isolation: isolate;
        border: 1px solid var(--records-border, var(--border)) !important;
        border-radius: 12px !important;
        overflow-x: auto !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-system-pagination {
        position: static !important;
        margin-top: 16px !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    html[data-theme] body.panel-shell-body :is(.records-page, .master-records-page) .table-paginator {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }
    @media print {
        html[data-theme] body.panel-shell-body .records-page > .case-records-top-wrapper,
        html[data-theme] body.panel-shell-body .master-records-page .admin-table-toolbar { position: static !important; }
    }
</style>
<script>
    (() => {
        const scrollRoot = document.querySelector('.page-content');
        if (!scrollRoot) return;
        let scheduled = false;
        const update = () => {
            scheduled = false;
            const toolbar = document.querySelector('.records-page > .case-records-top-wrapper, .master-records-page .admin-table-toolbar, .payment-monitoring-page .pm-toolbar-shell, .directory-page .table-system-toolbar, .service-management-hub .service-hub-toolbar, .audit-ref-page .admin-table-toolbar');
            if (!toolbar) return;
            const top = toolbar.getBoundingClientRect().top - scrollRoot.getBoundingClientRect().top;
            toolbar.classList.toggle('is-stuck', scrollRoot.scrollTop > 0 && top <= 17);
        };
        const schedule = () => {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(update);
        };
        scrollRoot.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule, { passive: true });
        // Staff filters replace their toolbar through AJAX; update the replacement too.
        new MutationObserver(schedule).observe(scrollRoot, { childList: true, subtree: true });
        update();
    })();
</script>
