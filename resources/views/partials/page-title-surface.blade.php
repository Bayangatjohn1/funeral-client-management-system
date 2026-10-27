<style>
/* One shared owner for panel title surfaces. Legacy role/page layers use important
   declarations; this explicit component scope replaces their surface treatment. */
@media screen {
    html[data-theme] body.panel-shell-body .page-content > :is(.sa,.eb-shell,.admin-dashboard-shell,.staff-dashboard-v2,.retention-page,.service-management-hub,.ba-shell,.reports-page) {
        padding-inline: var(--panel-content-inline,20px) !important;
        padding-top: 12px !important;
        box-sizing: border-box;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface {
        --title-surface: #F4F8F5;
        --title-border: #AEC7B7;
        --title-ink: #102D21;
        background: var(--title-surface) !important;
        border: 1px solid var(--title-border) !important;
        border-radius: 16px !important;
        box-shadow: none !important;
        padding: 16px 20px !important;
        color: var(--title-ink);
        box-sizing: border-box;
        min-width: 0;
        min-height: 0 !important;
        height: auto !important;
        gap: 12px;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__leading {
        position:relative;
        flex:1 1 auto;
        justify-content:center;
        gap:12px;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__copy {
        width:100%;
        max-width:760px;
        text-align:center;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__copy h1 {
        justify-content:center;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__leading > :is(.case-back-icon,.owner-case-back,.owner-records-back) {
        position:absolute;
        left:0;
        top:50%;
        transform:translateY(-50%);
    }
    html[data-theme] body.panel-shell-body .panel-title-surface :is(h1 + p,.panel-page-header__copy > p,.reports-module-subtitle,.staff-dashboard-description,.sa-heading p,.intake-page-heading-copy p,.ec-hero__subtitle,.retention-hero-copy p,.service-reference-header p) { font-size:13px !important; line-height:1.5; margin:4px 0 0 !important; }
    html[data-theme] body.panel-shell-body .panel-title-surface .page-title-icon i { display:inline-flex; align-items:center; justify-content:center; line-height:1; }
    html[data-theme] body.panel-shell-body .panel-title-surface .page-title-icon--inline { font-size:22px; vertical-align:middle; margin-top:0; }
    html[data-theme='dark'] body.panel-shell-body .panel-title-surface {
        --title-surface: #112218;
        --title-border: #29523D;
        --title-ink: #E5F2E9;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface :is(h1,.ec-hero__title) {
        font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif !important;
        font-size: 26px !important;
        font-weight: 600 !important;
        line-height: 1.25 !important;
        letter-spacing: -.025em;
        color: var(--title-ink) !important;
        margin-block: 0;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface.panel-page-header {
        margin: 12px var(--panel-content-inline, 20px) 12px;
        width: auto;
        align-items: center;
        display:grid !important;
        grid-template-columns:minmax(0,1fr) minmax(0,760px) minmax(0,1fr);
        position:relative;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface.panel-page-header > .panel-page-header__leading {
        grid-column:2;
        position:static;
        width:100%;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface.panel-page-header > .panel-page-header__actions {
        grid-column:3;
        justify-self:end;
    }
    html[data-theme] body.panel-shell-body .centered-page-header :is(.topbar-leading,.service-reference-heading,.retention-hero-copy,.intake-brand,.reports-module-title-block,.ec-hero__left),
    html[data-theme] body.panel-shell-body .centered-page-header.ops-page-header > :first-child,
    html[data-theme] body.panel-shell-body .centered-page-header.contract-preview-head > :first-child {
        flex:1 1 auto;
        text-align:center;
    }
    html[data-theme] body.panel-shell-body .centered-page-header :is(.topbar-leading,.service-reference-heading,.retention-hero-copy,.intake-brand) {
        justify-content:center;
    }
    html[data-theme] body.panel-shell-body .centered-page-header .topbar-heading {
        text-align:center;
    }
    html[data-theme] body.panel-shell-body .centered-page-header .service-reference-title {
        justify-content:center;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface.topbar.centered-page-header,
    html[data-theme] body.panel-shell-body .service-reference-header.centered-page-header,
    html[data-theme] body.panel-shell-body .retention-hero.centered-page-header,
    html[data-theme] body.panel-shell-body .ops-page-header.centered-page-header,
    html[data-theme] body.panel-shell-body .contract-preview-head.centered-page-header {
        display:grid !important;
        grid-template-columns:minmax(0,1fr) minmax(0,760px) minmax(0,1fr);
        align-items:center;
        position:relative;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface.topbar.centered-page-header > .topbar-leading,
    html[data-theme] body.panel-shell-body .service-reference-header.centered-page-header > .service-reference-heading,
    html[data-theme] body.panel-shell-body .retention-hero.centered-page-header > .retention-hero-copy,
    html[data-theme] body.panel-shell-body .ops-page-header.centered-page-header > :first-child,
    html[data-theme] body.panel-shell-body .contract-preview-head.centered-page-header > :first-child {
        grid-column:2;
        width:100%;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface.topbar.centered-page-header > .topbar-actions,
    html[data-theme] body.panel-shell-body .service-reference-header.centered-page-header > :is(button,.btn),
    html[data-theme] body.panel-shell-body .retention-hero.centered-page-header > .policy-chip,
    html[data-theme] body.panel-shell-body .ops-page-header.centered-page-header > :last-child,
    html[data-theme] body.panel-shell-body .contract-preview-head.centered-page-header > :last-child {
        grid-column:3;
        justify-self:end;
    }
    html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header {
        display:grid !important;
        grid-template-columns:minmax(0,1fr) minmax(0,760px) minmax(0,1fr) !important;
        align-items:center;
    }
    html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header > .reports-module-title-block {
        display:contents;
    }
    html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header .reports-reference-scope {
        grid-column:1;
        justify-self:start;
    }
    html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header .reports-module-heading-copy {
        grid-column:2;
        width:100%;
        text-align:center;
    }
    html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header .reports-module-primary-tabs {
        grid-column:3 !important;
        justify-self:end !important;
    }
    /* The title owns this gap; list wrappers must not add a second top gutter. */
    html[data-theme] body.panel-shell-body .panel-title-surface.panel-page-header ~ :is(.admin-table-page,.payments-page,.pm-page,.reminders-page) {
        padding-top: 0 !important;
    }
    html[data-theme] body.panel-shell-body .panel-title-surface::after { content: none; }
    html[data-theme] body.panel-shell-body .panel-title-surface.topbar {
        margin: 12px var(--panel-content-inline, 20px) 0;
    }
    html[data-theme] body.panel-shell-body .reports-reference-heading.panel-title-surface { align-items:center; }
    html[data-theme] body.panel-shell-body .ec-hero.panel-title-surface { padding: 0 !important; }
    html[data-theme] body.panel-shell-body .ec-hero.panel-title-surface .ec-hero__top { padding: 16px 20px; }
    @media(max-width:700px) {
        html[data-theme] body.panel-shell-body .panel-title-surface :is(h1,.ec-hero__title) { font-size:22px !important; }
        html[data-theme] body.panel-shell-body .panel-title-surface { padding: 14px 16px !important; border-radius: 14px !important; gap: 12px; }
        html[data-theme] body.panel-shell-body .panel-title-surface.panel-page-header,
        html[data-theme] body.panel-shell-body .panel-title-surface.topbar.centered-page-header,
        html[data-theme] body.panel-shell-body .service-reference-header.centered-page-header,
        html[data-theme] body.panel-shell-body .retention-hero.centered-page-header,
        html[data-theme] body.panel-shell-body .ops-page-header.centered-page-header,
        html[data-theme] body.panel-shell-body .contract-preview-head.centered-page-header {
            display:flex !important;
            flex-direction:column;
            align-items:stretch;
        }
        html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__leading {
            display:grid !important;
            grid-template-columns:40px 44px minmax(0,1fr);
            align-items:center;
            gap:8px;
            width:100%;
        }
        html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__leading > .mobile-menu-btn { grid-column:1; grid-row:1; width:40px; }
        html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__leading > :is(.case-back-icon,.owner-case-back,.owner-records-back) { position:static; grid-column:2; grid-row:1; transform:none; }
        html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__copy { grid-column:1 / -1; grid-row:2; max-width:none; }
        html[data-theme] body.panel-shell-body .panel-title-surface .panel-page-header__actions { justify-content:center; justify-self:center; }
        html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header { grid-template-columns:minmax(0,1fr) !important; }
        html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header > .reports-module-title-block { display:block; grid-column:1; text-align:center; }
        html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header .reports-reference-scope { justify-content:center; }
        html[data-theme] body.panel-shell-body .reports-reference-heading.centered-page-header .reports-module-primary-tabs { grid-column:1 !important; justify-self:center !important; max-width:100%; }
        html[data-theme] body.panel-shell-body .ec-hero.panel-title-surface { padding: 0 !important; }
        html[data-theme] body.panel-shell-body .ec-hero.panel-title-surface .ec-hero__top { padding: 16px; }
    }
}
</style>
