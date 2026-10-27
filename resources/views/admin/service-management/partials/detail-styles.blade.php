<style>
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) { --font-body:'Plus Jakarta Sans','Segoe UI',sans-serif; --font-heading:var(--font-body); font-family:var(--font-body); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail { color:var(--ink); font-family:var(--font-body); font-size:14px; line-height:1.5; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail :is(h1,h2,h3,h4) { font-family:var(--font-heading); color:var(--ink) !important; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail h1 { font-size:24px; line-height:1.25; font-weight:700; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail :is(.svc-detail-stat-grid,.svc-detail-stat,.svc-detail-list-grid > *, .svc-detail-badge) { background:var(--surface-muted); border-color:var(--border); color:var(--ink); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) { --surface:#DCE8E0; --card:#F4F8F5; --surface-muted:#D0E2D6; --border:#AEC7B7; --ink:#081D13; --ink-muted:#1B3E2E; }
    html[data-theme='dark'] body:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) { --surface:#07130E; --card:#112218; --surface-muted:#14261D; --border:#29523D; --ink:#F8FAFC; --ink-muted:#94A3B8; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card { background:var(--card); border-color:var(--border); box-shadow:none; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) :is(.svc-detail-head,.svc-detail-section,.svc-detail-actions) { background:var(--card); border-color:var(--border); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail :is(h1,h2,h3,strong) { color:var(--ink); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail :is(p,small,.svc-detail-kicker) { color:var(--ink-muted); }
    .svc-detail {
        max-width: 980px;
        color: #1f2d20;
    }

    .svc-detail-card {
        overflow: hidden;
        border: 1px solid #B7C9AE;
        border-radius: 10px;
        background: #EEF4EA;
        box-shadow: 0 14px 30px rgba(31, 45, 32, 0.08);
    }

    .svc-detail-head,
    .svc-detail-section,
    .svc-detail-actions {
        padding: 22px;
    }

    .svc-detail-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        border-bottom: 1px solid #C7D7BE;
        background: #E3EEDC;
    }

    .svc-detail-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--ink-muted, #60705D);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .svc-detail h1,
    .svc-detail h2,
    .svc-detail h3,
    .svc-detail p {
        margin: 0;
    }

    .svc-detail h1 {
        font-size: clamp(1.5rem, 2vw, 2rem);
        line-height: 1.15;
    }

    .svc-detail-subtitle {
        margin-top: 8px;
        color: #60705D;
        font-weight: 700;
    }

    .svc-detail-badges,
    .svc-detail-actions,
    .svc-detail-stat-grid,
    .svc-detail-list-grid {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .svc-detail-overview {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--border, #C7D7BE);
        background: var(--card, #EEF4EA);
    }

    .svc-detail-overview-price { display: grid; gap: 4px; text-align: right; }
    .svc-detail-overview-price span { color: var(--ink-muted, #60705D); font-size: 11px; font-weight: 600; }
    .svc-detail-overview-price strong { color: var(--ink, #1F2D20); font-size: 20px; font-weight: 700; }

    .svc-detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 7px 12px;
        border: 1px solid #B7C9AE;
        border-radius: 999px;
        background: #F8FAF4;
        color: #43513F;
        font-size: 0.82rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .svc-detail-badge.is-active {
        border-color: #B6D9AC;
        background: #E4F2DF;
        color: #2F5E34;
    }

    .svc-detail-badge.is-muted {
        color: #7C6E48;
        border-color: #D6C9A0;
        background: #F3ECD4;
    }

    .svc-detail-primary,
    .svc-detail-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 8px;
        font-weight: 800;
        text-decoration: none;
        transition: border-color 160ms ease, background 160ms ease, color 160ms ease, box-shadow 160ms ease;
    }

    .svc-detail-primary {
        border: 1px solid #284A2D;
        background: #284A2D;
        color: #fff;
        box-shadow: 0 12px 22px rgba(40, 74, 45, 0.18);
    }

    .svc-detail-secondary {
        border: 1px solid #B7C9AE;
        background: #F8FAF4;
        color: #43513F;
    }

    .svc-detail-primary:hover,
    .svc-detail-primary:focus-visible {
        background: #203D24;
        color: #fff;
    }

    .svc-detail-secondary:hover,
    .svc-detail-secondary:focus-visible {
        border-color: #78906E;
        color: #284A2D;
    }

    .svc-detail-primary:focus-visible,
    .svc-detail-secondary:focus-visible {
        outline: 3px solid rgba(47, 81, 49, 0.3);
        outline-offset: 2px;
    }

    .svc-detail-stat-grid {
        padding: 18px 22px;
        border-bottom: 1px solid #C7D7BE;
        background: #DCE8D4;
    }

    .svc-detail-stat {
        flex: 1 1 190px;
        min-width: 0;
        padding: 16px;
        border: 1px solid #B7C9AE;
        border-radius: 8px;
        background: rgba(248, 250, 244, 0.72);
    }

    .svc-detail-stat span,
    .svc-detail-section h3 {
        display: block;
        color: var(--ink-muted, #60705D);
        font-size: 0.78rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .svc-detail-stat strong {
        display: block;
        margin-top: 8px;
        font-size: clamp(1.25rem, 2vw, 1.65rem);
        line-height: 1.1;
    }

    .svc-detail-section {
        border-bottom: 1px solid #C7D7BE;
    }

    .svc-detail-list-grid {
        margin-top: 14px;
    }

    .svc-detail-list {
        flex: 1 1 260px;
        min-width: 0;
        padding: 16px;
        border: 1px solid #C7D7BE;
        border-radius: 8px;
        background: #F8FAF4;
    }

    .svc-detail-list ul {
        display: grid;
        gap: 10px;
        margin: 12px 0 0;
        padding: 0;
        list-style: none;
    }

    .svc-detail-list li {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        color: #344232;
        font-weight: 700;
    }

    .svc-detail-list li::before {
        content: "";
        width: 7px;
        height: 7px;
        margin-top: 8px;
        border-radius: 999px;
        background: #2F8D5B;
        flex: 0 0 auto;
    }

    .svc-detail-empty {
        margin-top: 12px;
        color: #60705D;
        font-style: italic;
        font-weight: 700;
    }

    .svc-detail-note {
        margin-top: 12px;
        color: #43513F;
        line-height: 1.55;
        font-weight: 650;
    }

    .svc-detail-actions {
        justify-content: flex-end;
        background: #DCE8D4;
    }

    @media (max-width: 720px) {
        .svc-detail-head {
            display: grid;
        }

        .svc-detail-actions {
            display: grid;
        }
    }

    /* Keep the outer dialog as the single modal container; the content follows the
       reference's quieter header, summary tiles, detail panels, and footer. */
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail { max-width:900px; font-size:14px; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card { border-radius:16px; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-head { padding:24px; background:var(--surface-muted); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-kicker { margin-bottom:7px; font-size:.73rem; font-weight:650; letter-spacing:.04em; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail h1 { font-size:clamp(1.3rem,2vw,1.65rem); line-height:1.35; font-weight:700; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-subtitle { margin-top:5px; font-size:.88rem; font-weight:450; line-height:1.55; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-badge { min-height:30px; padding:5px 10px; font-size:.75rem; font-weight:650; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat-grid { gap:12px; padding:18px 24px; background:var(--card); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat { padding:15px; border-radius:12px; background:var(--surface-muted); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat span,
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-section h3 { font-size:.73rem; font-weight:650; letter-spacing:.04em; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat strong { font-weight:700; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-section { padding:20px 24px; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list { border-radius:12px; padding:16px; background:var(--surface-muted); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list li,
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-note { color:var(--ink-muted); font-weight:450; line-height:1.6; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-actions { gap:10px; padding:18px 24px; background:var(--card); }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-primary { background:#1B4332; border-color:#1B4332; box-shadow:none; font-weight:650; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-secondary { background:var(--surface-muted); border-color:var(--border); color:var(--ink); font-weight:600; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-primary:hover { background:#285A43; }
    html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-secondary:hover { background:var(--border); color:var(--ink); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) .page-content { padding:0; min-height:0; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) .svc-detail-card { border:0; border-radius:0; box-shadow:none; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) { background:var(--card); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail { max-width:none; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-head { min-height:0; padding:16px 76px 14px 20px; background:var(--card); border-bottom:1px solid var(--border); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-kicker { padding:3px 8px; border:1px solid var(--border); border-radius:6px; background:var(--surface-muted); color:var(--ink-muted); font-size:11px; letter-spacing:0; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin:14px 20px 0; padding:12px 14px; border:1px solid var(--border); border-radius:12px; background:var(--surface-muted); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview-price { display:grid; gap:4px; text-align:right; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview-price span { color:var(--ink-muted); font-size:11px; font-weight:600; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview-price strong { color:var(--ink); font-size:20px; line-height:1.25; font-weight:700; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat-grid { gap:10px; padding:12px 20px; border:0; background:var(--card); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat { padding:11px 13px; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat strong { margin-top:4px; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-section { margin:0 20px; padding:12px 0; background:var(--card); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list { padding:12px 14px; background:var(--surface-muted); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list-grid { margin-top:10px; gap:10px; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list ul { gap:6px; margin-top:8px; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-actions { justify-content:flex-end; padding:12px 20px; border-top:1px solid var(--border); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-actions button { cursor:pointer; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-actions :is(button,a) { min-width:88px; }
    @media(max-width:600px) {
        html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-head,
        html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-section,
        html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-actions { padding:18px; }
        html[data-theme] body:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat-grid { padding:14px 18px; }
        html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview { margin:16px 18px 0; }
        html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-section { margin-inline:18px; }
        html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview-price { text-align:left; }
    }

    /* Match the supplied ServiceManagementView detail dialog without copying its sample data. */
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) { background:#E8F1EC; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail { padding:24px; font-size:13px; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card { background:transparent; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-head { padding:0 56px 16px 0; border-bottom:1px solid rgb(174 199 183 / 60%); background:transparent; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail h1 { margin-top:7px; font-size:22px; font-weight:800; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-kicker { margin:0; padding:2px 8px; background:rgb(255 255 255 / 80%); color:var(--ink-muted); font-size:10px; font-weight:800; text-transform:uppercase; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-subtitle { margin-top:3px; font-size:12px; font-weight:500; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview { margin:18px 0 0; padding:13px 14px; border-radius:16px; background:rgb(255 255 255 / 80%); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-badge { min-height:26px; padding:4px 10px; font-size:11px; font-weight:700; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview-price strong { font-size:20px; color:#1B4332; font-weight:800; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat-grid { gap:12px; padding:16px 0 0; background:transparent; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat { padding:12px 14px; border-radius:12px; background:rgb(255 255 255 / 65%); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-stat strong { font-size:15px; font-weight:650; line-height:1.4; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-section { margin:0; padding:16px 0 0; border:0; background:transparent; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-description .svc-detail-note { margin-top:8px; padding:13px 14px; border:1px solid var(--border); border-radius:12px; background:rgb(255 255 255 / 65%); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list-grid { gap:12px; margin-top:10px; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list { padding:13px 14px; border-radius:12px; background:rgb(255 255 255 / 65%); }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-list li { font-size:12px; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-actions { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:10px; margin-top:18px; padding:16px 0 0; border-top:1px solid rgb(174 199 183 / 60%); background:transparent; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-primary { background:#2D6A4F; border-color:#2D6A4F; }
    html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-secondary { background:rgb(255 255 255 / 65%); }
    html[data-theme='dark'] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui):has(.svc-detail) { background:#0F241C; }
    html[data-theme='dark'] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) :is(.svc-detail-kicker,.svc-detail-overview,.svc-detail-stat,.svc-detail-description .svc-detail-note,.svc-detail-list,.svc-detail-secondary) { background:rgb(7 19 14 / 70%); border-color:#1B4332; }
    html[data-theme='dark'] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview-price strong { color:#86D2A8; }
    @media(max-width:600px) {
        html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail { padding:18px; }
        html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-head { padding:0 52px 14px 0; }
        html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-overview { margin-top:14px; }
    }
    .svc-status-feedback { display:flex; align-items:flex-start; gap:8px; margin:0 0 12px; padding:11px 14px; border:1px solid var(--border); border-radius:10px; background:var(--card); color:var(--ink); font-size:13px; }
    .svc-status-feedback.is-error { border-color:#CC8A83; color:#8B3028; }
    .svc-intake-readiness ul { margin:6px 0 0 20px; list-style:disc; }
    .svc-intake-readiness li { margin-top:4px; }
    .svc-status-dialog { width:min(440px,calc(100vw - 32px)); max-height:calc(100vh - 32px); margin:auto; padding:20px; border:1px solid var(--border); border-radius:16px; background:var(--card); color:var(--ink); box-shadow:0 18px 50px rgb(0 0 0 / 20%); }
    .svc-status-dialog::backdrop { background:rgba(15,23,42,.55); }
    .svc-status-dialog-head { display:flex; align-items:flex-start; gap:12px; }
    .svc-status-dialog-icon { display:grid; place-items:center; flex:0 0 38px; height:38px; border-radius:9px; background:var(--surface-muted); color:var(--ink); }
    .svc-status-dialog h2 { margin:0 0 5px; font-size:17px; line-height:1.35; }
    .svc-status-dialog p { margin:0; color:var(--ink-muted); font-size:13px; line-height:1.55; }
    .svc-status-affected { margin-top:14px; padding:12px; border:1px solid var(--border); border-radius:10px; background:var(--surface-muted); font-size:12px; }
    .svc-status-affected ul { margin:6px 0 0 18px; list-style:disc; }
    .svc-status-affected li { margin-top:4px; }
    .svc-status-affected a { color:var(--ink); text-decoration:underline; }
    .svc-status-dialog-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:8px; margin-top:18px; }
    .svc-status-dialog-actions form { margin:0; }
    .svc-status-dialog-actions button { min-height:40px; cursor:pointer; }
    .svc-status-dialog :is(button,a):focus-visible { outline:2px solid #2D6A4F; outline-offset:2px; }
    html[data-theme='dark'] .svc-status-feedback.is-error { color:#F5B2AA; }
</style>
