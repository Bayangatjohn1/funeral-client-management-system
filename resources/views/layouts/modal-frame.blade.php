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
    </style>
</head>
<body class="{{ auth()->user()?->isBranchAdmin() ? 'branch-admin-ui' : '' }}{{ auth()->user()?->isSystemAdmin() ? ' system-admin-ui' : '' }}{{ request()->boolean('service_form') ? ' service-catalog-form-frame' : '' }}{{ request()->boolean('modal') ? ' service-catalog-modal-frame' : '' }}">
    <main class="page-content">
        @yield('content')
    </main>
    <script>
        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || window.parent === window) return;
            if (document.querySelector('.svc-status-dialog[open]')) return;
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
        document.addEventListener('submit', function (event) {
            if (!document.body.classList.contains('service-catalog-form-frame') || event.defaultPrevented) return;
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            const submit = event.submitter || form.querySelector('button[type="submit"]');
            form.setAttribute('aria-busy', 'true');
            if (submit) {
                submit.disabled = true;
                submit.dataset.originalLabel = submit.innerHTML;
                submit.innerHTML = '<i class="bi bi-arrow-repeat" aria-hidden="true"></i> Saving…';
            }
        });
    </script>
@include('partials.idle_session')
</body>
</html>
