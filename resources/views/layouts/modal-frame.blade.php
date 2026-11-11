<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', 'Form') - Sabangan Caguioa</title>
    <script>
        (function () {
            const stored = localStorage.getItem('app-theme');
            const theme = stored === 'dark' || stored === 'light' ? stored : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="{{ asset('vendor/offline/fonts.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            margin:0;
            background:#DCE6D6;
            color:#1F2D20;
        }
        body:is(.branch-admin-ui,.system-admin-ui) {
            background:var(--surface);
            color:var(--ink);
            font-family:var(--font-body);
        }
        .page-content {
            min-height:100vh;
            padding:18px;
            background:#DCE6D6 !important;
            overflow:auto;
        }
        .page-content > * {
            margin-left:auto;
            margin-right:auto;
        }
        body:is(.branch-admin-ui,.system-admin-ui) .page-content {
            background:var(--surface) !important;
            color:var(--ink);
        }
        body.service-catalog-form-frame .page-content { padding:0; }
        body.service-catalog-modal-frame { min-height:0; height:auto; }
        body.service-catalog-modal-frame .page-content { min-height:0 !important; height:auto; overflow:visible; }
        body.service-catalog-form-frame :is(.pkg-hero,.cc-head,.aoc-head,.fbc-head) { display:none !important; }
        body.service-catalog-form-frame :is(.cc-card,.aoc-card,.fbc-card) { border:0 !important; border-radius:0 !important; box-shadow:none !important; }
        body.service-catalog-form-frame #packageForm.package-wizard { gap:0; }
        body.service-catalog-form-frame #packageForm.package-wizard .package-wizard-nav { border-radius:0; }
        [data-page-context-toast],
        .management-toast {
            display:none !important;
        }
        .page-content .admin-table-page,
        .page-content .admin-catalog-page,
        .page-content .cc-form {
            max-width:100% !important;
            padding-top:0 !important;
            padding-bottom:0 !important;
        }
        .page-content .cc-card,
        .page-content .package-form-card,
        .page-content .form-card {
            box-shadow:none !important;
        }
        @media (max-width: 640px) {
            .page-content {
                padding:12px;
            }
        }
        .service-discard-dialog { width:min(420px,calc(100vw - 32px)); padding:24px; border:1px solid var(--border); border-radius:12px; background:var(--card); color:var(--ink); font-family:var(--font-body); text-align:center; }
        .service-discard-dialog::backdrop { background:transparent; }
        .service-discard-dialog { box-shadow:0 12px 48px rgb(0 0 0 / 25%); }
        .service-discard-dialog__icon { display:grid; place-items:center; width:46px; height:46px; margin:0 auto 12px; border-radius:50%; background:#F7E9C9; color:#8A5A00; font-size:20px; }
        html[data-theme='dark'] .service-discard-dialog__icon { background:#3B2E17; color:#FBBF24; }
        .service-discard-dialog h2 { margin:0 0 8px; font-size:20px; font-weight:700; }
        .service-discard-dialog p { margin:0; color:var(--ink-muted); font-size:14px; line-height:1.6; }
        .service-discard-dialog footer { display:flex; flex-wrap:wrap; justify-content:center; gap:8px; margin-top:20px; }
        .service-discard-dialog button { min-height:44px; padding:10px 14px; border:1px solid var(--border); border-radius:8px; background:var(--surface-muted); color:var(--ink); font:600 13px var(--font-body); }
        .service-discard-dialog [data-discard] { color:#B42318; }
        html[data-theme='dark'] .service-discard-dialog [data-discard] { color:#FDA29B; }
        .service-discard-dialog button:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
        html[data-theme] body.service-catalog-form-frame .page-content [aria-invalid="true"] { border-color:#B42318 !important; }
        html[data-theme='dark'] body.service-catalog-form-frame .page-content [aria-invalid="true"] { border-color:#FDA29B !important; }
        html[data-theme] body.service-catalog-form-frame .page-content .form-error { color:#B42318; font-size:12px; line-height:1.5; font-weight:500; }
        html[data-theme='dark'] body.service-catalog-form-frame .page-content .form-error { color:#FDA29B; }
    </style>
</head>
<body class="{{ auth()->user()?->isBranchAdmin() ? 'branch-admin-ui' : '' }}{{ auth()->user()?->isSystemAdmin() ? ' system-admin-ui' : '' }}{{ request()->boolean('service_form') ? ' service-catalog-form-frame' : '' }}{{ request()->boolean('modal') ? ' service-catalog-modal-frame' : '' }}">
    <main class="page-content">
        @yield('content')
    </main>
    @include('partials.record_modal_surface_styles')
    @include('partials.service_detail_drawer_body')
    <script>
        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || window.parent === window) return;
            if (document.querySelector('.svc-status-dialog[open], .service-discard-dialog[open]')) return;
            window.parent.closeServiceCatalogModal?.();
        });
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-service-modal-url]');

            if (trigger && window.parent && window.parent !== window && window.parent.openServiceCatalogModal) {
                event.preventDefault();
                window.parent.openServiceCatalogModal(trigger, event);
                return;
            }

            const closeTrigger = event.target.closest('[data-parent-modal-close]');

            if (closeTrigger && window.parent && window.parent !== window && window.parent.closeServiceCatalogModal) {
                event.preventDefault();
                window.parent.closeServiceCatalogModal();
            }

            const cancelTrigger = event.target.closest('[data-service-form-cancel]');
            if (cancelTrigger && window.parent && window.parent !== window && window.parent.cancelServiceCatalogModal) {
                event.preventDefault();
                window.parent.cancelServiceCatalogModal();
            }
        });
        // Native navigation creates a fresh document after Laravel validation errors.
        // The parent handles the successful redirect back to the catalog hub.
        document.addEventListener('submit', function (event) {
            if (!document.body.classList.contains('service-catalog-form-frame') || event.defaultPrevented) return;
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || form.method === 'dialog') return;
            // The package wizard owns its own busy state. Only block a second
            // submission already accepted by this frame.
            if (form.dataset.serviceFrameSubmitting === 'true') { event.preventDefault(); return; }
            let mode = form.querySelector('input[name="_service_modal"]');
            if (!mode) { mode = document.createElement('input'); mode.type = 'hidden'; mode.name = '_service_modal'; form.append(mode); }
            mode.value = '1';
            form.dataset.serviceFrameSubmitting = 'true';
            form.setAttribute('aria-busy', 'true');
            const submit = event.submitter;
            if (submit && !submit.disabled) {
                submit.disabled = true;
                submit.textContent = 'Saving...';
            }
        });

    </script>
@include('partials.idle_session')
</body>
</html>
