<!DOCTYPE html>
@php
    $worklistStylesInHead = request()->routeIs('funeral-cases.index','clients.index','payments.history','admin.payments.index','admin.payment-monitoring','historical-cases.index','historical-cases.show');
    $useRecordSurfaces = $worklistStylesInHead || request()->routeIs('admin.cases.index','owner.history','admin.branches.index','admin.users.index','admin.service-management.index');
@endphp
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

            try {
                const raw = sessionStorage.getItem('sidebar-nav-entry');
                sessionStorage.removeItem('sidebar-nav-entry');
                const entry = raw ? JSON.parse(raw) : null;
                const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
                const currentUrl = currentPath + window.location.search;
                const targetPath = entry?.targetPath?.replace(/\/+$/, '') || '';
                const targetUrl = entry?.targetUrl || targetPath;
                const sourceUrl = entry?.sourceUrl || entry?.sourcePath;

                if (sourceUrl && sourceUrl !== currentUrl && (targetUrl === currentUrl || targetPath === currentPath)) {
                    document.documentElement.setAttribute('data-sidebar-nav-entry', 'true');
                    if (entry.revealContent) {
                        const root = document.documentElement;
                        root.setAttribute('data-staff-sidebar-entry', 'true');
                        document.addEventListener('DOMContentLoaded', () => {
                            root.setAttribute('data-staff-sidebar-ready', 'true');
                        }, { once: true });
                    }
                    if (entry.label) {
                        document.documentElement.setAttribute('data-sidebar-nav-label', entry.label);
                    }
                }
            } catch (_) {
                sessionStorage.removeItem('sidebar-nav-entry');
            }
        })();
    </script>

    <style>
        [data-page-context-toast] {
            display: none !important;
        }

        html[data-sidebar-nav-entry='true'] [data-global-page-context-toast] {
            display: flex !important;
        }

        /* Wait for the destination markup, inline styles and deferred setup. */
        html[data-staff-sidebar-entry='true']:not([data-staff-sidebar-ready]) body[data-sidebar-manual]
            :is(.page-content, [data-global-page-context-toast]) {
            visibility: hidden;
        }
    </style>

    <link href="{{ asset('vendor/offline/fonts.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/login-logo.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('images/login-logo.png') }}" type="image/png">

    <title>@yield('page_title', 'Dashboard') - Sabangan Caguioa</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Load the final title geometry before first paint. Keeping this at the
         end of <body> briefly exposed the legacy left-aligned header on reload. --}}
    @include('partials.page-title-surface')
    @if($worklistStylesInHead)
        @stack('worklist-styles')
        @include('partials.uniform_filter_styles')
        @include('partials.shared_filter_ui')
        @include('partials.record_surface_styles')
        @include('partials.record_action_menu_styles')
    @endif
