<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', 'Case Intake')</title>
    <script>
        try {
            document.documentElement.dataset.theme = localStorage.getItem('app-theme') === 'dark' ? 'dark' : 'light';
        } catch {}
    </script>
    <link href="{{ asset('vendor/offline/fonts.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @if(auth()->user()?->isStaff())
        @include('partials.shared_filter_ui')
    @endif
    <style>
        html, body.intake-frame { margin:0; overflow:hidden; }
        html[data-theme],
        html[data-theme] body.panel-shell-body.staff-ui.intake-frame,
        html[data-theme] body.panel-shell-body.staff-ui.intake-frame .page-content {
            background:transparent !important;
        }
        body.intake-frame::before, body.intake-frame::after { display:none !important; }
        body.intake-frame { --topbar-h:0px; }
        body.panel-shell-body.staff-ui.intake-frame .page-content {
            display:flex;
            flex-direction:column;
            height:100dvh;
            overflow:hidden !important;
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference {
            flex:1;
            height:auto !important;
            min-height:0 !important;
            padding:0 !important;
            overflow:hidden !important;
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference
            :is(.intake-section-shell,.intake-form-wrapper,#intakeWizardForm,#intakeFormContent) {
            flex:1 !important;
            min-height:0 !important;
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference .intake-section-shell { max-width:none; }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference #intakeWizardForm {
            display:flex;
            flex-direction:column;
            background:transparent !important;
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference .wizard-steps-shell {
            border:0 !important;
            border-radius:0 !important;
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference #intakeFormContent {
            flex:1 1 0 !important;
            max-height:none !important;
            overflow-x:hidden !important;
            overflow-y:auto !important;
            padding:12px !important;
            overscroll-behavior:contain;
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root .schedule-time-popover[popover] {
            position:absolute !important;
            inset:auto;
            right:auto !important;
            bottom:auto !important;
            margin:0;
            width:min(280px,calc(100vw - 16px)) !important;
            max-height:calc(100dvh - 16px);
            overflow:auto;
            box-shadow:none;
        }
        body.intake-frame :is(.schedule-time-popover[popover],.flatpickr-calendar) {
            opacity:0;
        }
        body.intake-frame :is(.schedule-time-popover:popover-open,.flatpickr-calendar.open) { opacity:1; }
        @media (prefers-reduced-motion:no-preference) {
            body.intake-frame :is(.schedule-time-popover[popover],.flatpickr-calendar) {
                transition:opacity 100ms ease-out, display 100ms allow-discrete, overlay 100ms allow-discrete;
            }
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference .section-title-block {
            margin-bottom:12px !important;
            padding-bottom:0;
            border-bottom:0 !important;
        }
        body.panel-shell-body.staff-ui.intake-frame .intake-root.intake-reference .footer-action-bar {
            flex:none;
            margin-top:0 !important;
            border:0 !important;
            border-top:1px solid var(--staff-border) !important;
            border-radius:0 !important;
            padding:8px 12px max(8px,env(safe-area-inset-bottom)) !important;
        }
    </style>
</head>
<body class="panel-shell-body staff-ui intake-frame">
    <main class="page-content">@yield('content')</main>
@include('staff.payments._workflow_styles')
</body>
</html>
