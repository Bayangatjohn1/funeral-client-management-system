@php
    $authUser = $authUser ?? auth()->user();
    $authRole = $authRole ?? ($authUser->role ?? null);
    $showTopbarNotifications = $showTopbarNotifications ?? (! $authUser?->isOwner());
    $notificationRouteName = $notificationRouteName ?? match (true) {
        $authUser?->isAdmin() => 'admin.reminders.index',
        $authRole === 'staff' => 'staff.reminders.index',
        default => null,
    };
    $notificationBranchId = $notificationBranchId ?? (int) ($authUser?->operationalBranchId() ?? 0);
    $notificationRouteParams = $notificationBranchId > 0 ? ['branch_id' => $notificationBranchId] : [];
    $notificationHref = $notificationHref ?? ($notificationRouteName ? route($notificationRouteName, $notificationRouteParams) : null);
    $isReminderPage = $isReminderPage ?? (request()->routeIs('staff.reminders.index') || request()->routeIs('admin.reminders.index'));
    $notificationCounts = $notificationCounts ?? ['all' => 0, 'needs_attention' => 0, 'today' => 0, 'upcoming' => 0, 'with_balance' => 0];
    $compactNotificationMenu = $compactNotificationMenu ?? false;
    $topbarNotifications = isset($topbarNotifications) ? collect($topbarNotifications) : collect();

    if ($showTopbarNotifications && $authUser && ! isset($payload)) {
        $payload = app(\App\Support\TopbarNotificationBuilder::class)->forUser(
            $authUser,
            $authRole,
            $notificationBranchId > 0 ? $notificationBranchId : null
        );
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

    <div class="topbar-notification-menu {{ $compactNotificationMenu ? 'is-compact' : '' }}" data-notification-menu hidden>
        @unless($compactNotificationMenu)
            <div class="topbar-notification-menu__head">
                <div>
                    <strong>Reminders &amp; Schedules</strong>
                    <small data-notification-summary>{{ $notificationTotal ?? 0 }} case{{ ($notificationTotal ?? 0) === 1 ? '' : 's' }} with active reminders</small>
                </div>
                @if($notificationHref ?? null)
                    <a href="{{ $notificationHref }}">View all <i class="bi bi-arrow-up-right"></i></a>
                @endif
            </div>

        @endunless

        <div class="topbar-notification-menu__list" data-notification-list>
            @forelse(($topbarNotifications ?? collect()) as $item)
                @php
                    $itemClass = match ($item['bucket']) {
                        'needs_attention' => 'is-due',
                        'today' => 'is-today',
                        default => 'is-upcoming',
                    };
                    $itemIcon = match ($item['bucket']) {
                        'needs_attention' => 'bi-exclamation-triangle',
                        'with_balance' => 'bi-wallet2',
                        'today' => 'bi-calendar-day',
                        default => 'bi-calendar-event',
                    };
                    $itemDate = $item['date']?->format('M d, Y h:i A') ?? 'No date';
                    $daysAway = $item['date']
                        ? now()->startOfDay()->diffInDays($item['date']->copy()->startOfDay(), false)
                        : null;
                    $rightTag = match ($item['bucket']) {
                        'needs_attention' => 'Review',
                        'with_balance' => 'Balance',
                        'today' => 'Today',
                        default => ($daysAway === null ? 'Upcoming' : ($daysAway <= 0 ? 'Today' : $daysAway . ' day' . ($daysAway > 1 ? 's' : ''))),
                    };
                    $pillLabel = match ($item['bucket']) {
                        'needs_attention' => 'Needs Attention',
                        'with_balance' => 'With Balance',
                        default => 'Schedule',
                    };
                    $detail = match ($item['bucket']) {
                        'needs_attention' => $item['message'] ?: (($item['deceased_name'] ?? 'Client') . ' needs review or schedule coordination.'),
                        'with_balance' => ($item['deceased_name'] ?? 'Client') . ' - ' . ($item['client_name'] ?? 'N/A') . ' has a recorded remaining balance.',
                        'today' => ($item['deceased_name'] ?? 'Client') . ' - ' . ($item['client_name'] ?? 'N/A') . ' schedule is set today.',
                        default => ($item['deceased_name'] ?? 'Client') . ' - ' . ($item['client_name'] ?? 'N/A') . ' has upcoming schedule.',
                    };
                    $title = $item['title'] . ' - ' . ($item['case_code'] ?? 'N/A');
                @endphp

                @if($notificationHref ?? null)
                    <a
                        href="{{ route($notificationRouteName, ['branch_id' => $item['branch_id'], 'tab' => $item['tab'], 'focus_case' => $item['case_id']]) }}#case-reminder-{{ $item['case_id'] }}"
                        class="topbar-notification-card {{ $itemClass }}"
                        data-notification-item
                        data-bucket="{{ $item['bucket'] }}"
                        data-buckets="{{ implode(' ', $item['buckets']) }}"
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
                    <div class="topbar-notification-card {{ $itemClass }}" data-notification-item data-bucket="{{ $item['bucket'] }}" data-buckets="{{ implode(' ', $item['buckets']) }}">
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
            @if($notificationHref ?? null)
                <a href="{{ $notificationHref }}" class="topbar-notification-footer-btn is-primary">Open Reminders <i class="bi bi-arrow-up-right"></i></a>
            @else
                <button type="button" class="topbar-notification-footer-btn is-primary" disabled>Open Reminders</button>
            @endif
        </div>
    </div>
</div>
@endif
