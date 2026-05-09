@php
    $authUser = $authUser ?? auth()->user();
    $authRole = $authRole ?? ($authUser->role ?? null);
    $showTopbarNotifications = $showTopbarNotifications ?? (! $authUser?->isOwner());
    $notificationRouteName = $notificationRouteName ?? match (true) {
        $authUser?->isAdmin() => 'admin.reminders.index',
        $authRole === 'staff' => 'staff.reminders.index',
        default => null,
    };
    $notificationHref = $notificationHref ?? ($notificationRouteName ? route($notificationRouteName) : null);
    $isReminderPage = $isReminderPage ?? (request()->routeIs('staff.reminders.index') || request()->routeIs('admin.reminders.index'));
    $notificationCounts = $notificationCounts ?? ['all' => 0, 'due' => 0, 'today' => 0, 'upcoming' => 0];
    $topbarNotifications = isset($topbarNotifications) ? collect($topbarNotifications) : collect();

    if ($showTopbarNotifications && $authUser && ! isset($payload)) {
        $payload = app(\App\Support\TopbarNotificationBuilder::class)->forUser($authUser, $authRole);
        $topbarNotifications = collect($payload['items'] ?? []);
        $notificationCounts = array_merge($notificationCounts, $payload['counts'] ?? []);
    }

    $notificationTotal = $notificationTotal ?? ($notificationCounts['all'] ?? 0);
@endphp

@if($showTopbarNotifications)
<div class="topbar-notification-wrap" data-notification>
    <button
        type="button"
        class="topbar-notification {{ ($isReminderPage ?? false) ? 'is-active' : '' }}"
        aria-label="Open reminders and schedule"
        aria-expanded="false"
        data-notification-toggle
    >
        <i class="bi bi-bell"></i>
        @if(($notificationTotal ?? 0) > 0)
            <span class="topbar-notification__count" aria-hidden="true">{{ min($notificationTotal, 99) }}</span>
        @endif
    </button>

    <div class="topbar-notification-menu" data-notification-menu hidden>
        <div class="topbar-notification-menu__head">
            <div>
                <strong>Reminders & Alerts</strong>
                <small data-notification-summary>{{ $notificationTotal ?? 0 }} active alert{{ ($notificationTotal ?? 0) === 1 ? '' : 's' }} need your attention</small>
            </div>
            @if($notificationHref ?? null)
                <a href="{{ $notificationHref }}">View all <i class="bi bi-arrow-up-right"></i></a>
            @endif
        </div>

        <div class="topbar-notification-menu__chips">
            <button type="button" class="topbar-notification-chip is-active" data-notification-filter="all">
                <span class="topbar-notification-chip__dot"></span>
                <span>All</span>
                <strong data-notification-count="all">{{ $notificationCounts['all'] ?? 0 }}</strong>
            </button>
            <button type="button" class="topbar-notification-chip" data-notification-filter="due">
                <span class="topbar-notification-chip__dot"></span>
                <span>Due</span>
                <strong data-notification-count="due">{{ $notificationCounts['due'] ?? 0 }}</strong>
            </button>
            <button type="button" class="topbar-notification-chip" data-notification-filter="today">
                <span class="topbar-notification-chip__dot"></span>
                <span>Today</span>
                <strong data-notification-count="today">{{ $notificationCounts['today'] ?? 0 }}</strong>
            </button>
            <button type="button" class="topbar-notification-chip" data-notification-filter="upcoming">
                <span class="topbar-notification-chip__dot"></span>
                <span>Upcoming</span>
                <strong data-notification-count="upcoming">{{ $notificationCounts['upcoming'] ?? 0 }}</strong>
            </button>
        </div>

        <div class="topbar-notification-menu__list" data-notification-list>
            @forelse(($topbarNotifications ?? collect()) as $item)
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

                @if($notificationHref ?? null)
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
            @if(($topbarNotifications ?? collect())->isNotEmpty())
                <div class="topbar-notification-empty" data-notification-empty hidden>
                    No alerts match this filter.
                </div>
            @endif
        </div>

        <div class="topbar-notification-menu__footer">
            <button type="button" class="topbar-notification-footer-btn" data-notification-mark-read>Mark all as read</button>
            @if($notificationHref ?? null)
                <a href="{{ $notificationHref }}" class="topbar-notification-footer-btn is-primary">Open Reminders <i class="bi bi-arrow-up-right"></i></a>
            @else
                <button type="button" class="topbar-notification-footer-btn is-primary" disabled>Open Reminders</button>
            @endif
        </div>
    </div>
</div>
@endif
