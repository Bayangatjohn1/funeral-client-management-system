<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (function () {
            const stored = localStorage.getItem('app-theme');
            const theme = stored === 'dark' || stored === 'light' ? stored : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <title>@yield('page_title', 'Dashboard') - Sabangan Caguioa</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-shell-body">
    
    <div id="globalLoadingIndicator" class="hidden">
        <div class="loading-pill">
            <div class="spin"></div>
            Processing...
        </div>
    </div>

    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <div class="app-shell">
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa Logo">
                <div class="sidebar-brand-copy">
                    <div class="sidebar-brand-name">Sabangan Caguioa</div>
                    <div class="sidebar-brand-sub">Funeral Home System</div>
                </div>
            </div>

            <div class="sidebar-scroll">
                <nav class="sidebar-nav">
                    @include('partials.sidebar')
                </nav>
            </div>

            <div class="sidebar-footer">
                <div class="profile-pill">
                    <div class="profile-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>

                    <div class="profile-copy">
                        <div class="profile-name">{{ auth()->user()->name ?? 'System User' }}</div>
                        <div class="profile-role">{{ ucfirst(auth()->user()->role ?? 'User') }} Account</div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <div class="topbar-leading">
                    <button
                        type="button"
                        id="mobileSidebarToggle"
                        class="mobile-menu-btn"
                        aria-label="Open navigation"
                        aria-expanded="false"
                        aria-controls="appSidebar"
                    >
                        <i class="bi bi-list"></i>
                    </button>

                    <div class="topbar-heading">
                        <h1 class="topbar-title">@yield('page_title', 'Dashboard')</h1>
                    </div>
                </div>

                <div class="topbar-actions">
                    @yield('header_actions')

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

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('mobileSidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            const sidebar = document.getElementById('appSidebar');
            if (!toggle || !backdrop || !sidebar) return;

            const closeSidebar = () => {
                document.body.removeAttribute('data-sidebar-open');
                toggle.setAttribute('aria-expanded', 'false');
            };

            const openSidebar = () => {
                document.body.setAttribute('data-sidebar-open', 'true');
                toggle.setAttribute('aria-expanded', 'true');
            };

            toggle.addEventListener('click', () => {
                if (document.body.getAttribute('data-sidebar-open') === 'true') {
                    closeSidebar();
                    return;
                }
                openSidebar();
            });

            backdrop.addEventListener('click', closeSidebar);

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) closeSidebar();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeSidebar();
            });

            document.addEventListener('click', (event) => {
                if (window.innerWidth >= 1024) return;
                const navLink = event.target.closest('.sidebar a[href]');
                if (navLink) closeSidebar();
            });
        })();

        (function () {
            const indicator = document.getElementById('globalLoadingIndicator');
            if (!indicator) return;

            let showTimer = null;
            let safetyTimer = null;

            function hideLoading() {
                clearTimeout(showTimer);
                showTimer = null;
                clearTimeout(safetyTimer);
                safetyTimer = null;
                indicator.classList.add('hidden');
            }

            function showLoading() {
                if (!indicator.classList.contains('hidden')) return;
                indicator.classList.remove('hidden');
                safetyTimer = setTimeout(hideLoading, 10000);
            }

            function scheduleLoading() {
                hideLoading();
                showTimer = setTimeout(showLoading, 120);
            }

            function isNormalLeftClick(e) {
                return e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey;
            }

            document.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link || !isNormalLeftClick(e)) return;
                if (link.target === '_blank' || link.hasAttribute('download')) return;

                const href = link.getAttribute('href') || '';
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

                const targetUrl = new URL(link.href, window.location.href);
                if (targetUrl.origin !== window.location.origin) return;

                setTimeout(function () {
                    if (!e.defaultPrevented) scheduleLoading();
                }, 0);
            });

            document.addEventListener('submit', function (e) {
                setTimeout(function () {
                    if (!e.defaultPrevented) scheduleLoading();
                }, 0);
            });

            window.addEventListener('pageshow', hideLoading);
            window.addEventListener('load', hideLoading);
        })();

        (function () {
            const root = document.documentElement;
            const toggle = document.querySelector('[data-theme-toggle]');
            const label = document.querySelector('[data-theme-label]');
            if (!toggle || !label) return;

            const applyTheme = (theme, animate = false) => {
                if (animate) root.classList.add('theme-changing');

                root.setAttribute('data-theme', theme);
                localStorage.setItem('app-theme', theme);
                toggle.setAttribute('data-theme-state', theme);
                label.textContent = theme === 'dark' ? 'Dark' : 'Light';

                if (animate) {
                    window.setTimeout(() => {
                        root.classList.remove('theme-changing');
                    }, 260);
                }
            };

            const initialTheme = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            applyTheme(initialTheme);

            toggle.addEventListener('click', () => {
                const current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                toggle.classList.add('is-switching');
                applyTheme(next, true);

                window.setTimeout(() => {
                    toggle.classList.remove('is-switching');
                }, 260);
            });
        })();

        (function () {
            const scroller = document.querySelector('.sidebar-scroll');
            if (!scroller) return;
            const key = 'sidebar-scroll-top';
            const saved = localStorage.getItem(key);
            if (saved) scroller.scrollTop = parseInt(saved, 10) || 0;
            scroller.addEventListener('scroll', () => {
                localStorage.setItem(key, String(scroller.scrollTop));
            });
        })();
    </script>
</body>
</html>
