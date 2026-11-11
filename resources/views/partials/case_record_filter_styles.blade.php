{{-- Case-list presentation only. Toolbar and filter-drawer layout live in
     case_records_layout_styles; shared control styling lives in shared_filter_ui. --}}
<style>
    /* Keep one table surface and center the case status tabs on both worklists. */
    html body :is(.records-page, .master-records-page) :is(.table-system-card, .table-system-list) {
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    html body :is(.records-page, .master-records-page) .table-system-list > .table-system-wrap {
        margin: 0 !important;
        border: 1px solid var(--records-border, var(--border)) !important;
        border-radius: 8px !important;
        box-shadow: none !important;
    }

    html body :is(.records-page, .master-records-page) .case-records-tabs-row {
        box-sizing: border-box;
        width: 100% !important;
        margin: 0 0 12px !important;
        justify-content: center !important;
    }

    html body :is(.records-page, .master-records-page) .case-records-tabs-row:has(> .historical-cases-entry-link) {
        justify-content: space-between !important;
    }

    html body :is(.records-page, .master-records-page) .case-records-tabs {
        margin: 0 !important;
        justify-content: center !important;
        gap: 8px !important;
    }

    @media (min-width: 768px) {
        html body :is(.records-page, .master-records-page) .case-records-tabs {
            width: max-content !important;
            max-width: 100%;
        }
    }

    html body :is(.records-page, .master-records-page) .case-records-tabs-row + .table-system-list {
        box-sizing: border-box;
        width: 100% !important;
        margin-inline: 0 !important;
        padding-inline: 0 !important;
    }

    html body :is(.records-page, .master-records-page) .case-records-tabs-row + .table-system-list > .table-system-wrap {
        box-sizing: border-box;
        width: 100% !important;
        margin-inline: 0 !important;
    }

    html body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab {
        transform: none !important;
        border: 0 !important;
        border-bottom: 2px solid transparent !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    html body :is(.records-page, .master-records-page) .case-records-tabs .table-quick-tab-active {
        border-bottom-color: var(--records-text, var(--brand, #24513e)) !important;
        color: var(--records-text, var(--ink)) !important;
    }

    html body :is(.records-page, .master-records-page) :is(.case-records-tabs-row, .case-records-tabs) {
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
</style>
