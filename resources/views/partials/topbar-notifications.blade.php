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
    $notificationPayload = $notificationPayload ?? null;

    if ($showTopbarNotifications && $authUser && $notificationPayload === null) {
        $notificationPayload = app(\App\Support\TopbarNotificationBuilder::class)->forUser(
            $authUser,
            $authRole,
            $notificationBranchId > 0 ? $notificationBranchId : null
        );
        $topbarNotifications = collect($notificationPayload['items'] ?? []);
        $notificationCounts = array_merge($notificationCounts, $notificationPayload['counts'] ?? []);
    }

    $notificationTotal = $notificationTotal ?? ($notificationCounts['all'] ?? 0);
    $notificationUnread = $notificationUnread ?? ($notificationPayload['unread'] ?? $notificationTotal);
    $correctionFeed = $showTopbarNotifications && ($authUser?->isStaff() || $authUser?->isBranchAdmin() || $authUser?->isSystemAdmin())
        ? \App\Models\WorkflowNotice::correctionFeedFor($authUser)
        : null;
    $bellTotal = $notificationUnread + ($correctionFeed['unread'] ?? 0);
    $unreadReminderBuckets = $topbarNotifications->where('read', false)->pluck('bucket');
    $unreadWorkflowTones = collect($correctionFeed['items'] ?? [])->where('read', false)->pluck('tone');
    $reminderTone = match (true) {
        $unreadReminderBuckets->contains('needs_attention') => 'danger',
        $unreadReminderBuckets->contains('with_balance') => 'warning',
        $unreadReminderBuckets->contains('today') => 'info',
        $unreadReminderBuckets->contains('upcoming') => 'plan',
        default => 'neutral',
    };
    $workflowTone = match (true) {
        $unreadWorkflowTones->contains('danger') => 'danger',
        $unreadWorkflowTones->contains('warning') => 'warning',
        $unreadWorkflowTones->contains('info') => 'info',
        $unreadWorkflowTones->contains('plan') => 'plan',
        $unreadWorkflowTones->contains('success') => 'success',
        default => 'neutral',
    };
    $tonePriority = ['danger', 'warning', 'info', 'plan', 'success', 'neutral'];
    $bellTone = collect($tonePriority)->first(
        fn ($tone) => $tone === $reminderTone || $tone === $workflowTone,
        'neutral'
    );
@endphp

@if($showTopbarNotifications)
<div
    class="topbar-notification-wrap"
    data-notification
    data-notification-read-all-url="{{ route('notifications.read-all') }}"
    data-notification-state-url="{{ route('notifications.state') }}"
    data-notification-branch-id="{{ $notificationBranchId > 0 ? $notificationBranchId : '' }}"