</head>
<body class="panel-shell-body{{ $useRecordSurfaces ? ' record-surface-ui' : '' }}{{ auth()->user()?->role === 'staff' ? ' staff-ui' : '' }}{{ auth()->user()?->isBranchAdmin() ? ' branch-admin-ui' : '' }}{{ auth()->user()?->isSystemAdmin() ? ' system-admin-ui' : '' }}{{ auth()->user()?->isOwner() ? ' owner-ui' : '' }}" data-sidebar-manual>
    <script>
        (function () {
            const desktopMedia = window.matchMedia('(min-width: 1024px)');
            if (!desktopMedia.matches) return;

            const lockState = localStorage.getItem('sidebar-lock-open');
            if (lockState === 'true') {
                document.body.setAttribute('data-sidebar-locked', 'true');
                document.body.removeAttribute('data-sidebar-collapsed');
                return;
            }

            document.body.setAttribute('data-sidebar-collapsed', 'true');
        })();
    </script>

    <div id="globalLoadingIndicator" class="hidden">
        <div class="loading-pill">
            <div class="spin"></div>
            Processing...
        </div>
    </div>

    <div class="page-context-toast no-print" role="status" aria-live="polite" data-page-context-toast data-global-page-context-toast>
        <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
        <span data-page-context-toast-copy>You are viewing this page.</span>
        <button type="button" class="page-context-toast__close" aria-label="Dismiss page message" data-page-context-toast-close>
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
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
                <button
                    type="button"
                    class="sidebar-lock-btn"
                    data-sidebar-lock-toggle
                    aria-label="Lock sidebar open"
                    aria-pressed="false"
                    title="Lock sidebar open"
                >
                    <i class="bi bi-pin-angle" aria-hidden="true"></i>
                </button>
            </div>

            <div class="sidebar-scroll">
                <nav class="sidebar-nav">
                    @include('partials.sidebar')
                </nav>
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-account-row">
                    <button
                        type="button"
                        class="profile-pill profile-pill-toggle"
                        data-account-toggle
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-label="Open account menu"
                    >
                        <div class="profile-avatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>

                        <div class="profile-copy">
                            <div class="profile-name">{{ auth()->user()->name ?? 'System User' }}</div>
                            <div class="profile-role">{{ auth()->user()?->roleLabel() ?? ucfirst(auth()->user()->role ?? 'User') }} Account</div>
                        </div>
                        <i class="bi bi-chevron-up profile-pill-caret" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="sidebar-account-dropdown" data-account-dropdown hidden>
                    @if(!(auth()->user()?->role === 'staff' && request()->is('staff')))
                    <div class="sidebar-account-dropdown__section">
                        <p class="sidebar-account-dropdown__label">Appearance</p>
                        <button type="button" class="theme-toggle theme-toggle--sidebar" data-theme-toggle aria-label="Toggle color theme">
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
                    @endif

                    <div class="sidebar-account-dropdown__section">
                        <a href="{{ route('profile.edit') }}" class="sidebar-account-dropdown__logout" aria-label="My Profile">
                            <i class="bi bi-person-circle"></i>
                            <span>My Profile</span>
                        </a>
                    </div>

                    <div class="sidebar-account-dropdown__section">
                        <form method="POST" action="{{ route('logout') }}" class="sidebar-account-dropdown__logout-form">
                            @csrf
                            <button type="submit" class="sidebar-account-dropdown__logout" aria-label="Logout">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-area">
            @php
                $explicitHideLayoutTopbar = trim($__env->yieldContent('hide_layout_topbar')) === '1';
                $pageDesc = trim($__env->yieldContent('page_desc'));
                $topbarActions = trim($__env->yieldContent('topbar_actions'));
                $legacyHeaderActions = trim($__env->yieldContent('header_actions'));
                $headerNavigation = trim($__env->yieldContent('header_navigation'));
                $filterBar = trim($__env->yieldContent('filter_bar'));
                $suppressLayoutPageHeader = trim($__env->yieldContent('suppress_layout_page_header')) === '1';
                $authUser = auth()->user();
                $authRole = $authUser->role ?? null;
                $isStaffAdminChrome = $authUser && ($authUser?->isAdmin() || $authRole === 'staff');
                $isStaffAdminDashboard = request()->is('staff') || request()->is('admin');
                $hideLayoutTopbar = $explicitHideLayoutTopbar || $isStaffAdminChrome;
                $showInlinePageHeader = ! $suppressLayoutPageHeader
                    && ! $isStaffAdminDashboard
                    && (
                        $isStaffAdminChrome
                        || ($hideLayoutTopbar && trim($__env->yieldContent('page_title')) !== '')
                    );
                $showTopbarNotifications = ! $authUser?->isOwner();
                $notificationRouteName = match (true) {
                    $authUser?->isAdmin() => 'admin.reminders.index',
                    $authRole === 'staff' => 'staff.reminders.index',
                    default => null,
                };
                $notificationHref = $notificationRouteName ? route($notificationRouteName) : null;
                $isReminderPage = request()->routeIs('staff.reminders.index') || request()->routeIs('admin.reminders.index');
                $topbarNotifications = collect();
                $notificationCounts = ['all' => 0, 'due' => 0, 'today' => 0, 'upcoming' => 0];

                if ($showTopbarNotifications && auth()->check()) {
                    $payload = app(\App\Support\TopbarNotificationBuilder::class)->forUser($authUser, $authRole);
                    $topbarNotifications = collect($payload['items'] ?? []);
                    $notificationCounts = array_merge($notificationCounts, $payload['counts'] ?? []);
                }

                $notificationTotal = $notificationCounts['all'] ?? 0;
            @endphp

            @unless($hideLayoutTopbar)
            <header class="topbar panel-title-surface centered-page-header">
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
                        @if($pageDesc !== '')
                            <div class="topbar-desc">@yield('page_desc')</div>
                        @endif
                    </div>
                </div>

                <div class="topbar-actions">
                    @if($topbarActions !== '')
                        @yield('topbar_actions')
                    @elseif($legacyHeaderActions !== '')
                        @yield('header_actions')
                    @endif

                    @include('partials.topbar-notifications', [
                        'authUser' => $authUser,
                        'authRole' => $authRole,
                        'showTopbarNotifications' => $showTopbarNotifications,
                        'notificationRouteName' => $notificationRouteName,
                        'notificationHref' => $notificationHref,
                        'isReminderPage' => $isReminderPage,
                        'topbarNotifications' => $topbarNotifications,
                        'notificationCounts' => $notificationCounts,
                        'notificationTotal' => $notificationTotal,
                        'notificationPayload' => $payload ?? null,
                    ])

                </div>
            </header>

            @if($filterBar !== '')
                <div class="topbar-filter-bar">
                    @yield('filter_bar')
                </div>
            @endif
            @endunless

            @if($hideLayoutTopbar && ! $showInlinePageHeader && ! $isStaffAdminDashboard)
                <button
                    type="button"
                    id="mobileSidebarToggle"
                    class="mobile-menu-btn panel-floating-menu-btn"
                    aria-label="Open navigation"
                    aria-expanded="false"
                    aria-controls="appSidebar"
                >
                    <i class="bi bi-list"></i>
                </button>
            @endif

            <main class="page-content{{ request()->routeIs('historical-cases.index') ? ' historical-cases-shell' : '' }}">
                @if($showInlinePageHeader)
                    <div class="panel-page-header panel-title-surface{{ $headerNavigation !== '' ? ' has-header-navigation' : '' }}">
                        <div class="panel-page-header__leading">
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
                            @hasSection('page_back')
                                @yield('page_back')
                            @endif
                            <div class="panel-page-header__copy">
                                <h1>@yield('page_title', 'Dashboard')@hasSection('page_title_badge')<span class="panel-page-header__badge">@yield('page_title_badge')</span>@endif</h1>
                                @if($pageDesc !== '')
                                    <p>@yield('page_desc')</p>
                                @endif
                            </div>
                        </div>

                        @if($topbarActions !== '' || $legacyHeaderActions !== '')
                            <div class="panel-page-header__actions">
                                @if($topbarActions !== '')
                                    @yield('topbar_actions')
                                @else
                                    @yield('header_actions')
                                @endif
                            </div>
                        @endif

                        @if($headerNavigation !== '')
                            <div class="panel-page-header__navigation">
                                @yield('header_navigation')
                            </div>
                        @endif
                    </div>
                @endif
                @if(auth()->user()?->isSystemAdmin() && request('return_to') === url('/admin') && !request()->is('admin'))
                    <div class="px-4 py-2"><a class="btn btn-secondary" href="{{ url('/admin') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Dashboard</a></div>
                @endif
                @include('partials.branch-sync-status')
                @yield('content')
                @include('partials.panel_scroll_top')
                @unless($worklistStylesInHead)
                    @include('partials.uniform_filter_styles')
                    @include('partials.shared_filter_ui')
                    @if($useRecordSurfaces)
                        @include('partials.record_surface_styles')
                    @endif
                    @include('partials.record_action_menu_styles')
                @endunless
                @if(request()->routeIs('funeral-cases.index','admin.cases.index','owner.history'))
                    @include('partials.case_summary_drawer')
                @endif
                @if($isStaffAdminDashboard || request()->routeIs('owner.dashboard'))
                    @include('partials.dashboard_surfaces')
                @endif
            </main>
        </div>
    </div>

    @if(auth()->user()?->isStaff())
        @include('staff.intake.global-modal')
    @endif

    @if(session('personal_password_set'))
        @include('partials.password_changed_welcome')
    @endif

    @if(request()->routeIs('intake.saved') && session('intake_payment.id'))
        @include('staff.intake._payment-success')
    @endif

    @if(session('warning'))
        <div class="flash-warning">{{ session('warning') }}</div>
    @endif

    @if(auth()->user()?->isStaff() || auth()->user()?->isBranchAdmin() || auth()->user()?->isSystemAdmin())
        <div hidden data-workflow-toast-url="{{ route('payments.corrections.notices') }}"></div>
    @endif
    <script>
        (function () {
            const meta = {
                'flash-success': { icon: 'bi-check-circle-fill',        colorClass: 'flash-icon-success' },
                'flash-error':   { icon: 'bi-x-circle-fill',            colorClass: 'flash-icon-error'   },
                'flash-info':    { icon: 'bi-info-circle-fill',          colorClass: 'flash-icon-info'    },
                'flash-warning': { icon: 'bi-exclamation-triangle-fill', colorClass: 'flash-icon-warning' },
            };

            const toasts = [...document.querySelectorAll('.flash-success, .flash-error, .flash-info, .flash-warning')];
            function showToast(el, href) {
                let stack = document.querySelector('.flash-toast-stack');
                if (!stack) {
                    stack = document.createElement('div');
                    stack.className = 'flash-toast-stack';
                    stack.setAttribute('aria-live', 'polite');
                    document.body.appendChild(stack);
                }
                const type = Object.keys(meta).find((k) => el.classList.contains(k)) || 'flash-info';
                const { icon, colorClass } = meta[type];
                const customIcon = el.dataset.flashIcon || icon;

                const text = el.textContent.trim();

                const iconEl = document.createElement('i');
                iconEl.className = `bi ${customIcon} flash-icon ${colorClass}`;

                const body = document.createElement(href ? 'a' : 'span');
                if (href) body.href = href;
                body.className = 'flash-body';
                body.textContent = text;

                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'flash-close';
                closeBtn.setAttribute('aria-label', 'Dismiss');
                closeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';

                el.innerHTML = '';
                el.appendChild(iconEl);
                el.appendChild(body);
                el.appendChild(closeBtn);
                stack.appendChild(el);

                const dismiss = () => {
                    el.classList.remove('flash-show');
                    el.classList.add('flash-dismiss');
                    setTimeout(() => el.remove(), 250);
                };

                closeBtn.addEventListener('click', dismiss);
                let timer;
                const scheduleDismiss = () => {
                    clearTimeout(timer);
                    if (!href || !el.matches(':hover, :focus-within')) timer = setTimeout(dismiss, href ? 10000 : 5000);
                };
                scheduleDismiss();
                if (href) {
                    el.addEventListener('pointerenter', () => clearTimeout(timer));
                    el.addEventListener('pointerleave', scheduleDismiss);
                    el.addEventListener('focusin', () => clearTimeout(timer));
                    el.addEventListener('focusout', () => queueMicrotask(scheduleDismiss));
                }
                closeBtn.addEventListener('click', () => clearTimeout(timer));

                // Trigger entrance after a micro-frame so CSS transition fires
                requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('flash-show')));
            }
            window.showPanelToast = (message, tone = 'info', href) => {
                const toast = document.createElement('div');
                toast.className = Object.hasOwn(meta, 'flash-' + tone) ? 'flash-' + tone : 'flash-info';
                toast.dataset.workflowToast = tone;
                toast.textContent = message;
                showToast(toast, href);
            };
            toasts.forEach(toast => showToast(toast));
        })();
    </script>

    <script>
        (function () {
            const toggle = document.getElementById('mobileSidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            const sidebar = document.getElementById('appSidebar');
            const lockToggle = sidebar?.querySelector('[data-sidebar-lock-toggle]');
            const desktopMedia = window.matchMedia('(min-width: 1024px)');
            if (!backdrop || !sidebar) return;
            const lockStorageKey = 'sidebar-lock-open';

            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach((link) => {
                const label = link.querySelector('span')?.textContent?.trim();
                if (!label) return;
                link.setAttribute('data-nav-label', label);
                link.setAttribute('aria-label', label);
            });

            const railTooltip = document.createElement('div');
            railTooltip.className = 'sidebar-rail-tooltip';
            railTooltip.setAttribute('aria-hidden', 'true');
            document.body.appendChild(railTooltip);
            const hideRailTooltip = () => { railTooltip.hidden = true; };
            const showRailTooltip = (link) => {
                if (!desktopMedia.matches || !document.body.hasAttribute('data-sidebar-collapsed')) return;
                const label = link.getAttribute('data-nav-label');
                if (!label) return;
                const rect = link.getBoundingClientRect();
                railTooltip.textContent = label;
                railTooltip.style.left = `${rect.right + 10}px`;
                railTooltip.style.top = `${rect.top + rect.height / 2}px`;
                railTooltip.hidden = false;
            };
            hideRailTooltip();
            navLinks.forEach((link) => {
                link.addEventListener('mouseenter', () => showRailTooltip(link));
                link.addEventListener('mouseleave', hideRailTooltip);
                link.addEventListener('focus', () => showRailTooltip(link));
                link.addEventListener('blur', hideRailTooltip);
            });
            sidebar.querySelector('.sidebar-scroll')?.addEventListener('scroll', hideRailTooltip, { passive: true });

            const brandName = sidebar.querySelector('.sidebar-brand-name')?.textContent?.trim();
            const brand = sidebar.querySelector('.sidebar-brand');
            if (brand && brandName) {
                brand.setAttribute('title', brandName);
                brand.setAttribute('aria-label', brandName);
            }

            const closeSidebar = () => {
                hideRailTooltip();
                document.body.removeAttribute('data-sidebar-open');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            };

            const openSidebar = () => {
                document.body.setAttribute('data-sidebar-open', 'true');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
            };

            const isLockedOpen = () => localStorage.getItem(lockStorageKey) === 'true';

            const syncLockButton = () => {
                if (!lockToggle) return;

                const locked = document.body.hasAttribute('data-sidebar-locked');
                const manualSidebar = document.body.hasAttribute('data-sidebar-manual');
                lockToggle.setAttribute('aria-pressed', locked ? 'true' : 'false');
                const label = manualSidebar ? (locked ? 'Collapse sidebar' : 'Expand sidebar') : (locked ? 'Unlock sidebar' : 'Lock sidebar open');
                lockToggle.setAttribute('aria-label', label);
                lockToggle.setAttribute('title', label);
                if (manualSidebar) lockToggle.setAttribute('aria-expanded', String(locked));

                const icon = lockToggle.querySelector('i');
                if (icon) {
                    icon.className = manualSidebar ? (locked ? 'bi bi-layout-sidebar-inset' : 'bi bi-layout-sidebar') : (locked ? 'bi bi-pin-angle-fill' : 'bi bi-pin-angle');
                }
            };

            const setSidebarLocked = (locked, persist = true) => {
                hideRailTooltip();
                if (!desktopMedia.matches) {
                    document.body.removeAttribute('data-sidebar-locked');
                    syncLockButton();
                    return;
                }

                if (locked) {
                    document.body.setAttribute('data-sidebar-locked', 'true');
                } else {
                    document.body.removeAttribute('data-sidebar-locked');
                }
                document.body.removeAttribute('data-sidebar-hovered');

                if (locked) {
                    document.body.removeAttribute('data-sidebar-collapsed');
                } else {
                    document.body.setAttribute('data-sidebar-collapsed', 'true');
                }

                if (persist) {
                    localStorage.setItem(lockStorageKey, locked ? 'true' : 'false');
                }

                syncLockButton();
            };

            const syncDesktopCollapsed = () => {
                if (!desktopMedia.matches) {
                    document.body.removeAttribute('data-sidebar-collapsed');
                    document.body.removeAttribute('data-sidebar-locked');
                    syncLockButton();
                    return;
                }

                setSidebarLocked(isLockedOpen(), false);
            };

            if (toggle) {
                toggle.addEventListener('click', () => {
                    if (desktopMedia.matches) return;

                    if (document.body.getAttribute('data-sidebar-open') === 'true') {
                        closeSidebar();
                        return;
                    }
                    openSidebar();
                });
            }

            lockToggle?.addEventListener('click', () => {
                setSidebarLocked(!document.body.hasAttribute('data-sidebar-locked'));
            });

            syncDesktopCollapsed();
            syncLockButton();

            backdrop.addEventListener('click', closeSidebar);

            window.addEventListener('resize', () => {
                if (desktopMedia.matches) {
                    closeSidebar();
                    syncDesktopCollapsed();
                    return;
                }

                document.body.removeAttribute('data-sidebar-collapsed');
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !document.getElementById('staff-intake-dialog')?.open) closeSidebar();
            });

            document.addEventListener('click', (event) => {
                const sidebarNavLink = event.target.closest('.sidebar a[href]');
                if (event.defaultPrevented && sidebarNavLink?.hasAttribute('data-intake-trigger')) {
                    hideRailTooltip();
                    return;
                }
                if (sidebarNavLink && !event.defaultPrevented && event.button === 0 &&
                    !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey &&
                    (!sidebarNavLink.target || sidebarNavLink.target === '_self')) {
                    try {
                        const target = new URL(sidebarNavLink.getAttribute('href'), window.location.href);
                        const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
                        const currentUrl = currentPath + window.location.search;
                        const targetPath = target.pathname.replace(/\/+$/, '') || '/';
                        const targetUrl = targetPath + target.search;

                        if (
                            target.origin === window.location.origin &&
                            !sidebarNavLink.hasAttribute('download') &&
                            targetUrl !== currentUrl &&
                            !sidebarNavLink.getAttribute('href').startsWith('#')
                        ) {
                            const label = sidebarNavLink.getAttribute('data-nav-label')
                                || sidebarNavLink.querySelector('span')?.textContent?.trim()
                                || sidebarNavLink.textContent?.trim()
                                || '';
                            sessionStorage.setItem('sidebar-nav-entry', JSON.stringify({
                                sourcePath: currentPath,
                                sourceUrl: currentUrl,
                                targetPath,
                                targetUrl,
                                label,
                                revealContent: true,
                            }));
                        }
                    } catch (_) {}
                }

                if (window.innerWidth >= 1024) return;
                const navLink = sidebarNavLink;
                if (navLink) closeSidebar();
            });
        })();

        // Authenticated pages must not be prefetched: GET requests can consume session flash messages.

        (function () {
            const indicator = document.getElementById('globalLoadingIndicator');
            if (!indicator) return;
            let finishPageSkeleton;

            function hideLoading() {
                finishPageSkeleton?.(); finishPageSkeleton = null;
                indicator.classList.add('hidden');
            }

            function startPageTransition(destination) {
                hideLoading();
                finishPageSkeleton = window.PanelSkeleton?.begin(document.querySelector('.page-content'), {immediate:true, variant:'navigation', destination});
            }

            const historicalPageSelector = '.historical-cases-page, .records-page, .master-records-page';
            let historicalNavigationPending = false;
            const applyHistoricalRouteStyle = (style) => {
                document.head.querySelector('[data-historical-route-style]')?.remove();
                if (style) document.head.appendChild(style.cloneNode(true));
            };
            const executeHistoricalPageScripts = (page) => {
                page.querySelectorAll('script:not([src])').forEach(oldScript => {
                    const type = (oldScript.getAttribute('type') || 'text/javascript').toLowerCase();
                    if (!['text/javascript', 'application/javascript', 'module'].includes(type)) return;
                    const liveScript = document.createElement('script');
                    for (const attribute of oldScript.attributes) liveScript.setAttribute(attribute.name, attribute.value);
                    liveScript.textContent = oldScript.textContent;
                    oldScript.replaceWith(liveScript);
                });
            };
            const swapHistoricalPage = async (entry, url, { push = false, sourceUrl = null } = {}) => {
                if (!entry?.page) return false;
                const commit = () => {
                    document.querySelector('.page-content')?.replaceWith(entry.page);
                    document.title = entry.title;
                    applyHistoricalRouteStyle(entry.style);
                    if (push) {
                        const current = sourceUrl || window.location.href;
                        history.replaceState({ ...(history.state || {}), __historicalCasesPage: true }, '', current);
                        history.pushState({ __historicalCasesPage: true, from: current }, '', url);
                    }
                    executeHistoricalPageScripts(entry.page);
                    window.scrollTo({ top: 0, behavior: 'auto' });
                };
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                document.documentElement.classList.add('historical-route-switching');
                try {
                    if (!reduceMotion && typeof document.startViewTransition === 'function') {
                        await document.startViewTransition(commit).finished;
                    } else {
                        commit();
                    }
                } finally {
                    document.documentElement.classList.remove('historical-route-switching');
                }
                return true;
            };
            const fetchHistoricalPageEntry = async (targetUrl) => {
                const response = await fetch(targetUrl, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                if (!response.ok) throw new Error('Historical navigation failed');
                const html = await response.text();
                const destination = new DOMParser().parseFromString(html, 'text/html');
                const nextPage = destination.querySelector('.page-content');
                if (!nextPage || !nextPage.querySelector(historicalPageSelector)) throw new Error('Destination page is not supported for partial navigation');
                return {
                    page: document.importNode(nextPage, true),
                    title: destination.title || document.title,
                    style: destination.head.querySelector('[data-historical-route-style]')?.cloneNode(true) ?? null,
                };
            };
            const navigateHistoricalPage = async (url, { backHref = null } = {}) => {
                const target = new URL(url, window.location.href);
                const targetUrl = target.href;
                const currentUrl = window.location.href;
                if (targetUrl === currentUrl || historicalNavigationPending) return;

                if (backHref && history.state?.__historicalCasesPage && history.state.from) {
                    history.back();
                    return;
                }

                historicalNavigationPending = true;
                try {
                    const entry = await fetchHistoricalPageEntry(targetUrl);
                    await swapHistoricalPage(entry, targetUrl, { push: true, sourceUrl: currentUrl });
                } catch (_) {
                    window.location.assign(targetUrl);
                } finally {
                    historicalNavigationPending = false;
                }
            };

            document.addEventListener('click', event => {
                const target = event.target instanceof Element ? event.target : null;
                const link = target?.closest('a[data-historical-transition][href]');
                if (!link || !isNormalLeftClick(event) || link.target || link.hasAttribute('download')) return;
                if (!isSameOriginLink(link)) return;
                event.preventDefault();
                navigateHistoricalPage(link.href, { backHref: link.classList.contains('hc-back-link') ? link.href : null });
            }, true);

            window.addEventListener('popstate', event => {
                const url = window.location.href;
                if (!event.state?.__historicalCasesPage) return;
                event.stopImmediatePropagation();
                fetchHistoricalPageEntry(url)
                    .then(entry => swapHistoricalPage(entry, url))
                    .catch(() => window.location.assign(url));
            }, true);

            function isNormalLeftClick(e) {
                return e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey;
            }

            function shouldIgnoreNavigationLink(link, e) {
                if (e.defaultPrevented || !link || !isNormalLeftClick(e)) return true;
                // Historical Cases swaps only its page content; it does not use
                // the generic full-page navigation skeleton.
                if (link.hasAttribute('data-historical-transition')) return true;
                if (link.closest('[role="tablist"], [role="tab"], .case-records-tabs, .table-quick-tabs, .table-system-quick-tabs, .service-hub-tabs, .pm-tabs, [data-pm-section-action], .rp-tabs-row, .schedule-section-tabs, .upcoming-view-switch, .reports-module-tabs, .reports-module-subtabs')) return true;
                if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-service-form-cancel')) return true;

                const href = link.getAttribute('href') || '';
                try {
                    const url = new URL(link.href, window.location.href);
                    if (/\/(?:print|export|download)(?:\/|$)|\.(?:pdf|csv|xlsx?|docx?)$/i.test(url.pathname)) return true;
                } catch (_) { return true; }
                return !href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:');
            }

            function isSameOriginLink(link) {
                try {
                    return new URL(link.href, window.location.href).origin === window.location.origin;
                } catch (error) {
                    return false;
                }
            }

            function startNavTransition(link) {
                const targetUrl = new URL(link.href, window.location.href);
                if (targetUrl.href === window.location.href) return false;

                startPageTransition(targetUrl.href);
                // Let the browser paint the immediate skeleton before leaving
                // this document. This is a paint boundary, not a fixed wait.
                let dispatched = false;
                const navigate = () => {
                    if (dispatched) return;
                    dispatched = true;
                    window.location.href = targetUrl.href;
                };
                requestAnimationFrame(() => requestAnimationFrame(navigate));
                // Background tabs can suspend animation frames; never leave
                // navigation waiting indefinitely for a compositor frame.
                window.setTimeout(navigate, 100);

                return true;
            }

            // Programmatic navigations (clickable rows and request fallbacks)
            // use the same skeleton/view-transition owner as ordinary links.
            window.SabanganNavigate = function (href) {
                try {
                    const link = document.createElement('a');
                    link.href = new URL(href, window.location.href).href;
                    if (!isSameOriginLink(link)) return false;
                    return startNavTransition(link);
                } catch (_) {
                    return false;
                }
            };

            // Page-specific document handlers get first refusal (including
            // handlers registered later by deferred modules).
            window.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (shouldIgnoreNavigationLink(link, e)) return;
                if (!isSameOriginLink(link)) return;
                if (link.closest('.sidebar')) return;

                if (startNavTransition(link)) {
                    e.preventDefault();
                }
            });

            document.addEventListener('submit', function (e) {
                if (!(e.target instanceof HTMLFormElement) || e.target.method.toLowerCase() !== 'get') return;
                setTimeout(function () {
                    if (!e.defaultPrevented) startPageTransition(e.target.action);
                }, 0);
            });

            window.addEventListener('pageshow', hideLoading);
            window.addEventListener('load', function () {
                hideLoading();
                if (document.documentElement.getAttribute('data-sidebar-nav-entry') !== 'true') return;
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        document.documentElement.setAttribute('data-sidebar-nav-settled', 'true');
                    });
                });
            });
        })();

        (function () {
            let dismissTimer = null;

            const buildToast = () => {
                const existing = document.querySelector('[data-global-page-context-toast]');
                if (existing) return existing;

                const toast = document.createElement('div');
                toast.className = 'page-context-toast no-print';
                toast.setAttribute('role', 'status');
                toast.setAttribute('aria-live', 'polite');
                toast.setAttribute('data-page-context-toast', '');
                toast.setAttribute('data-global-page-context-toast', '');
                toast.innerHTML = `
                    <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                    <span data-page-context-toast-copy>You are viewing this page.</span>
                    <button type="button" class="page-context-toast__close" aria-label="Dismiss page message" data-page-context-toast-close>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                `;
                document.body.appendChild(toast);
                return toast;
            };

            const normalizeLabel = (value) => (value || document.title.split(' - ')[0] || 'this page')
                .replace(/\s+/g, ' ')
                .trim() || 'this page';

            const show = (rawLabel = null) => {
                const toast = buildToast();
                const copy = toast.querySelector('[data-page-context-toast-copy]');
                const close = toast.querySelector('[data-page-context-toast-close]');
                const label = normalizeLabel(rawLabel);

                if (copy) {
                    copy.textContent = `You are viewing ${label}.`;
                }

                document.documentElement.setAttribute('data-sidebar-nav-entry', 'true');
                document.documentElement.setAttribute('data-sidebar-nav-toast-ready', 'true');
                document.documentElement.setAttribute('data-sidebar-nav-label', label);

                const dismiss = () => {
                    toast.classList.remove('is-visible');
                    toast.classList.add('is-leaving');
                    window.setTimeout(() => toast.remove(), 180);
                };

                close?.addEventListener('click', dismiss, { once: true });
                window.clearTimeout(dismissTimer);
                toast.classList.remove('is-leaving');
                if (document.documentElement.hasAttribute('data-staff-sidebar-entry')) {
                    toast.classList.add('is-visible');
                } else {
                    window.setTimeout(() => toast.classList.add('is-visible'), 60);
                }
                dismissTimer = window.setTimeout(dismiss, 3600);
            };

            window.SabanganPageContextToast = { show };

            if (document.documentElement.getAttribute('data-sidebar-nav-entry') === 'true') {
                show(document.documentElement.getAttribute('data-sidebar-nav-label'));
            }
        })();

        (function () {
            const wrapperSelector = [
                '.pm-field.has-icon',
                '.pf-control-wrap',
                '.aoc-select-wrap',
                '.admin-filter-control',
                '.audit-filter-control.is-select',
                '.sales-filter-control',
                '.owner-sales-field-control',
                '.rp-field',
                '.reports-module-control-wrap',
                '.reports-field-control',
                '.reports-analytics-branch',
                '.ba-branch-select-wrap',
                '.ba-period-select-wrap',
                '.eb-branch-select-wrap',
                '.eb-period-select-wrap',
                '.table-toolbar-select-wrap',
                '.global-filter-select-wrap',
                '.case-compact-select-wrap',
                '.case-compact-date-filter',
                '.case-compact-sort-filter',
                '.case-compact-branch',
            ].join(',');

            const getWrapper = (target) => target?.matches?.(wrapperSelector)
                ? target
                : target?.closest?.(wrapperSelector);

            const closeOtherWrappers = (activeWrapper = null) => {
                document.querySelectorAll(wrapperSelector).forEach((wrapper) => {
                    if (wrapper !== activeWrapper) {
                        wrapper.classList.remove('is-open');
                    }
                });
            };

            const setOpen = (target, open) => {
                const wrapper = getWrapper(target);
                if (!wrapper || !wrapper.querySelector('select')) return;
                if (wrapper.hasAttribute('data-filter-select-wrap') || wrapper.querySelector('.filter-select-menu')) return;
                if (open) closeOtherWrappers(wrapper);
                wrapper.classList.toggle('is-open', open);
            };

            document.addEventListener('click', (event) => {
                if (!getWrapper(event.target)) {
                    closeOtherWrappers();
                }
            });
            document.addEventListener('focusin', (event) => setOpen(event.target, true));
            document.addEventListener('change', (event) => setOpen(event.target, false));
            document.addEventListener('blur', (event) => {
                window.setTimeout(() => setOpen(event.target, false), 0);
            }, true);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' || event.key === 'Tab' || event.key === 'Enter') {
                    setOpen(event.target, false);
                }
            }, true);
            window.addEventListener('pageshow', () => closeOtherWrappers());
        })();

        (function () {
            const toggle = document.querySelector('[data-account-toggle]');
            const menu = document.querySelector('[data-account-dropdown]');
            if (!toggle || !menu) return;

            const closeMenu = () => {
                toggle.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
            };

            const openMenu = () => {
                toggle.setAttribute('aria-expanded', 'true');
                menu.hidden = false;
            };

            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                if (menu.hidden) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });

            document.addEventListener('click', (event) => {
                if (!toggle.contains(event.target) && !menu.contains(event.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            document.addEventListener('panel-ui:reset', closeMenu);
        })();

        (function () {
            const storageKey = 'admin-service-management-open';
            document.querySelectorAll('[data-service-nav]').forEach((nav) => {
                const parent = nav.querySelector('[data-service-nav-parent]');
                const submenu = nav.querySelector('[data-service-nav-submenu]');
                if (!parent || !submenu) return;

                const isActive = nav.getAttribute('data-service-active') === '1';
                const stored = localStorage.getItem(storageKey);
                const shouldOpen = isActive ? true : (stored === null || stored === 'true');

                const setOpen = (open, persist = true) => {
                    submenu.classList.toggle('is-expanded', open);
                    nav.classList.toggle('is-open', open);
                    parent.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (persist) localStorage.setItem(storageKey, open ? 'true' : 'false');
                };

                setOpen(shouldOpen, isActive);

                parent.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (document.body.getAttribute('data-sidebar-collapsed') === 'true'
                        && document.body.getAttribute('data-sidebar-hovered') !== 'true') {
                        setOpen(false, false);
                        return;
                    }
                    setOpen(!submenu.classList.contains('is-expanded'));
                });

                parent.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    if (document.body.getAttribute('data-sidebar-collapsed') === 'true'
                        && document.body.getAttribute('data-sidebar-hovered') !== 'true') {
                        setOpen(false, false);
                        return;
                    }
                    setOpen(!submenu.classList.contains('is-expanded'));
                });

                submenu.querySelectorAll('a[href]').forEach((link) => {
                    link.addEventListener('click', () => setOpen(true));
                });
            });
        })();

        (function () {
            const wrap = document.querySelector('[data-notification]');
            const toggle = document.querySelector('[data-notification-toggle]');
            const menu = document.querySelector('[data-notification-menu]');
            if (!wrap || !toggle || !menu) return;
            const filterButtons = [...menu.querySelectorAll('[data-notification-filter]')];
            const cards = () => [...menu.querySelectorAll('[data-notification-item]')];
            const emptyState = menu.querySelector('[data-notification-empty]') || menu.querySelector('.topbar-notification-empty');
            const markReadBtn = menu.querySelector('[data-notification-mark-read]');
            let countBadge = toggle.querySelector('.topbar-notification__count');
            const countEls = [...menu.querySelectorAll('[data-notification-count]')];
            const summary = menu.querySelector('[data-notification-summary]');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const stateUrl = wrap.dataset.notificationStateUrl;
            const readAllUrl = wrap.dataset.notificationReadAllUrl;
            const notificationTones = ['danger', 'warning', 'info', 'plan', 'success', 'neutral'];

            const setBellTone = (tone = 'neutral') => {
                notificationTones.forEach((name) => toggle.classList.remove(`tone-${name}`));
                toggle.classList.add(`tone-${notificationTones.includes(tone) ? tone : 'neutral'}`);
            };

            const syncBellTone = () => {
                const unreadEntries = [...menu.querySelectorAll('[data-notification-entry][data-unread="true"]')];
                const tone = notificationTones.find((name) => unreadEntries.some((entry) => entry.classList.contains(`tone-${name}`))) || 'neutral';
                setBellTone(tone);
            };

            const postState = async (url, payload = {}, keepalive = false) => {
                if (!url) return null;
                const response = await fetch(url, {
                    method: 'POST',
                    keepalive,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                if (!response.ok) throw new Error('Notification update failed');
                return response.json();
            };

            const setUnreadCount = (unread) => {
                unread = Math.max(0, Number(unread) || 0);
                toggle.dataset.unreadTotal = String(unread);
                if (unread > 0) {
                    if (!countBadge) {
                        countBadge = document.createElement('span');
                        countBadge.className = 'topbar-notification__count';
                        countBadge.setAttribute('aria-hidden', 'true');
                        toggle.appendChild(countBadge);
                    }
                    countBadge.textContent = String(Math.min(unread, 99));
                } else {
                    countBadge?.remove();
                    countBadge = null;
                    setBellTone('neutral');
                }
                toggle.setAttribute('aria-label', unread > 0 ? `Notifications, ${unread} unread` : 'Notifications');
                if (summary) summary.textContent = unread > 0 ? `${unread} unread update${unread === 1 ? '' : 's'}` : 'All caught up';
                if (markReadBtn) markReadBtn.disabled = unread === 0;
            };

            const positionNotificationMenu = () => {
                if (menu.hidden || !window.matchMedia('(max-width: 640px)').matches) return;
                const top = Math.max(12, Math.min(toggle.getBoundingClientRect().bottom + 8, window.innerHeight - menu.offsetHeight - 12));
                menu.style.setProperty('--notification-top', `${top}px`);
            };

            window.addEventListener('resize', positionNotificationMenu, { passive: true });
            window.addEventListener('scroll', positionNotificationMenu, { passive: true, capture: true });

            const updateVisibleState = (bucket = 'all') => {
                cards().forEach((card) => {
                    const cardBuckets = (card.getAttribute('data-buckets') || card.getAttribute('data-bucket') || '').split(/\s+/);
                    const show = bucket === 'all' || cardBuckets.includes(bucket);
                    card.hidden = !show;
                });

                const visibleCount = cards().filter((card) => !card.hidden).length;
                if (emptyState) {
                    emptyState.hidden = visibleCount > 0;
                }
            };

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const bucket = button.getAttribute('data-notification-filter') || 'all';
                    filterButtons.forEach((btn) => btn.classList.remove('is-active'));
                    button.classList.add('is-active');
                    updateVisibleState(bucket);
                });
            });

            if (markReadBtn) {
                markReadBtn.addEventListener('click', async () => {
                    markReadBtn.disabled = true;
                    try {
                        const result = await postState(readAllUrl, { branch_id: wrap.dataset.notificationBranchId || null });
                        menu.querySelectorAll('[data-notification-entry]').forEach((entry) => {
                            entry.classList.add('is-read');
                            entry.dataset.unread = 'false';
                        });
                        toggle.dataset.reminders = '0';
                        toggle.dataset.reminderTone = 'neutral';
                        countEls.forEach((el) => { el.textContent = '0'; });
                        setUnreadCount(result?.unread ?? 0);
                        syncBellTone();
                    } catch (_) {
                        markReadBtn.disabled = false;
                    }
                });
            }

            menu.addEventListener('click', async (event) => {
                const dismiss = event.target.closest('[data-notification-dismiss]');
                const open = event.target.closest('[data-notification-open]');
                const entry = event.target.closest('[data-notification-entry]');
                const key = entry?.dataset.notificationKey;
                if (!entry || !key || (!dismiss && !open)) return;

                const payload = { key, action: dismiss ? 'dismiss' : 'read', branch_id: wrap.dataset.notificationBranchId || null };
                if (dismiss) {
                    event.preventDefault();
                    event.stopPropagation();
                    dismiss.disabled = true;
                    const wasUnread = entry.dataset.unread === 'true';
                    try {
                        const result = await postState(stateUrl, payload);
                        if (wasUnread && key.startsWith('reminder:')) {
                            toggle.dataset.reminders = String(Math.max(0, (Number(toggle.dataset.reminders) || 0) - 1));
                        }
                        entry.remove();
                        setUnreadCount(result?.unread ?? 0);
                        syncBellTone();
                        updateVisibleState('all');
                    } catch (_) {
                        dismiss.disabled = false;
                    }
                    return;
                }

                if (entry.dataset.unread === 'true') {
                    entry.classList.add('is-read');
                    entry.dataset.unread = 'false';
                    if (key.startsWith('reminder:')) {
                        toggle.dataset.reminders = String(Math.max(0, (Number(toggle.dataset.reminders) || 0) - 1));
                    }
                    const current = Number(toggle.dataset.unreadTotal || countBadge?.textContent || 0);
                    setUnreadCount(Math.max(0, current - 1));
                    syncBellTone();
                    postState(stateUrl, payload, true).catch(() => {});
                }
            });

            const closeMenu = () => {
                toggle.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
            };

            const openMenu = () => {
                toggle.setAttribute('aria-expanded', 'true');
                menu.hidden = false;
                positionNotificationMenu();
            };

            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                if (menu.hidden) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });

            document.addEventListener('click', (event) => {
                if (!wrap.contains(event.target)) {
                    closeMenu();
                }
            });

            window.addEventListener('notification:tone-sync', syncBellTone);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            document.addEventListener('panel-ui:reset', closeMenu);

            updateVisibleState('all');
        })();

        (function () {
            // JS-driven sidebar hover-expand so the gap between sidebar and
            // the fly-out dropdown never causes an accidental collapse.
            if (document.body.hasAttribute('data-sidebar-manual')) return;
            const sidebar   = document.getElementById('appSidebar');
            const desktop   = window.matchMedia('(min-width: 1024px)');
            if (!sidebar) return;

            const ATTR        = 'data-sidebar-hovered';
            const LEAVE_DELAY = 200; // ms — enough to cross the gap
            let leaveTimer    = null;

            const isCollapsed    = () => document.body.getAttribute('data-sidebar-collapsed') === 'true';
            const isDropdownOpen = () => {
                const dd = sidebar.querySelector('[data-account-dropdown]');
                return dd ? !dd.hidden : false;
            };

            const enter = () => {
                if (!desktop.matches || !isCollapsed()) return;
                clearTimeout(leaveTimer);
                document.body.setAttribute(ATTR, 'true');
            };

            const leave = () => {
                clearTimeout(leaveTimer);
                leaveTimer = setTimeout(() => {
                    if (isDropdownOpen()) return; // keep open while menu is visible
                    document.body.removeAttribute(ATTR);
                }, LEAVE_DELAY);
            };

            sidebar.addEventListener('mouseenter', enter);
            sidebar.addEventListener('mouseleave', leave);

            // When the account dropdown is closed and mouse is no longer on sidebar, collapse
            const accountDropdown = sidebar.querySelector('[data-account-dropdown]');
            if (accountDropdown) {
                new MutationObserver(() => {
                    if (!isDropdownOpen() && !sidebar.matches(':hover')) {
                        clearTimeout(leaveTimer);
                        document.body.removeAttribute(ATTR);
                    }
                }).observe(accountDropdown, { attributes: true, attributeFilter: ['hidden'] });
            }

            // Remove hover state when sidebar is manually expanded via toggle button
            new MutationObserver(() => {
                if (!isCollapsed()) {
                    clearTimeout(leaveTimer);
                    document.body.removeAttribute(ATTR);
                }
            }).observe(document.body, { attributes: true, attributeFilter: ['data-sidebar-collapsed'] });
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

@include('partials.idle_session')
@include('staff.payments._workflow_styles')
@if(auth()->user()?->isOwner())
@include('owner.partials.visual-reference')
@endif
</body>
</html>
