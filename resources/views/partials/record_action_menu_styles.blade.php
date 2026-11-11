<style>
html[data-theme] body.panel-shell-body { --row-menu-bg:#D5E4D9; --row-menu-hover:#C1D8CA; --row-menu-text:#123524; }
html[data-theme=dark] body.panel-shell-body { --row-menu-bg:#304C3B; --row-menu-hover:#3C5B47; --row-menu-text:#E7F0E9; }
html[data-theme] body.panel-shell-body .page-content [data-row-menu] > .row-action-dropdown {
    --ink:var(--row-menu-text);
    box-sizing:border-box; width:max-content; min-width:190px; max-width:calc(100vw - 24px);
    max-height:calc(100dvh - 24px); overflow:auto; padding:8px !important; border-radius:10px !important;
    background-color:var(--row-menu-bg) !important; color:var(--row-menu-text) !important;
    border-color:transparent !important; box-shadow:none !important;
}
html[data-theme] body.panel-shell-body .page-content [data-row-menu]:not(.is-open) > .row-action-dropdown { display:none !important; }
html[data-theme] body.panel-shell-body .page-content [data-row-menu] > .row-action-dropdown .row-action-item {
    box-sizing:border-box; min-height:40px; width:100%; border-radius:8px;
}
html[data-theme] body.panel-shell-body .page-content [data-row-menu] > .row-action-dropdown .row-action-item:is(:hover,:focus-visible) {
    background-color:var(--row-menu-hover) !important; box-shadow:none !important;
}
html[data-theme] body.panel-shell-body .page-content [data-row-menu] > .row-action-dropdown .row-action-item:focus-visible {
    outline:2px solid var(--filter-ui-focus,#1B4332) !important; outline-offset:-2px;
}
</style>