>
    <button
        type="button"
        class="topbar-notification tone-{{ $bellTone }} {{ ($isReminderPage ?? false) ? 'is-active' : '' }}"
        aria-label="Notifications{{ $bellTotal > 0 ? ', '.$bellTotal.' unread' : '' }}"
        aria-expanded="false"
        aria-controls="topbarNotificationMenu"
        title="Notifications"
        data-notification-toggle
        data-reminders="{{ $notificationUnread }}"
        data-reminder-tone="{{ $reminderTone }}"
        data-unread-total="{{ $bellTotal }}"
    >
        <i class="bi bi-bell"></i>
        @if($bellTotal > 0)
            <span class="topbar-notification__count" aria-hidden="true">{{ min($bellTotal, 99) }}</span>
        @endif
    </button>

    <div id="topbarNotificationMenu" class="topbar-notification-menu {{ $compactNotificationMenu ? 'is-compact' : '' }}" data-notification-menu hidden>
        <div class="topbar-notification-menu__head">
            <div class="topbar-notification-menu__identity">
                <span class="topbar-notification-menu__icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                <div>
                    <strong>Notifications</strong>
                    <small data-notification-summary>{{ $bellTotal > 0 ? $bellTotal.' unread update'.($bellTotal === 1 ? '' : 's') : 'All caught up' }}</small>
                </div>
            </div>
        </div>

        <div class="topbar-notification-menu__list" data-notification-list>
            @if($authUser?->isStaff() || $authUser?->isBranchAdmin() || $authUser?->isSystemAdmin())
            <section class="topbar-workflow-notices" data-workflow-notices-url="{{ route('payments.corrections.notices') }}">
                <div class="topbar-workflow-notices__head"><strong>{{ $authUser->isSystemAdmin() ? 'Payment follow-ups' : 'Payment updates' }}</strong><small data-workflow-unread>{{ $correctionFeed ? ($correctionFeed['unread'] ? $correctionFeed['unread'].' unread' : 'All read') : 'Checking updates…' }}</small></div>
                <div class="pw-notice-list" data-workflow-notice-list>
                    @if($correctionFeed)
                        @forelse($correctionFeed['items'] as $notice)
                            <div class="pw-notice-entry tone-{{ $notice['tone'] ?? 'info' }} {{ $notice['read'] ? 'is-read' : '' }}" data-notification-entry data-notification-key="{{ $notice['key'] }}" data-unread="{{ $notice['read'] ? 'false' : 'true' }}">
                                <a href="{{ $notice['url'] }}" data-notification-open>
                                    <span>{{ implode(' · ', array_filter([$notice['title'], $notice['branch'], $notice['reference']])) }}</span>
                                    <small>{{ $notice['at'] }}</small>
                                </a>
                                <button type="button" class="topbar-notification-dismiss" data-notification-dismiss aria-label="Dismiss {{ $notice['title'] }}" title="Dismiss notification"><i class="bi bi-x" aria-hidden="true"></i></button>
                            </div>
                        @empty
                            No payment correction notifications yet.
                        @endforelse
                    @endif
                </div>
            </section>
            @endif
            @forelse(($topbarNotifications ?? collect()) as $item)
                @php
                    $itemClass = match ($item['bucket']) {
                        'needs_attention' => 'is-due',
                        'today' => 'is-today',
                        default => 'is-upcoming',
                    };
                    $itemTone = match ($item['bucket']) {
                        'needs_attention' => 'danger',
                        'with_balance' => 'warning',
                        'today' => 'info',
                        default => 'plan',
                    };
                    $itemIcon = match ($item['bucket']) {
                        'needs_attention' => 'bi-exclamation-triangle',
                        'with_balance' => 'bi-wallet2',
                        'today' => 'bi-calendar-day',
                        default => 'bi-calendar-event',
                    };
                    $itemDate = $item['date']
                        ? ($item['has_time'] ? $item['date']->format('M d, Y h:i A') : $item['date']->format('M d, Y').' · Time not recorded')
                        : 'No date';
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
                    <article
                        class="topbar-notification-entry tone-{{ $itemTone }} {{ $item['read'] ? 'is-read' : '' }}"
                        data-notification-item
                        data-notification-entry
                        data-notification-key="{{ $item['key'] }}"
                        data-unread="{{ $item['read'] ? 'false' : 'true' }}"
                        data-bucket="{{ $item['bucket'] }}"
                        data-buckets="{{ implode(' ', $item['buckets']) }}"
                    >
                        <a href="{{ route($notificationRouteName, ['branch_id' => $item['branch_id'], 'tab' => $item['tab'], 'focus_case' => $item['case_id']]) }}#case-reminder-{{ $item['case_id'] }}" class="topbar-notification-card {{ $itemClass }}" data-notification-open>
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
                        <button type="button" class="topbar-notification-dismiss" data-notification-dismiss aria-label="Dismiss {{ $title }}" title="Dismiss notification"><i class="bi bi-x" aria-hidden="true"></i></button>
                    </article>
                @else
                    <article class="topbar-notification-entry tone-{{ $itemTone }} {{ $item['read'] ? 'is-read' : '' }}" data-notification-item data-notification-entry data-notification-key="{{ $item['key'] }}" data-unread="{{ $item['read'] ? 'false' : 'true' }}" data-bucket="{{ $item['bucket'] }}" data-buckets="{{ implode(' ', $item['buckets']) }}">
                        <div class="topbar-notification-card {{ $itemClass }}">
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
                        <button type="button" class="topbar-notification-dismiss" data-notification-dismiss aria-label="Dismiss {{ $title }}" title="Dismiss notification"><i class="bi bi-x" aria-hidden="true"></i></button>
                    </article>
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
            <button type="button" class="topbar-notification-footer-btn" data-notification-mark-read @disabled($bellTotal === 0)>Mark all as read</button>
        </div>
    </div>
</div>
@endif
