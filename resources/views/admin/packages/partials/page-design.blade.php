<style>
/* Scoped package page: application type scale, prototype section hierarchy. */
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] { --pkg-selected:#F2F8F4; --pkg-selection:#1B4332; --pkg-muted:#485D50; --pkg-page:#FAF9F6; --pkg-card:#FFFFFF; --pkg-inset:#F0F5F1; --pkg-field:#F7FAF8; --pkg-line:#B8C8BD; max-width:1200px; margin:auto; padding:24px 32px 48px; gap:24px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-layout { grid-template-columns:minmax(0,1fr); gap:24px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-main { gap:24px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-hero { display:flex !important; position:relative; top:auto; z-index:1; margin:0; padding:18px 20px; border:1px solid var(--pkg-line) !important; border-radius:14px !important; background:var(--pkg-card) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-page-actions { display:flex; align-items:center; gap:12px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.pkg-eyebrow,.pkg-icon,.pkg-service-icon) { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-title { font-size:24px; font-weight:700 !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.pkg-subtitle,.pkg-panel-copy,.pkg-service-note,.pkg-service-help,.pkg-inline-help) { font-weight:400 !important; line-height:1.6; color:var(--pkg-muted) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.pkg-panel,.pkg-review) { border:1px solid var(--pkg-line) !important; border-radius:12px !important; background:var(--pkg-card) !important; box-shadow:none !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-head { padding:24px 24px 20px; border-bottom:0 !important; background:transparent !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-title { font-size:18px; font-weight:700 !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-body { padding:0 24px 24px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-label { font-size:13px; font-weight:600 !important; text-transform:none; letter-spacing:0; color:var(--ink) !important; margin-bottom:6px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.form-input,.form-select,textarea) { font-family:inherit; font-size:14px; font-weight:400 !important; border-radius:8px; min-height:44px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-grid { gap:20px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-grid-2 { grid-template-columns:repeat(2,minmax(0,1fr)); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel:first-child .pkg-grid-2 { grid-template-columns:2fr 1fr; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-list { gap:16px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-card { border:1px solid var(--pkg-line) !important; border-radius:12px; background:var(--pkg-inset) !important; overflow:hidden; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-top { display:grid; grid-template-columns:20px minmax(0,1fr) 36px; align-items:center; gap:12px; padding:18px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-name { grid-column:2; grid-row:1; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-label { font-size:14px; font-weight:600 !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-controls { display:contents; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-state { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-controls .pkg-switch { grid-column:1; grid-row:1; width:20px; height:20px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-controls .pkg-switch input[type=checkbox] { position:static; opacity:1; appearance:auto !important; width:18px !important; height:18px !important; min-height:18px !important; padding:0 !important; border-radius:3px !important; accent-color:var(--brand); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-controls .pkg-slider { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-disclosure { grid-column:3; grid-row:1; border:0; background:transparent; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-fields { margin:0 18px; padding:18px 0; border-top:1px solid var(--pkg-line); background:transparent !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review { position:static; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel:not(.is-collapsible) .pkg-panel-toggle { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] [aria-invalid=true] { border-color:#B42318 !important; }

/* Complete field, checklist, promotion, and review states. */
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-toggle { min-width:40px; min-height:40px; border:1px solid var(--pkg-line); background:transparent !important; color:var(--ink); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-extra { display:block; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-card:not(.is-expanded) .pkg-service-fields { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.form-input,.form-select,textarea,.pkg-money) { border:1px solid var(--pkg-line) !important; background:var(--pkg-field) !important; color:var(--ink) !important; box-shadow:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-money .form-input { border:0 !important; background:transparent !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.form-input,.form-select,textarea):focus-visible, html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-money:focus-within { outline:2px solid var(--brand); outline-offset:2px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.form-input,textarea)::placeholder { color:var(--pkg-muted); opacity:1; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(input,select,textarea):disabled { cursor:not-allowed; opacity:.6; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-money { min-height:44px; border-radius:8px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-money > span { color:var(--pkg-muted); font-weight:600; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-help { margin-top:14px; padding:14px 16px; border:1px solid var(--pkg-line); border-radius:8px; background:var(--pkg-card); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-casket-summary { background:var(--pkg-card) !important; border:1px solid var(--pkg-line) !important; border-radius:8px; padding:14px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-casket-summary :is(strong,span) { color:var(--ink) !important; font-weight:400; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-casket-summary strong { font-weight:600; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-casket-meta span { background:var(--pkg-inset) !important; border-color:var(--pkg-line) !important; color:var(--pkg-muted) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-casket-edit { color:var(--brand); font-weight:600; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-muted-box { padding:18px; border:1px solid var(--pkg-line); border-radius:12px; background:var(--pkg-inset) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.pkg-subhead-title,.pkg-inline-title,.pkg-freebie-legacy-title) { color:var(--ink) !important; font-size:14px; font-weight:600 !important; text-transform:none; letter-spacing:0; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-list-head { gap:12px; align-items:flex-start; flex-wrap:wrap; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.pkg-tool-btn,.pkg-freebie-action) { min-height:40px; border-radius:8px; font-size:12px; font-weight:600; color:var(--ink) !important; background:var(--pkg-card) !important; border-color:var(--pkg-line) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-remove-btn,html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-remove-all-btn { color:#B42318 !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-row-card { border:1px solid var(--pkg-line) !important; border-radius:8px; background:var(--pkg-card) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-row-list:empty { padding:20px; border:1px dashed var(--pkg-line); border-radius:8px; color:var(--pkg-muted); font-size:13px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-checklist { grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card { padding:18px; gap:14px; background:var(--pkg-inset) !important; border:1px solid var(--pkg-line) !important; border-radius:12px; box-shadow:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card.is-selected { border-color:var(--brand) !important; background:var(--pkg-card) !important; box-shadow:inset 0 0 0 1px var(--brand); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card:focus-visible { outline:2px solid var(--brand); outline-offset:3px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-choice { align-items:center; gap:12px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-choice input { position:static; opacity:1; pointer-events:auto; appearance:auto !important; width:18px !important; height:18px !important; min-height:18px !important; padding:0 !important; accent-color:var(--brand); flex:0 0 18px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-mark { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-choice strong { font-size:14px; font-weight:600; line-height:1.5; color:var(--ink) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-choice > span:last-child > span { font-size:12px; font-weight:400; line-height:1.5; color:var(--pkg-muted) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-config-grid { padding-left:30px; grid-template-columns:80px minmax(0,1fr); gap:12px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card:not(.is-selected) .pkg-freebie-config-grid { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-row-label { display:block; color:var(--pkg-muted) !important; font-size:12px; font-weight:600 !important; text-transform:none; letter-spacing:0; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-actions { background:transparent; border-color:var(--pkg-line); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-action { border-radius:0; background:transparent; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-complimentary-note { margin-top:18px; color:var(--pkg-muted); font-size:13px; line-height:1.6; font-style:italic; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-optional { font-size:12px; font-weight:400; color:var(--pkg-muted); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-toggle { justify-content:flex-start; flex-direction:row; gap:12px; padding:0; border:0 !important; background:transparent !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-toggle .pkg-switch { order:-1; width:20px; height:20px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-toggle .pkg-switch input[type=checkbox] { position:static; opacity:1; appearance:auto !important; width:18px !important; height:18px !important; min-height:18px !important; padding:0 !important; accent-color:var(--brand); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-toggle .pkg-slider { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-fields { border:0; padding:0; margin-top:20px; grid-template-columns:repeat(3,minmax(0,1fr)); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-fields > div:first-child { grid-column:span 2; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-fields > .pkg-grid { display:contents; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-head { background:transparent !important; padding:24px; border-color:var(--pkg-line); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-title { font-size:18px; font-weight:700 !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-list { padding:0 24px 24px; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-kicker { font-size:12px; font-weight:600 !important; text-transform:none; letter-spacing:0; color:var(--pkg-muted) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-value { font-size:14px; font-weight:600 !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-lines li { background:var(--pkg-inset) !important; border-color:var(--pkg-line) !important; color:var(--ink) !important; font-weight:400; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-actions { display:flex; justify-content:flex-end; gap:12px; background:var(--pkg-inset) !important; border-color:var(--pkg-line); padding:18px 24px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-link-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 16px; border:1px solid var(--pkg-line); border-radius:8px; background:var(--pkg-card); color:var(--ink); text-decoration:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-link-btn i { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .form-error { color:#B42318; font-size:12px; font-weight:400; line-height:1.5; }
html[data-theme="dark"] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.form-error,.pkg-remove-btn,.pkg-remove-all-btn) { color:#FDA29B !important; }

/* Reference layout contract: source section order, field groups and checklist arrangement. */
html[data-theme="dark"] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] { --pkg-selected:#0F2219; --pkg-selection:#047857; --pkg-muted:#B5C6BB; --pkg-page:#0E1511; --pkg-card:#14271B; --pkg-inset:#0F1F15; --pkg-field:#0C1A12; --pkg-line:#335541; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page]  { background:var(--pkg-page); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-hero > div:first-child { position:relative; padding-left:40px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-hero-icon { position:absolute; left:0; top:0; display:grid; place-items:center; width:36px; height:36px; border:1px solid var(--pkg-line); border-radius:10px; background:var(--pkg-inset); color:var(--brand); font-size:18px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-back { position:absolute; left:0; top:4px; width:32px; height:32px; display:grid; place-items:center; color:var(--pkg-muted); border-radius:8px; text-decoration:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-head { margin:0 24px 20px; padding:24px 0 12px; border-bottom:1px solid var(--pkg-line) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-head-actions > .pkg-badge { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-top { padding:16px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-fields { margin:0 16px; padding:16px 0; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-extra { margin:0; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-disclosure { width:32px; min-height:32px; color:var(--pkg-muted); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-disclosure[aria-expanded=true] i { transform:rotate(180deg); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-field-help { margin-top:6px; font-size:12px; line-height:1.6; color:var(--pkg-muted); font-weight:400; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-unit-input { position:relative; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-unit-input input { padding-right:54px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-unit-input > span { position:absolute; right:14px; top:12px; color:var(--pkg-muted); font-size:12px; pointer-events:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes { padding:0; border:0 !important; background:transparent !important; margin-top:20px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes .pkg-list-head { margin-bottom:6px; align-items:center; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes .pkg-subhead-title { font-size:13px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-note-options summary, html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-unit-options summary { cursor:pointer; font-size:12px; color:var(--pkg-muted); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-note-options .pkg-list-actions { padding-top:8px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes .pkg-row-card { padding:0; border:0 !important; background:transparent !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes .pkg-row-grip { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes .pkg-custom-row { grid-template-columns:minmax(0,1fr) 36px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes .pkg-row-card:only-child:has(input:placeholder-shown) { grid-template-columns:minmax(0,1fr); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-reference-notes .pkg-row-card:only-child:has(input:placeholder-shown) .pkg-remove-btn { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; padding:14px; gap:8px 12px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-choice { grid-column:1; grid-row:1; margin:0; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-config-grid { display:contents; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-config-grid > div { grid-column:2; grid-row:1; display:flex; align-items:center; gap:6px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-config-grid > div .pkg-row-label { margin:0; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-config-grid > div input { width:56px; min-height:36px; height:36px; padding:6px; text-align:center; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-unit-options { grid-column:1 / -1; padding-left:30px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-unit-options .form-input { margin-top:6px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-unit-options label { margin-top:8px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card.is-selected { background:var(--pkg-selected) !important; border-color:var(--pkg-selection) !important; box-shadow:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review { border:0 !important; border-radius:0 !important; background:transparent !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-disclosure { padding:10px 0; cursor:pointer; font-size:13px; color:var(--pkg-muted); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review[open] { border:1px solid var(--pkg-line) !important; border-radius:12px !important; background:var(--pkg-card) !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review[open] > summary { padding:16px 24px; border-bottom:1px solid var(--pkg-line); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-head { display:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-list { padding-top:20px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-casket-search-tools { margin-bottom:8px; font-size:12px; color:var(--pkg-muted); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-casket-search-tools summary { cursor:pointer; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-review-bulk { padding:16px 24px 0; }
html[data-theme="dark"] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.pkg-service-fields,.pkg-promo-fields,.pkg-freebie-card) :is(.form-input,.form-select,.pkg-money) { background:#0C1A12 !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-configuration-errors { padding:16px; border:1px solid #FECDD3; border-radius:12px; background:#FFF1F2; color:#9F1239; font-size:13px; line-height:1.6; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-configuration-errors ul { padding-left:20px; margin-top:6px; list-style:disc; }
html[data-theme="dark"] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-configuration-errors { background:#32151F; border-color:#881337; color:#FECDD3; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page]  { max-width:none !important; padding:0 0 40px !important; background:transparent !important; border:0 !important; border-radius:0 !important; box-shadow:none !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-top { cursor:pointer; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card.is-expanded .pkg-freebie-config-grid { display:contents; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card { cursor:pointer; }
@media(max-width:640px) {
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] { padding:64px 0 28px !important; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-hero { position:static; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-page-actions { width:100%; justify-content:flex-end; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel .pkg-grid-2, html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel:first-child .pkg-grid-2 { grid-template-columns:minmax(0,1fr); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-head { margin:0 16px 18px; padding:18px 0 12px; flex-direction:column; align-items:stretch; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-body { padding:0 16px 18px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] :is(.pkg-freebie-checklist,.pkg-promo-fields,.pkg-review-list) { grid-template-columns:minmax(0,1fr); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-fields > div:first-child { grid-column:auto; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel-head-actions { flex-wrap:wrap; justify-content:space-between; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebie-card { grid-template-columns:minmax(0,1fr) auto; }
}

html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-panel:first-child .pkg-grid-2 > div:last-child { grid-column:1 / -1; }
/* Compact package dialog shares the catalog controls and keeps service disclosure separate. */
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-switch { position:relative; display:inline-block; width:40px !important; height:24px !important; flex:0 0 40px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-switch input[type=checkbox] { position:absolute !important; inset:0; width:100% !important; height:100% !important; min-height:0 !important; margin:0; opacity:0 !important; appearance:none !important; z-index:2; cursor:pointer; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-slider { display:block !important; position:absolute; inset:0; border-radius:999px; background:#7A8980; border:1px solid var(--pkg-line); pointer-events:none; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-slider::before { content:""; position:absolute; width:18px; height:18px; left:2px; top:2px; border-radius:50%; background:#FFF; transition:transform .15s; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-switch input:checked + .pkg-slider { background:var(--brand,#1B4332); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-switch input:checked + .pkg-slider::before { transform:translateX(16px); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-switch input:focus-visible + .pkg-slider { outline:2px solid var(--brand); outline-offset:3px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-service-top { grid-template-columns:40px minmax(0,1fr) 32px; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-promo-toggle .pkg-switch { order:0; margin-left:auto; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-compact-footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 0 0; border-top:1px solid var(--pkg-line); }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] { padding:16px !important; gap:16px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-hero { display:none !important; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-main { gap:16px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-panel-head { padding:16px 0 12px; margin:0 16px 12px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-panel-body { padding:0 16px 16px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-panel-title { font-size:16px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-panel-copy { font-size:12px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-service-top { padding:12px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-service-fields { padding:12px 0; margin:0 12px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-grid { gap:14px; }
html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-compact-footer { position:sticky; bottom:0; z-index:5; background:var(--pkg-card); margin:0 -16px -16px; padding:14px 16px; }
@media(max-width:600px) { html[data-theme] body.service-catalog-modal-frame #packageForm[data-package-page] .pkg-grid-2 { grid-template-columns:1fr !important; } }

/* Standalone package page: restrained content width and compact section rhythm. */
html[data-theme] body.panel-shell-body #packageForm[data-package-page] { max-width:960px !important; width:100%; margin:0 auto; padding:0 20px 28px !important; gap:16px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-main { gap:16px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-hero { padding:16px 0; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-panel-head { margin:0 18px 12px; padding:16px 0 12px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-panel-body { padding:0 18px 18px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-grid { gap:16px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-service-list { gap:10px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-service-top { padding:12px 14px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-service-fields { padding:14px 0; margin:0 14px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card { padding:14px; }
@media(max-width:640px) {
html[data-theme] body.panel-shell-body #packageForm[data-package-page] { padding:48px 12px 24px !important; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-hero { flex-wrap:wrap; gap:12px; }
}

html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-hero { align-items:center; gap:20px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-hero > div:first-child { flex:1; min-width:0; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-page-actions { flex-shrink:0; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-page-actions .btn { min-height:40px; white-space:nowrap; padding:10px 16px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-service-top { grid-template-columns:40px minmax(0,1fr) auto; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-service-disclosure { display:inline-flex; align-items:center; gap:7px; width:auto; padding:8px 10px; border:1px solid var(--pkg-line); border-radius:7px; color:var(--ink); font-size:12px; background:var(--pkg-card); }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-limit-label { color:#167044 !important; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-excess-label { color:#986000 !important; }
html[data-theme='dark'] body.panel-shell-body #packageForm[data-package-page] .pkg-limit-label { color:#52E6B0 !important; }
html[data-theme='dark'] body.panel-shell-body #packageForm[data-package-page] .pkg-excess-label { color:#FBBF24 !important; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-promo-header-control { display:flex; align-items:center; gap:10px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-promo-panel .pkg-panel-head { border-bottom:0 !important; margin-bottom:0; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-promo-fields { margin-top:12px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-list-actions { display:flex; gap:8px; flex-wrap:wrap; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] [data-clear-custom][hidden] { display:none !important; }
@media(max-width:640px) { html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-list-head { flex-direction:column; gap:10px; } }

/* One action group, aligned with the page title. */
html[data-theme] body.panel-shell-body #packageForm[data-package-page] { margin-top:8px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-hero { display:grid !important; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:20px; padding:18px 20px !important; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-hero > div:first-child { padding-left:50px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-page-actions { width:auto; display:flex; gap:10px; align-items:center; justify-content:flex-end; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-reference-back { top:50%; transform:translateY(-50%); width:44px; height:44px; border:1px solid var(--pkg-line); background:var(--pkg-card); color:var(--ink); font-size:20px; }
.pkg-back-tooltip { position:absolute; top:calc(100% + 8px); left:0; padding:6px 10px; width:max-content; border-radius:7px; background:var(--ink); color:var(--card); font-size:12px; font-weight:500; line-height:1.4; opacity:0; visibility:hidden; pointer-events:none; }
.pkg-reference-back:is(:hover,:focus-visible) .pkg-back-tooltip { opacity:1; visibility:visible; }
@media(max-width:760px) {
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-hero { grid-template-columns:1fr; gap:12px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-page-actions { justify-content:flex-end; }
}

html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebies-empty { display:flex; align-items:flex-start; gap:12px; padding:10px 0 4px; color:var(--pkg-muted); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebies-empty > i { font-size:18px; line-height:1.5; opacity:.75; }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebies-empty p { margin:0 0 4px; font-size:14px; font-weight:500; color:var(--pkg-muted); }
html[data-theme] body:is(.panel-shell-body,.service-catalog-modal-frame) #packageForm[data-package-page] .pkg-freebies-empty span { font-size:13px; font-weight:400; line-height:1.6; }

html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-actions { display:flex; width:max-content; margin-bottom:14px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-checklist { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr)); align-items:start; gap:12px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card { display:flex; flex-direction:column; gap:12px; padding:16px; cursor:default; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-choice { display:flex; width:100%; gap:12px; align-items:flex-start; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-config-grid { display:grid; width:100%; padding:12px 0 0; border-top:1px solid var(--pkg-line); grid-template-columns:90px minmax(0,1fr); gap:12px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card.is-expanded .pkg-freebie-config-grid { display:grid; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card:not(.is-selected) .pkg-freebie-config-grid { display:none; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-unit-options { grid-column:auto; }

html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card.is-selected .pkg-freebie-config-grid { display:grid !important; grid-template-columns:90px minmax(0,1fr); width:100%; padding:12px 0 0; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card .pkg-freebie-config { display:flex; flex-direction:column; align-items:stretch; gap:6px; position:static; width:100%; grid-column:auto; grid-row:auto; padding:0; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card .pkg-freebie-config .form-input { width:100% !important; min-width:0; height:42px; }
html[data-theme] body.panel-shell-body #packageForm[data-package-page] .pkg-freebie-card .pkg-row-label { display:block; margin:0; }
</style>
