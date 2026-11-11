<style>
    html[data-theme] body.panel-shell-body .page-content .staff-clients-page form.worklist-toolbar {
        grid-template-columns: minmax(0, 1fr) 220px 220px !important;
    }
    html[data-theme] body.panel-shell-body .page-content .staff-clients-page {
        box-sizing: border-box;
        height: auto;
        overflow: visible;
        padding-inline: var(--panel-content-inline, 20px);
    }
    html[data-theme] body.panel-shell-body .page-content .staff-clients-page > .table-system-card {
        flex: 0 0 auto;
        gap: 0;
        min-width: 0;
        overflow: visible;
    }
    html[data-theme] body.panel-shell-body .page-content .staff-clients-page .table-system-list {
        border: 0 !important;
    }
    html[data-theme] body.panel-shell-body.record-surface-ui .page-content .staff-clients-page .table-system-toolbar {
        border-bottom: 1px solid var(--rs-divider) !important;
        border-radius: 12px 12px 0 0 !important;
    }
    html[data-theme] body.panel-shell-body .page-content .staff-clients-page .table-system-wrap {
        flex: 0 0 auto;
        height: auto;
        max-height: none;
        width: auto;
        margin: 0 var(--panel-content-inline, 16px);
        padding: 0;
        isolation: isolate;
    }
    html[data-theme] body.panel-shell-body .page-content .staff-clients-page .table-system-table {
        min-width: 46rem;
        border: 0;
        border-radius: 0;
    }
    .staff-clients-page .table-system-table thead :is(tr, th) { background-image: none; }
    .staff-clients-page .table-system-table thead th { padding: 16px 14px; text-transform: uppercase; }
    .staff-clients-page .table-system-table .table-col-actions { width: 112px; min-width: 112px; text-align: right; }
    .staff-clients-page .table-system-table .table-primary { font-size: 15px; font-weight: 400; line-height: 1.45; }
    .staff-clients-page .table-system-table .table-secondary { font-size: 13px; line-height: 1.5; }
    .staff-clients-page .staff-records-mono { font-family: 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }
    .staff-clients-page .table-system-table :is(.table-primary, .table-secondary) { white-space: normal; overflow-wrap: anywhere; }
    .staff-clients-page .client-directory-address { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
    .staff-clients-page .client-directory-empty { display: grid; justify-items: center; gap: 6px; padding: 30px 18px; color: var(--ink-muted); text-align: center; }
    .staff-clients-page .client-directory-empty i { font-size: 1.5rem; color: var(--ink); }
    .staff-clients-page .client-directory-empty strong { color: var(--ink); }
    html[data-theme] body.panel-shell-body .page-content .staff-clients-page .table-system-empty { height: calc(clamp(20rem, 40vh, 30rem) - 48px); vertical-align: middle; }

    .client-directory-dialog { position: fixed; inset: 0; margin: auto; padding: 0; width: min(54rem, calc(100% - 24px)); max-width: none; max-height: calc(100dvh - 24px); overflow: hidden; border: 1px solid var(--filter-ui-border); border-radius: 16px; background: var(--card); color: var(--ink); }
    .client-directory-dialog[open] { display: flex; flex-direction: column; }
    .client-directory-dialog::backdrop { background: var(--modal-scrim, rgb(0 0 0 / .48)); }
    .client-directory-dialog > header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--filter-ui-border); }
    .client-directory-dialog h2#clientModalTitle { margin: 0; font-size: 1.125rem; font-weight: 700; }
    .client-directory-dialog #clientModalClose { display: grid; place-items: center; width: 44px; height: 44px; padding: 0; border: 0; border-radius: 8px; }
    .client-directory-dialog #clientModalClose:hover { background: var(--worklist-hover) !important; }
    .client-directory-dialog #clientModalContent { min-height: 0; overflow: auto; overscroll-behavior: contain; padding: clamp(12px, 2.4vw, 24px); }
    .client-profile-page { max-width: 58rem; margin: 0 auto; padding: clamp(12px, 2.4vw, 24px); background: var(--card); border: 1px solid var(--filter-ui-border); border-radius: 14px; }
    .client-profile-surface #clientViewContent { display: grid; gap: 18px; padding: 0; background: transparent !important; color: var(--ink); }
    .client-profile-surface #clientViewContent > * { margin: 0; }
    @media screen { .client-profile-surface :is(.client-print-brand, .client-print-signature) { display: none; } }
    .client-profile-surface .client-profile-identity { align-items: flex-start; flex-wrap: wrap; gap: 12px; padding: 14px 16px; background: var(--worklist-search); border: 0; border-radius: 10px; }
    .client-profile-surface .client-profile-identity h2 { font-size: 1.25rem; line-height: 1.35; text-transform: none; overflow-wrap: anywhere; }
    .client-profile-surface #clientViewContent :is(.text-black, .font-bold) { color: var(--ink) !important; }
    .client-profile-surface #clientViewContent :is(.text-gray-500, .text-gray-600, .text-slate-500) { color: var(--ink-muted) !important; }
    .client-profile-surface #clientViewContent h3 { margin-bottom: 8px; padding-bottom: 7px; border-color: var(--filter-ui-border); font-size: .875rem; text-transform: none; }
    .client-profile-surface .client-profile-contacts { column-gap: 20px; }
    .client-profile-surface .client-profile-contacts > div { gap: 8px; min-width: 0; overflow-wrap: anywhere; }
    .client-profile-surface .client-profile-table { overflow-x: auto; border-color: var(--filter-ui-border); }
    .client-profile-surface .client-profile-table table { min-width: 36rem; }
    html[data-theme] body.panel-shell-body .client-profile-surface .client-profile-table :is(table, thead, thead tr, th) { background: transparent !important; color: var(--ink) !important; }
    .client-profile-surface .client-profile-table :is(th, td) { border-bottom: 1px solid var(--filter-ui-border); }
    .client-profile-surface .client-profile-table tbody tr:last-child td { border-bottom: 0; }
    .client-profile-case-link { color: var(--ink); font-weight: 600; text-decoration: underline; text-underline-offset: 3px; white-space: nowrap; }
    .client-profile-case-link:focus-visible { outline: 2px solid var(--filter-ui-focus); outline-offset: 3px; }
    html[data-theme] body.panel-shell-body .client-profile-surface #printClientBtn { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 10px 16px; border: 0; border-radius: 8px; background: var(--record-search-action) !important; color: var(--record-search-action-text) !important; box-shadow: none; font-size: .875rem; letter-spacing: normal; text-transform: none; }
    html[data-theme] body.panel-shell-body .client-profile-surface #printClientBtn:hover { background: var(--record-search-action-hover) !important; }

    @media (max-width: 1100px) {
        html[data-theme] body.panel-shell-body .page-content .staff-clients-page form.worklist-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    }
    @media (max-width: 600px) {
        .staff-clients-page .worklist-filter-panel { left: 0 !important; right: auto; }
        .client-profile-surface .client-profile-contacts { grid-template-columns: 1fr; }
        .client-profile-surface .client-profile-contacts > div { grid-column: auto; }
    }
</style>
