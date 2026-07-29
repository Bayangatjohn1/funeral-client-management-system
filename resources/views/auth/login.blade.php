<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Login | Sabangan Caguioa</title>
    <script>
        (function () {
            const stored = localStorage.getItem('app-theme');
            const theme = stored === 'dark' || stored === 'light' ? stored : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/login-logo.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('images/login-logo.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .login-page {
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:
                linear-gradient(90deg, rgba(62, 74, 61, 0.06) 0 1px, transparent 1px),
                linear-gradient(180deg, rgba(62, 74, 61, 0.05) 0 1px, transparent 1px),
                var(--color-bg-page);
            background-size: 44px 44px;
            color: var(--color-text-primary);
        }

        .login-page *,
        .login-page *::before,
        .login-page *::after {
            letter-spacing: 0;
        }

        @keyframes loginPanelEnter {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes loginBrandEnter {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 0.92fr) minmax(500px, 640px);
        }

        .login-brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: clamp(2rem, 4.2vw, 4rem);
            background:
                linear-gradient(145deg, rgba(47, 58, 46, 0.97), rgba(62, 74, 61, 0.94)),
                url("{{ asset('images/login-logo.png') }}");
            background-position: center;
            background-size: cover;
            color: #FAFAF7;
        }

        .login-brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(15, 23, 42, 0.22), rgba(15, 23, 42, 0.58)),
                repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.07) 0 1px, transparent 1px 14px);
        }

        .login-brand-panel > * {
            position: relative;
            z-index: 1;
        }

        .login-brand-lockup {
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: loginBrandEnter 260ms ease-out both;
        }

        .login-brand-logo,
        .login-mobile-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #FAFAF7;
            border: 1px solid rgba(255, 255, 255, 0.46);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.24);
        }

        .login-brand-logo img,
        .login-mobile-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 6px;
        }

        .login-brand-logo {
            width: 4rem;
            height: 4rem;
            padding: 0.35rem;
        }

        .login-brand-name {
            margin: 0;
            font-family: 'Syne', 'DM Sans', sans-serif;
            font-size: clamp(1.7rem, 3vw, 2.7rem);
            line-height: 1.08;
            color: #ffffff;
        }

        .login-brand-meta {
            margin-top: 0.35rem;
            color: rgba(250, 250, 247, 0.74);
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .login-brand-content {
            max-width: 600px;
            margin-block: clamp(2.25rem, 5vh, 3.5rem);
            animation: loginBrandEnter 300ms ease-out 60ms both;
        }

        .login-brand-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 2rem;
            padding: 0.35rem 0.7rem;
            border: 1px solid rgba(250, 250, 247, 0.18);
            border-radius: 8px;
            background: rgba(250, 250, 247, 0.1);
            color: rgba(250, 250, 247, 0.84);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .login-brand-kicker i {
            color: #D5B49A;
        }

        .login-brand-title {
            margin: 1.15rem 0 0;
            max-width: 620px;
            font-family: 'Syne', 'DM Sans', sans-serif;
            font-size: clamp(2.15rem, 4.2vw, 4.05rem);
            line-height: 1.05;
            color: #ffffff;
        }

        .login-brand-copy {
            max-width: 540px;
            margin-top: 1rem;
            color: rgba(250, 250, 247, 0.76);
            font-size: clamp(0.96rem, 1.35vw, 1.04rem);
            line-height: 1.7;
        }

        .login-ops-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            max-width: 640px;
            animation: loginBrandEnter 300ms ease-out 110ms both;
        }

        .login-ops-item {
            min-height: 4.85rem;
            padding: 0.9rem;
            border-radius: 8px;
            border: 1px solid rgba(250, 250, 247, 0.16);
            background: rgba(250, 250, 247, 0.1);
        }

        .login-ops-item i {
            display: block;
            margin-bottom: 0.55rem;
            color: #D5B49A;
            font-size: 1rem;
        }

        .login-ops-item span {
            display: block;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .login-form-panel {
            position: relative;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 3.5rem);
            background: transparent;
        }

        .login-theme-position {
            position: absolute;
            top: 1.25rem;
            right: clamp(1.5rem, 4vw, 3.5rem);
            z-index: 3;
            animation: loginPanelEnter 220ms ease-out both;
        }

        .login-card {
            width: min(100%, 480px);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            background: var(--color-bg-surface);
            box-shadow: 0 22px 60px rgba(62, 74, 61, 0.13);
            animation: loginPanelEnter 280ms ease-out 50ms both;
            will-change: opacity, transform;
        }

        .login-card-inner {
            padding: clamp(1.35rem, 4vw, 2.25rem);
        }

        .login-mobile-brand {
            display: none;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1.75rem;
        }

        .login-mobile-logo {
            width: 3rem;
            height: 3rem;
            padding: 0.25rem;
            border-color: var(--color-border);
            box-shadow: var(--shadow-sm);
        }

        .login-mobile-brand strong {
            display: block;
            color: var(--color-text-primary);
            font-size: 1rem;
        }

        .login-mobile-brand span {
            color: var(--color-text-secondary);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .login-form-title {
            margin: 0;
            font-family: 'Syne', 'DM Sans', sans-serif;
            font-size: clamp(1.85rem, 4vw, 2.35rem);
            line-height: 1.1;
            color: var(--color-text-primary);
        }

        .login-form-copy {
            margin: 0.65rem 0 0;
            color: var(--color-text-secondary);
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .login-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin-top: 1.35rem;
            padding: 0.85rem;
            border-radius: 8px;
            border: 1px solid rgba(111, 138, 109, 0.28);
            background: rgba(139, 154, 139, 0.14);
            color: var(--color-success-text);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .login-form {
            margin-top: 1.75rem;
            display: grid;
            gap: 1.1rem;
        }

        .login-field-group {
            display: grid;
            gap: 0.5rem;
        }

        .login-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .login-form-label {
            color: var(--color-text-primary);
            font-size: 0.86rem;
            font-weight: 700;
        }

        .login-reset-link {
            color: var(--color-primary);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .login-reset-link:hover {
            color: var(--color-primary-hover);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .login-field {
            position: relative;
        }

        .login-field-icon {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            display: inline-flex;
            width: 1.1rem;
            height: 1.1rem;
            transform: translateY(-50%);
            align-items: center;
            justify-content: center;
            color: var(--color-text-muted);
            pointer-events: none;
        }

        .login-field-icon i {
            font-size: 1rem;
            line-height: 1;
        }

        .login-field-input {
            width: 100%;
            min-height: 3.1rem;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            background: var(--color-bg-surface);
            color: var(--color-text-primary);
            font-size: 0.96rem;
            font-weight: 600;
            padding: 0.78rem 0.95rem 0.78rem 2.9rem;
            box-shadow: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .login-field-input::placeholder {
            color: var(--color-text-muted);
            font-weight: 500;
        }

        .login-field-input:hover {
            border-color: var(--color-border-strong);
        }

        .login-field-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-focus-ring);
            outline: none;
        }

        .login-field-input.password-input {
            padding-right: 3.25rem;
        }

        .login-field:focus-within .login-field-icon {
            color: var(--color-primary);
        }

        .login-toggle-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            transform: translateY(-50%);
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            color: var(--color-text-secondary);
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }

        .login-toggle-btn:hover,
        .login-toggle-btn:focus-visible {
            background: var(--color-bg-muted);
            border-color: var(--color-border);
            color: var(--color-primary);
            outline: none;
        }

        .login-error {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.05rem;
            color: var(--color-danger);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-top: 0.1rem;
        }

        .login-remember {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--color-text-secondary);
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
        }

        .login-remember input {
            width: 1rem;
            height: 1rem;
            min-height: 1rem;
            margin: 0;
            border-radius: 4px;
            border: 1px solid var(--color-border);
            accent-color: var(--color-primary);
            cursor: pointer;
        }

        .login-submit {
            display: inline-flex;
            min-height: 3.1rem;
            width: 100%;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            border-radius: 8px;
            border: 1px solid var(--color-primary);
            background: var(--color-primary);
            color: #FAFAF7;
            font-size: 0.92rem;
            font-weight: 700;
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.12s ease;
        }

        .login-submit:hover,
        .login-submit:focus-visible {
            background: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
            box-shadow: 0 14px 28px rgba(62, 74, 61, 0.22);
            outline: none;
        }

        .login-submit:active {
            transform: translateY(1px);
        }

        .login-support {
            margin-top: 1.4rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
            color: var(--color-text-muted);
            font-size: 0.78rem;
            line-height: 1.55;
            text-align: center;
        }

        .login-support strong {
            color: var(--color-text-secondary);
            font-weight: 700;
        }

        @media (max-width: 1024px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .login-brand-panel {
                display: none;
            }

            .login-form-panel {
                min-height: 100vh;
                align-items: flex-start;
                padding-top: 5.5rem;
            }

            .login-mobile-brand {
                display: flex;
            }
        }

        @media (max-height: 760px) and (min-width: 1025px) {
            .login-brand-panel {
                padding-block: 2rem;
            }

            .login-brand-content {
                margin-block: 2rem;
            }

            .login-brand-title {
                font-size: clamp(2rem, 3.6vw, 3.45rem);
            }

            .login-brand-copy {
                max-width: 500px;
                line-height: 1.6;
            }

            .login-ops-item {
                min-height: 4.35rem;
                padding: 0.75rem;
            }
        }

        @media (max-width: 560px) {
            .login-page {
                background-size: 34px 34px;
            }

            .login-form-panel {
                padding: 5rem 1rem 1.25rem;
            }

            .login-card-inner {
                padding: 1.2rem;
            }

            .login-options {
                align-items: flex-start;
                flex-direction: column;
            }

            .theme-toggle__meta {
                display: none;
            }
        }

        html[data-theme='dark'] .login-page {
            background:
                linear-gradient(90deg, rgba(148, 163, 184, 0.05) 0 1px, transparent 1px),
                linear-gradient(180deg, rgba(148, 163, 184, 0.04) 0 1px, transparent 1px),
                var(--color-bg-page);
        }

        html[data-theme='dark'] .login-form-panel {
            background: transparent;
        }

        html[data-theme='dark'] .login-field-input {
            background: var(--color-bg-surface);
        }

        @media (prefers-reduced-motion: reduce) {
            .login-brand-lockup,
            .login-brand-content,
            .login-ops-strip,
            .login-theme-position,
            .login-card {
                animation: none !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body class="login-page antialiased">
    <main class="login-shell">
        <section class="login-brand-panel" aria-label="Sabangan Caguioa portal overview">
            <div class="login-brand-lockup">
                <div class="login-brand-logo">
                    <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa Logo">
                </div>
                <div>
                    <h1 class="login-brand-name">Sabangan Caguioa</h1>
                    <p class="login-brand-meta">Funeral Home System</p>
                </div>
            </div>

            <div class="login-brand-content">
                <span class="login-brand-kicker">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    Secure staff portal
                </span>
                <h2 class="login-brand-title">Focused access for daily branch operations.</h2>
                <p class="login-brand-copy">
                    Manage cases, service schedules, records, and reports from a protected workspace built for staff, admin, and owner workflows.
                </p>
            </div>

            <div class="login-ops-strip" aria-label="System highlights">
                <div class="login-ops-item">
                    <i class="bi bi-folder2-open" aria-hidden="true"></i>
                    <span>Case records</span>
                </div>
                <div class="login-ops-item">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                    <span>Service schedules</span>
                </div>
                <div class="login-ops-item">
                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                    <span>Branch reports</span>
                </div>
            </div>
        </section>

        <section class="login-form-panel" aria-label="Login form">
            <div class="login-theme-position">
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">
                    <span class="theme-toggle__meta">
                        <span class="theme-toggle__eyebrow">Theme</span>
                        <span class="theme-toggle__value" data-theme-label>Light</span>
                    </span>
                    <span class="theme-toggle__switch" aria-hidden="true">
                        <span class="theme-toggle__sun"><i class="bi bi-brightness-high-fill"></i></span>
                        <span class="theme-toggle__moon"><i class="bi bi-moon-stars-fill"></i></span>
                        <span class="theme-toggle__thumb"></span>
                    </span>
                </button>
            </div>

            <div class="login-card">
                <div class="login-card-inner">
                    <div class="login-mobile-brand">
                        <div class="login-mobile-logo">
                            <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa Logo">
                        </div>
                        <div>
                            <strong>Sabangan Caguioa</strong>
                            <span>System Portal</span>
                        </div>
                    </div>

                    <header>
                        <h2 class="login-form-title">Welcome back</h2>
                        <p class="login-form-copy">Sign in with your assigned account to continue.</p>
                    </header>

                    @if (session('status'))
                        <div class="login-alert" role="status">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="login-form">
                        @csrf

                        <div class="login-field-group">
                            <div class="login-label-row">
                                <label for="email" class="login-form-label">Email address</label>
                            </div>
                            <div class="login-field">
                                <span class="login-field-icon">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    class="login-field-input"
                                    placeholder="name@example.com"
                                >
                            </div>
                            @error('email')
                                <p class="login-error">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="login-field-group">
                            <div class="login-label-row">
                                <label for="password" class="login-form-label">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="login-reset-link">
                                        Forgot password?
                                    </a>
                                @endif
                            </div>
                            <div class="login-field">
                                <span class="login-field-icon">
                                    <i class="bi bi-lock" aria-hidden="true"></i>
                                </span>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    class="login-field-input password-input"
                                    placeholder="Enter your password"
                                >
                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="login-toggle-btn"
                                    aria-label="Show password"
                                >
                                    <i id="togglePasswordIcon" class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="login-error">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="login-options">
                            <label for="remember" class="login-remember">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    name="remember"
                                    {{ old('remember') ? 'checked' : '' }}
                                >
                                <span>Keep me signed in</span>
                            </label>
                        </div>

                        <button type="submit" class="login-submit">
                            <span>Sign in</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="login-support">
                        <strong>&copy; {{ date('Y') }} Sabangan Caguioa.</strong>
                        Secure access is monitored.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <script>
        (function () {
            const toggle = document.getElementById('togglePassword');
            const icon = document.getElementById('togglePasswordIcon');
            const password = document.getElementById('password');

            if (!toggle || !password || !icon) {
                return;
            }

            toggle.addEventListener('click', function () {
                const show = password.type === 'password';
                password.type = show ? 'text' : 'password';
                icon.classList.toggle('bi-eye', !show);
                icon.classList.toggle('bi-eye-slash', show);
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
</body>
</html>
