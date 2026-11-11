<div class="dashboard-header-controls">
    @if($dashboardShowNotifications ?? false)
        @include('partials.topbar-notifications', [
            'compactNotificationMenu' => $dashboardCompactNotifications ?? false,
            'notificationBranchId' => $dashboardNotificationBranchId ?? (int) (auth()->user()?->operationalBranchId() ?? 0),
        ])
    @endif
    <button type="button" class="dashboard-header-theme" data-theme-toggle aria-label="Toggle color theme" title="Toggle light or dark mode">
        <i class="bi bi-moon" aria-hidden="true"></i>
        <i class="bi bi-sun" aria-hidden="true"></i>
    </button>
</div>
