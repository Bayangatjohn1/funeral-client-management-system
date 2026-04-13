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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/login-logo.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('images/login-logo.png') }}" type="image/png">

    <title>@yield('page_title', 'Dashboard') - Sabangan Caguioa</title>

    <style>
        .flash-toast-warning {
            position: fixed;
            left: 50%;
            top: 18px;
            transform: translateX(-50%);
            z-index: 1100;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 16px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            font-weight: 700;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            opacity: 0;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        .flash-toast-warning.show {
            opacity: 1;
            transform: translate(-50%, 0);
        }
        .flash-toast-warning i {
            font-size: 1.05rem;
        }
    </style>

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

            <button
                type="button"
                id="desktopSidebarToggle"
                class="sidebar-collapse-btn"
                aria-label="Collapse sidebar"
                aria-expanded="true"
                aria-controls="appSidebar"
            >
                <i class="bi bi-caret-left-fill sidebar-collapse-glyph" aria-hidden="true"></i>
            </button>

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
                            <div class="profile-role">{{ ucfirst(auth()->user()->role ?? 'User') }} Account</div>
                        </div>
                        <i class="bi bi-chevron-up profile-pill-caret" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="sidebar-account-dropdown" data-account-dropdown hidden>
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
                $hideLayoutTopbar = trim($__env->yieldContent('hide_layout_topbar')) === '1';
                $pageDesc = trim($__env->yieldContent('page_desc'));
                $topbarActions = trim($__env->yieldContent('topbar_actions'));
                $legacyHeaderActions = trim($__env->yieldContent('header_actions'));
                $filterBar = trim($__env->yieldContent('filter_bar'));
                $authRole = auth()->user()->role ?? null;
                $notificationRouteName = match ($authRole) {
                    'staff' => 'staff.reminders.index',
                    'admin' => 'admin.reminders.index',
                    default => null,
                };
                $notificationHref = $notificationRouteName ? route($notificationRouteName) : null;
                $isReminderPage = request()->routeIs('staff.reminders.index') || request()->routeIs('admin.reminders.index');
                $topbarNotifications = collect();
                $notificationCounts = ['all' => 0, 'due' => 0, 'today' => 0, 'upcoming' => 0];

                if (auth()->check()) {
                    $authUser = auth()->user();
                    $scopeBranchIds = [];

                    if ($authRole === 'staff') {
                        $scopeBranchIds = method_exists($authUser, 'branchScopeIds')
                            ? $authUser->branchScopeIds()
                            : [];

                        $mainBranchId = (int) \App\Models\Branch::query()
                            ->whereIn('id', $scopeBranchIds)
                            ->where('branch_code', 'BR001')
                            ->value('id');

                        if ($mainBranchId > 0) {
                            $scopeBranchIds = [$mainBranchId];
                        } elseif (!empty($authUser->branch_id)) {
                            $scopeBranchIds = [(int) $authUser->branch_id];
                        }
                    } elseif (in_array($authRole, ['admin', 'owner'], true)) {
                        $scopeBranchIds = \App\Models\Branch::query()
                            ->where('is_active', true)
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->all();
                    }

                    if (!empty($scopeBranchIds)) {
                        sort($scopeBranchIds);
                        $scopeKey = sha1(implode(',', $scopeBranchIds));
                        $cacheKey = 'topbar:notifications:v2:user:' . $authUser->id . ':role:' . ($authRole ?? 'guest') . ':scope:' . $scopeKey;

                        $payload = \Illuminate\Support\Facades\Cache::remember(
                            $cacheKey,
                            now()->addSeconds(20),
                            function () use ($scopeBranchIds) {
                                $today = now()->startOfDay();
                                $upcomingEnd = $today->copy()->addDays(7)->endOfDay();
                                $todayDate = $today->toDateString();
                                $upcomingDate = $upcomingEnd->toDateString();

                                $notificationCases = \App\Models\FuneralCase::query()
                                    ->select([
                                        'id',
                                        'branch_id',
                                        'client_id',
                                        'deceased_id',
                                        'case_code',
                                        'case_status',
                                        'payment_status',
                                        'balance_amount',
                                        'funeral_service_at',
                                        'interment_at',
                                        'created_at',
                                    ])
                                    ->with([
                                        'client:id,full_name',
                                        'deceased:id,full_name',
                                    ])
                                    ->whereIn('branch_id', $scopeBranchIds)
                                    ->where(function ($q) {
                                        $q->where('entry_source', 'MAIN')->orWhereNull('entry_source');
                                    })
                                    ->where(function ($candidate) use ($todayDate, $upcomingDate, $today, $upcomingEnd) {
                                        $candidate
                                            ->where(function ($due) {
                                                $due->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
                                                    ->orWhere('balance_amount', '>', 0);
                                            })
                                            ->orWhere(function ($schedule) use ($todayDate, $upcomingDate, $today, $upcomingEnd) {
                                                $schedule->where('case_status', '!=', 'COMPLETED')
                                                    ->where(function ($dateWindow) use ($todayDate, $upcomingDate, $today, $upcomingEnd) {
                                                        $dateWindow
                                                            ->whereBetween('funeral_service_at', [$todayDate, $upcomingDate])
                                                            ->orWhereBetween('interment_at', [$today, $upcomingEnd]);
                                                    });
                                            });
                                    })
                                    ->get();

                                $items = collect();

                                foreach ($notificationCases as $case) {
                                    $deceasedName = $case->deceased?->full_name ?: 'Unknown';
                                    $caseCode = $case->case_code ?: 'N/A';

                                    if (
                                        in_array($case->payment_status, ['UNPAID', 'PARTIAL'], true) ||
                                        ((float) ($case->balance_amount ?? 0) > 0)
                                    ) {
                                        $isIntermentDueToday = $case->interment_at && $case->interment_at->isSameDay($today);
                                        $items->push([
                                            'bucket' => 'due',
                                            'priority' => 4,
                                            'title' => $isIntermentDueToday ? 'Payment due today (Interment)' : 'Balance pending',
                                            'subtitle' => "{$caseCode} - {$deceasedName}",
                                            'date' => $case->interment_at?->copy() ?? $case->funeral_service_at?->copy() ?? now(),
                                            'tab' => 'unpaid',
                                            'alert_type' => 'balance',
                                            'case_code' => $caseCode,
                                            'deceased_name' => $deceasedName,
                                            'client_name' => $case->client?->full_name ?? 'N/A',
                                        ]);
                                    }

                                    if ($case->case_status === 'COMPLETED') {
                                        continue;
                                    }

                                    if ($case->funeral_service_at && $case->funeral_service_at->isSameDay($today)) {
                                        $items->push([
                                            'bucket' => 'today',
                                            'priority' => 3,
                                            'title' => 'Funeral service today',
                                            'subtitle' => "{$caseCode} - {$deceasedName}",
                                            'date' => $case->funeral_service_at->copy(),
                                            'tab' => 'today',
                                            'alert_type' => 'service_today',
                                            'case_code' => $caseCode,
                                            'deceased_name' => $deceasedName,
                                            'client_name' => $case->client?->full_name ?? 'N/A',
                                        ]);
                                    }

                                    if ($case->interment_at && $case->interment_at->isSameDay($today)) {
                                        $items->push([
                                            'bucket' => 'today',
                                            'priority' => 3,
                                            'title' => 'Interment today',
                                            'subtitle' => "{$caseCode} - {$deceasedName}",
                                            'date' => $case->interment_at->copy(),
                                            'tab' => 'today',
                                            'alert_type' => 'interment_today',
                                            'case_code' => $caseCode,
                                            'deceased_name' => $deceasedName,
                                            'client_name' => $case->client?->full_name ?? 'N/A',
                                        ]);
                                    }

                                    if (
                                        $case->funeral_service_at &&
                                        $case->funeral_service_at->greaterThan($today) &&
                                        $case->funeral_service_at->lessThanOrEqualTo($upcomingEnd)
                                    ) {
                                        $items->push([
                                            'bucket' => 'upcoming',
                                            'priority' => 2,
                                            'title' => 'Upcoming funeral service',
                                            'subtitle' => "{$caseCode} - {$deceasedName}",
                                            'date' => $case->funeral_service_at->copy(),
                                            'tab' => 'upcoming',
                                            'alert_type' => 'upcoming_service',
                                            'case_code' => $caseCode,
                                            'deceased_name' => $deceasedName,
                                            'client_name' => $case->client?->full_name ?? 'N/A',
                                        ]);
                                    }

                                    if (
                                        $case->interment_at &&
                                        $case->interment_at->greaterThan($today) &&
                                        $case->interment_at->lessThanOrEqualTo($upcomingEnd)
                                    ) {
                                        $items->push([
                                            'bucket' => 'upcoming',
                                            'priority' => 2,
                                            'title' => 'Upcoming interment',
                                            'subtitle' => "{$caseCode} - {$deceasedName}",
                                            'date' => $case->interment_at->copy(),
                                            'tab' => 'upcoming',
                                            'alert_type' => 'upcoming_interment',
                                            'case_code' => $caseCode,
                                            'deceased_name' => $deceasedName,
                                            'client_name' => $case->client?->full_name ?? 'N/A',
                                        ]);
                                    }
                                }

                                $sortedItems = $items
                                    ->sortBy([
                                        ['priority', 'desc'],
                                        ['date', 'asc'],
                                    ])
                                    ->values();

                                return [
                                    'items' => $sortedItems->take(8)->all(),
                                    'counts' => [
                                        'all' => $sortedItems->count(),
                                        'due' => $sortedItems->where('bucket', 'due')->count(),
                                        'today' => $sortedItems->where('bucket', 'today')->count(),
                                        'upcoming' => $sortedItems->where('bucket', 'upcoming')->count(),
                                    ],
                                ];
                            }
                        );

                        $topbarNotifications = collect($payload['items'] ?? []);
                        $notificationCounts = array_merge($notificationCounts, $payload['counts'] ?? []);
                    }
                }

                $notificationTotal = $notificationCounts['all'] ?? 0;
            @endphp

            @unless($hideLayoutTopbar)
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

                    <div class="topbar-notification-wrap" data-notification>
                        <button
                            type="button"
                            class="topbar-notification {{ $isReminderPage ? 'is-active' : '' }}"
                            aria-label="Open reminders and schedule"
                            aria-expanded="false"
                            data-notification-toggle
                        >
                            <i class="bi bi-bell"></i>
                            @if($notificationTotal > 0)
                                <span class="topbar-notification__count" aria-hidden="true">{{ min($notificationTotal, 99) }}</span>
                            @endif
                        </button>

                        <div class="topbar-notification-menu" data-notification-menu hidden>
                            <div class="topbar-notification-menu__head">
                                <div>
                                    <strong>Reminders & Alerts</strong>
                                    <small data-notification-summary>{{ $notificationTotal }} active alert{{ $notificationTotal === 1 ? '' : 's' }} need your attention</small>
                                </div>
                                @if($notificationHref)
                                    <a href="{{ $notificationHref }}">View all <i class="bi bi-arrow-up-right"></i></a>
                                @endif
                            </div>

                            <div class="topbar-notification-menu__chips">
                                <button type="button" class="topbar-notification-chip is-active" data-notification-filter="all">
                                    <span class="topbar-notification-chip__dot"></span>
                                    <span>All</span>
                                    <strong data-notification-count="all">{{ $notificationCounts['all'] }}</strong>
                                </button>
                                <button type="button" class="topbar-notification-chip" data-notification-filter="due">
                                    <span class="topbar-notification-chip__dot"></span>
                                    <span>Due</span>
                                    <strong data-notification-count="due">{{ $notificationCounts['due'] }}</strong>
                                </button>
                                <button type="button" class="topbar-notification-chip" data-notification-filter="today">
                                    <span class="topbar-notification-chip__dot"></span>
                                    <span>Today</span>
                                    <strong data-notification-count="today">{{ $notificationCounts['today'] }}</strong>
                                </button>
                                <button type="button" class="topbar-notification-chip" data-notification-filter="upcoming">
                                    <span class="topbar-notification-chip__dot"></span>
                                    <span>Upcoming</span>
                                    <strong data-notification-count="upcoming">{{ $notificationCounts['upcoming'] }}</strong>
                                </button>
                            </div>

                            <div class="topbar-notification-menu__list" data-notification-list>
                                @forelse($topbarNotifications as $item)
                                    @php
                                        $itemClass = match ($item['bucket']) {
                                            'due' => 'is-due',
                                            'today' => 'is-today',
                                            default => 'is-upcoming',
                                        };
                                        $itemIcon = match ($item['bucket']) {
                                            'due' => 'bi-credit-card-2-front',
                                            'today' => 'bi-calendar-day',
                                            default => 'bi-calendar-event',
                                        };
                                        $itemDate = $item['date']?->format('M d, Y h:i A') ?? 'No date';
                                        $daysAway = $item['date']
                                            ? now()->startOfDay()->diffInDays($item['date']->copy()->startOfDay(), false)
                                            : null;
                                        $rightTag = match ($item['bucket']) {
                                            'due' => 'Urgent',
                                            'today' => 'Today',
                                            default => ($daysAway === null ? 'Upcoming' : ($daysAway <= 0 ? 'Today' : $daysAway . ' day' . ($daysAway > 1 ? 's' : ''))),
                                        };
                                        $pillLabel = $item['bucket'] === 'due' ? 'Payment' : 'Schedule';
                                        $detail = match ($item['bucket']) {
                                            'due' => ($item['deceased_name'] ?? 'Client') . ' - ' . ($item['client_name'] ?? 'N/A') . ' has unsettled balance. Immediate follow-up required.',
                                            'today' => ($item['deceased_name'] ?? 'Client') . ' - ' . ($item['client_name'] ?? 'N/A') . ' schedule is set today.',
                                            default => ($item['deceased_name'] ?? 'Client') . ' - ' . ($item['client_name'] ?? 'N/A') . ' has upcoming schedule.',
                                        };
                                        $title = $item['bucket'] === 'due'
                                            ? 'Balance Pending - ' . ($item['case_code'] ?? 'N/A')
                                            : ($item['title'] . ' - ' . ($item['case_code'] ?? 'N/A'));
                                    @endphp

                                    @if($notificationHref)
                                        <a
                                            href="{{ route($notificationRouteName, ['tab' => $item['tab'], 'alert_type' => $item['alert_type']]) }}"
                                            class="topbar-notification-card {{ $itemClass }}"
                                            data-notification-item
                                            data-bucket="{{ $item['bucket'] }}"
                                        >
                                            <div class="topbar-notification-card__icon"><i class="bi {{ $itemIcon }}"></i></div>
                                            <div class="topbar-notification-card__content">
                                                <div class="topbar-notification-card__head">
                                                    <div class="topbar-notification-card__title">{{ $title }}</div>
                                                    <span class="topbar-notification-card__tag">{{ $rightTag }}</span>
                                                </div>
                                                <div class="topbar-notification-card__text">{{ $detail }}</div>
                                                <div class="topbar-notification-card__meta">
                                                    <span class="topbar-notification-card__pill">{{ $pillLabel }}</span>
                                                    <span>{{ $itemDate }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    @else
                                        <div class="topbar-notification-card {{ $itemClass }}" data-notification-item data-bucket="{{ $item['bucket'] }}">
                                            <div class="topbar-notification-card__icon"><i class="bi {{ $itemIcon }}"></i></div>
                                            <div class="topbar-notification-card__content">
                                                <div class="topbar-notification-card__head">
                                                    <div class="topbar-notification-card__title">{{ $title }}</div>
                                                    <span class="topbar-notification-card__tag">{{ $rightTag }}</span>
                                                </div>
                                                <div class="topbar-notification-card__text">{{ $detail }}</div>
                                                <div class="topbar-notification-card__meta">
                                                    <span class="topbar-notification-card__pill">{{ $pillLabel }}</span>
                                                    <span>{{ $itemDate }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <div class="topbar-notification-empty" data-notification-empty>
                                        No alerts right now.
                                    </div>
                                @endforelse
                                @if($topbarNotifications->isNotEmpty())
                                    <div class="topbar-notification-empty" data-notification-empty hidden>
                                        No alerts match this filter.
                                    </div>
                                @endif
                            </div>

                            <div class="topbar-notification-menu__footer">
                                <button type="button" class="topbar-notification-footer-btn" data-notification-mark-read>Mark all as read</button>
                                @if($notificationHref)
                                    <a href="{{ $notificationHref }}" class="topbar-notification-footer-btn is-primary">Open Reminders <i class="bi bi-arrow-up-right"></i></a>
                                @else
                                    <button type="button" class="topbar-notification-footer-btn is-primary" disabled>Open Reminders</button>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            @if($filterBar !== '')
                <div class="topbar-filter-bar">
                    @yield('filter_bar')
                </div>
            @endif
            @endunless

            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    @if(session('warning'))
        <div id="flashWarningToast" class="flash-toast-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('warning') }}</span>
        </div>
        <script>
            (function () {
                const toast = document.getElementById('flashWarningToast');
                if (!toast) return;
                requestAnimationFrame(() => toast.classList.add('show'));
                setTimeout(() => toast.classList.remove('show'), 4500);
            })();
        </script>
    @endif

    <script>
        (function () {
            const toggle = document.getElementById('mobileSidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            const sidebar = document.getElementById('appSidebar');
            const desktopToggle = document.getElementById('desktopSidebarToggle');
            const desktopMedia = window.matchMedia('(min-width: 1024px)');
            const collapseStorageKey = 'sidebar-collapsed';
            if (!toggle || !backdrop || !sidebar) return;

            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach((link) => {
                const label = link.querySelector('span')?.textContent?.trim();
                if (!label) return;
                link.setAttribute('data-nav-label', label);
                link.setAttribute('title', label);
                link.setAttribute('aria-label', label);
            });

            const brandName = sidebar.querySelector('.sidebar-brand-name')?.textContent?.trim();
            const brand = sidebar.querySelector('.sidebar-brand');
            if (brand && brandName) {
                brand.setAttribute('title', brandName);
                brand.setAttribute('aria-label', brandName);
            }

            const closeSidebar = () => {
                document.body.removeAttribute('data-sidebar-open');
                toggle.setAttribute('aria-expanded', 'false');
            };

            const openSidebar = () => {
                document.body.setAttribute('data-sidebar-open', 'true');
                toggle.setAttribute('aria-expanded', 'true');
            };

            const syncDesktopToggle = (collapsed) => {
                if (!desktopToggle) return;
                desktopToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                desktopToggle.setAttribute('aria-label', collapsed ? 'Open sidebar' : 'Collapse sidebar');

                const glyph = desktopToggle.querySelector('.sidebar-collapse-glyph');
                if (!glyph) return;
                glyph.classList.toggle('bi-caret-right-fill', collapsed);
                glyph.classList.toggle('bi-caret-left-fill', !collapsed);
            };

            const setDesktopCollapsed = (collapsed, persist = true) => {
                if (!desktopMedia.matches) {
                    document.body.removeAttribute('data-sidebar-collapsed');
                    syncDesktopToggle(false);
                    return;
                }

                if (collapsed) {
                    document.body.setAttribute('data-sidebar-collapsed', 'true');
                } else {
                    document.body.removeAttribute('data-sidebar-collapsed');
                }

                syncDesktopToggle(collapsed);
                if (persist) {
                    localStorage.setItem(collapseStorageKey, collapsed ? 'true' : 'false');
                }
            };

            const restoreDesktopCollapsed = () => {
                const stored = localStorage.getItem(collapseStorageKey);
                const initialCollapsed = stored === null ? true : stored === 'true';
                setDesktopCollapsed(initialCollapsed, false);
            };

            toggle.addEventListener('click', () => {
                if (desktopMedia.matches) {
                    const isCollapsed = document.body.getAttribute('data-sidebar-collapsed') === 'true';
                    setDesktopCollapsed(!isCollapsed);
                    return;
                }

                if (document.body.getAttribute('data-sidebar-open') === 'true') {
                    closeSidebar();
                    return;
                }
                openSidebar();
            });

            if (desktopToggle) {
                desktopToggle.addEventListener('click', () => {
                    if (!desktopMedia.matches) return;
                    const isCollapsed = document.body.getAttribute('data-sidebar-collapsed') === 'true';
                    setDesktopCollapsed(!isCollapsed);
                });
            }

            restoreDesktopCollapsed();

            backdrop.addEventListener('click', closeSidebar);

            window.addEventListener('resize', () => {
                if (desktopMedia.matches) {
                    closeSidebar();
                    restoreDesktopCollapsed();
                    return;
                }

                document.body.removeAttribute('data-sidebar-collapsed');
                syncDesktopToggle(false);
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
            const navLinks = document.querySelectorAll('.sidebar .nav-link[href]');
            if (!navLinks.length) return;

            const prefetched = new Set();

            const prefetch = (href) => {
                if (!href || prefetched.has(href)) return;
                prefetched.add(href);

                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.as = 'document';
                link.href = href;
                document.head.appendChild(link);
            };

            navLinks.forEach((link) => {
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

                let absolute = '';
                try {
                    const target = new URL(href, window.location.href);
                    if (target.origin !== window.location.origin) return;
                    absolute = target.href;
                } catch (error) {
                    return;
                }

                const warm = () => prefetch(absolute);
                link.addEventListener('mouseenter', warm, { once: true });
                link.addEventListener('focus', warm, { once: true });
                link.addEventListener('touchstart', warm, { once: true, passive: true });
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
        })();

        (function () {
            const wrap = document.querySelector('[data-notification]');
            const toggle = document.querySelector('[data-notification-toggle]');
            const menu = document.querySelector('[data-notification-menu]');
            if (!wrap || !toggle || !menu) return;
            const filterButtons = [...menu.querySelectorAll('[data-notification-filter]')];
            const cards = [...menu.querySelectorAll('[data-notification-item]')];
            const emptyState = menu.querySelector('[data-notification-empty]') || menu.querySelector('.topbar-notification-empty');
            const markReadBtn = menu.querySelector('[data-notification-mark-read]');
            const countBadge = toggle.querySelector('.topbar-notification__count');
            const countEls = [...menu.querySelectorAll('[data-notification-count]')];
            const summary = menu.querySelector('[data-notification-summary]');

            const updateVisibleState = (bucket = 'all') => {
                cards.forEach((card) => {
                    const cardBucket = card.getAttribute('data-bucket');
                    const show = bucket === 'all' || bucket === cardBucket;
                    card.hidden = !show;
                });

                const visibleCount = cards.filter((card) => !card.hidden).length;
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
                markReadBtn.addEventListener('click', () => {
                    cards.forEach((card) => {
                        card.hidden = true;
                    });

                    countEls.forEach((el) => {
                        el.textContent = '0';
                    });

                    if (summary) {
                        summary.textContent = '0 active alerts. All caught up.';
                    }

                    if (countBadge) {
                        countBadge.remove();
                    }

                    filterButtons.forEach((btn) => btn.classList.remove('is-active'));
                    const allBtn = filterButtons.find((btn) => btn.getAttribute('data-notification-filter') === 'all');
                    if (allBtn) allBtn.classList.add('is-active');

                    if (emptyState) {
                        emptyState.textContent = 'All reminders are marked as read.';
                        emptyState.hidden = false;
                    }
                });
            }

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
                if (!wrap.contains(event.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            updateVisibleState('all');
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

