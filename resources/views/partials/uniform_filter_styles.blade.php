<style>
    /* Branch directory uses spacing and surfaces instead of nested outlines. */
    html body .branch-management-page .branch-kpi-strip {
        background:transparent !important;
        border:0 !important;
        border-radius:0 !important;
        box-shadow:none !important;
        padding:0 !important;
        gap:.75rem !important;
        margin:0 0 1rem !important;
    }
    html body .branch-management-page :is(
        .table-system-card, .table-system-head, .table-system-toolbar,
        .table-system-list, .table-system-wrap, .table-system-pagination,
        .branch-directory-card-view, .directory-card-view,
        .directory-item-card, .branch-kpi-card
    ) {
        border:0 !important;
        outline:0 !important;
        box-shadow:none !important;
    }
    html body .branch-management-page :is(.directory-item-card, .branch-kpi-card):focus-visible {
        outline:2px solid var(--brand, #24513e) !important;
        outline-offset:3px !important;
    }
    html body .branch-management-page .branch-kpi-strip .branch-kpi-card {
        border:1px solid var(--border, #AEBFA6) !important;
        border-radius:8px !important;
    }
    /* Match the Case Records More Filters surface across management toolbars. */
    html body .uniform-record-filters {
        --uniform-filter-bg:#DCE6D6;
        --uniform-filter-border:#AEBFA6;
        --uniform-filter-text:#263528;
        align-items:end !important;
        gap:10px !important;
    }
    html[data-theme='dark'] body .uniform-record-filters {
        --uniform-filter-bg:var(--surface-muted, #243229);
        --uniform-filter-border:var(--border, #465447);
        --uniform-filter-text:var(--ink, #e7eee4);
    }
    html body .uniform-record-filters :is(input:not([type='hidden']):not([type='checkbox']), select, .pm-readonly-control) {
        box-sizing:border-box !important;
        height:44px !important;
        min-height:44px !important;
        max-height:44px !important;
        width:100% !important;
        min-width:0 !important;
        margin-block:0 !important;
        background-color:var(--uniform-filter-bg) !important;
        color:var(--uniform-filter-text) !important;
        border:1px solid var(--uniform-filter-border) !important;
        border-radius:8px !important;
        box-shadow:none !important;
    }
    html body .uniform-record-filters :is(.table-toolbar-input-wrap, .table-toolbar-select-wrap, .ba-branch-select-wrap, .ba-period-select-wrap, .reports-field-control, .audit-filter-control) {
        box-sizing:border-box !important;
        min-width:0 !important;
        height:44px !important;
        min-height:44px !important;
        background:transparent !important;
        border:0 !important;
        box-shadow:none !important;
    }
    html body .uniform-record-filters :is(input,select):focus-visible {
        outline:2px solid var(--brand, #24513e) !important;
        outline-offset:2px !important;
    }
    html body .uniform-record-filters :is(.table-toolbar-field,.pm-field,.reports-field) { min-width:0 !important; }
    html body .uniform-record-filters .user-page-size select {
        min-width:88px !important; padding-left:12px !important; padding-right:32px !important;
    }
    html body .uniform-record-filters .audit-filter-control:has(select[name='per_page']) {
        min-width:170px !important;
    }
    html body .uniform-record-filters select[name='per_page'] { text-overflow:clip !important; white-space:nowrap; }
    @media(min-width:768px) {
        html body .uniform-record-filters:has(.user-page-size) {
            display:grid !important;
            grid-template-columns:minmax(140px, 1.6fr) repeat(4, minmax(0, 1fr)) 180px !important;
            align-items:center !important;
            width:100% !important;
            min-width:0 !important;
        }
        html body .uniform-record-filters:has(.user-page-size):not(:has(select[name='role'])) {
            grid-template-columns:minmax(140px, 2fr) repeat(2, minmax(0, 1fr)) 180px !important;
        }
        html body .uniform-record-filters:has(.user-page-size) > .table-toolbar-field {
            min-width:0 !important; width:100% !important; max-width:none !important;
        }
        html body .uniform-record-filters:has(.user-page-size) select {
            text-overflow:ellipsis; white-space:nowrap;
        }
        html body .uniform-record-filters .user-page-size {
            display:flex !important; align-items:center !important; gap:8px !important;
        }
        html body .uniform-record-filters .user-page-size .table-toolbar-label {
            display:block !important; white-space:nowrap; margin:0;
        }
        html body .uniform-record-filters .user-page-size select {
            width:88px !important; flex:0 0 88px !important;
        }
    }
    @media(max-width:767px) {
        html body .uniform-record-filters {
            display:flex !important;
            flex-direction:column !important;
            align-items:stretch !important;
            width:100% !important;
            grid-template-columns:minmax(0,1fr) !important;
        }
        html body .uniform-record-filters > :is(.table-toolbar-field,.pm-field,.audit-filter-control,.ba-branch-form,.ba-filter-group,.service-filter-actions),
        html body .uniform-record-filters .service-filter-actions > .table-toolbar-field {
            width:100% !important;
            min-width:0 !important;
            max-width:none !important;
            flex:0 0 auto !important;
            margin:0 !important;
        }
        html body .uniform-record-filters :is(.ba-seg,.ba-period-form,.ba-branch-select-wrap,.ba-period-select-wrap) { width:100% !important; min-width:0 !important; }
        html body .uniform-record-filters .service-filter-actions { display:flex !important; flex-direction:column !important; align-items:stretch !important; gap:10px !important; }
    }
</style>
<style>
    /* Text inputs can match :focus-visible after a click; track input method explicitly. */
    html[data-filter-input='pointer'] body :is(.uniform-record-filters, .case-record-filters) :is(input, select, button):focus,
    html[data-filter-input='pointer'] body :is(.uniform-record-filters, .case-record-filters) :is(.table-toolbar-input-wrap, .table-toolbar-select-wrap, .case-compact-search-control, .case-compact-branch, .case-compact-seg, .pm-field, .audit-filter-control):focus-within {
        outline:none !important;
        box-shadow:none !important;
        --tw-ring-shadow:0 0 #0000 !important;
        --tw-ring-offset-shadow:0 0 #0000 !important;
    }
</style>
<script>
    (() => {
        const root = document.documentElement;
        if (root.dataset.filterInputReady) return;
        root.dataset.filterInputReady = '1';
        document.addEventListener('pointerdown', () => {
            root.dataset.filterInput = 'pointer';
        }, true);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Tab' || event.key.startsWith('Arrow')) {
                root.dataset.filterInput = 'keyboard';
            }
        }, true);
    })();
</script>
