@extends('layouts.panel')
@section('page_title', 'System Admin Dashboard')
@section('page_desc', 'System-wide access, branch coverage, and recovery readiness.')
@section('hide_layout_topbar', '1')
@section('content')
@php
    $firstName = \Illuminate\Support\Str::of(auth()->user()?->name ?? 'Admin')->trim()->explode(' ')->first();
    $dashboardUrl = url('/admin');
    $usersUrl = route('admin.users.index', ['status' => 'active']);
    $backupStatus = $backupOverdue ? 'Needs review' : 'Verified';
    $backupTime = $lastBackup?->verified_at?->format('M d, Y · g:i A') ?? 'No verified backup';
    $branchCards = $branches->sortBy(fn ($branch) => !$branch->is_active ? 2 : ($branch->active_admin_count ? 1 : 0))->values();
    $branchesNeedingAdmin = $branches->where('is_active', true)->where('active_admin_count', 0);
    $dashboardAttention = collect();
    $branchWarningAdded = false;
    foreach ($attention as $alert) {
        if ($alert['action'] === 'Review branch admins') {
            if ($branchWarningAdded) continue;
            $branchWarningAdded = true;
            $alert['message'] = $branchesNeedingAdmin->count().($branchesNeedingAdmin->count() === 1 ? ' branch needs' : ' branches need').' an active Branch Admin.';
            $alert['action'] = 'Review branch coverage';
            $alert['url'] = '#sa-coverage-title';
        }
        $dashboardAttention->push($alert);
    }
    $userMetrics = [
        ['Other Active System Admins', $adminCounts['system'], ['role'=>'admin', 'admin_scope'=>'system', 'status'=>'active']],
        ['Active Branch Admins', $adminCounts['branch'], ['role'=>'admin', 'admin_scope'=>'branch', 'status'=>'active']],
        ['Active Staff', $roleCounts['staff'] ?? 0, ['role'=>'staff', 'status'=>'active']],
        ['Inactive Accounts', $inactiveUsers, ['status'=>'inactive']],
    ];
    $catalogMetrics = [
        ['Active Packages', $activePackages, 'packages', 'active'],
        ['Available Caskets', $activeCaskets, 'caskets', 'available'],
        ['Unavailable Caskets', $unavailableCaskets, 'caskets', 'unavailable'],
        ['Active Add-ons', $activeAddOns, 'addons', 'active'],
        ['Active Freebies', $activeFreebies, 'freebies', 'active'],
    ];
@endphp
@vite(['resources/css/system-admin-dashboard.css', 'resources/js/system-admin-dashboard.js'])
<div class="sa">
    <header class="sa-header panel-title-surface">
        <div class="sa-heading"><button type="button" id="mobileSidebarToggle" class="mobile-menu-btn" aria-label="Open navigation" aria-expanded="false" aria-controls="appSidebar"><i class="bi bi-list" aria-hidden="true"></i></button><div><h1><x-page-title-icon icon="grid" inline />Good day, {{ $firstName }}</h1><p>Review system issues and take the next action.</p></div></div>
        @include('partials.dashboard-header-controls', ['dashboardShowNotifications' => true])
    </header>

    @if($dashboardAttention->isNotEmpty())
    <section class="sa-panel" id="needs-attention" aria-labelledby="sa-attention-title">
        <header class="sa-head sa-attention-head"><div><h2 id="sa-attention-title"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Needs Attention <span class="sa-badge">{{ $dashboardAttention->count() }}</span></h2><p>{{ $dashboardAttention->isNotEmpty() ? $dashboardAttention->first()['message'] : 'No system issues require attention.' }}</p>@if($dashboardAttention->isNotEmpty())<span class="sa-muted">{{ $dashboardAttention->where('severity', 'critical')->count() }} critical · {{ $dashboardAttention->where('severity', 'warning')->count() }} {{ $dashboardAttention->where('severity', 'warning')->count() === 1 ? 'warning' : 'warnings' }} · System issues</span>@endif</div>@if($dashboardAttention->isNotEmpty())<button type="button" class="sa-link" data-sa-attention-toggle aria-expanded="false" aria-controls="sa-attention-list">Review {{ $dashboardAttention->count() }} {{ $dashboardAttention->count() === 1 ? 'issue' : 'issues' }} <i class="bi bi-chevron-down" aria-hidden="true"></i></button>@endif</header>
        <div id="sa-attention-list" class="sa-body sa-list sa-scroll-region" tabindex="0" role="region" aria-label="Issues needing attention" @if($dashboardAttention->isNotEmpty()) hidden @endif>@forelse($dashboardAttention as $index => $alert)
            <article class="sa-alert {{ $alert['severity'] === 'critical' ? 'is-critical' : '' }}"><div><span class="sa-badge">{{ $alert['severity'] === 'critical' ? 'Critical' : 'Warning' }}</span><h3>{{ $alert['message'] }}</h3><p>{{ $alert['severity'] === 'critical' ? 'Last verified: '.$backupTime : ($alert['action'] === 'Assign staff branch' ? 'Assign these accounts to the branch where they work.' : 'View the affected branches in Branch Coverage below.') }}</p></div><a class="sa-link" href="{{ $alert['url'] }}">{{ $alert['action'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a></article>
        @empty
            <div class="sa-empty"><i class="bi bi-check-circle" aria-hidden="true"></i><div><strong>No action required</strong><p>All active branches have admin coverage, active staff have branch assignments, and a recent verified backup is available.</p><a class="sa-link" href="{{ route('admin.users.index') }}">Manage Users</a></div></div>
        @endforelse</div>
    </section>

    @endif
    <section class="sa-kpis" aria-label="System summary">
        <article class="sa-panel sa-summary"><div class="sa-summary-heading"><h2>Active Branches</h2><span class="sa-summary-icon" aria-hidden="true"><i class="bi bi-buildings"></i></span></div><div><strong>{{ $activeBranches }}</strong><p>Branches currently open for operation</p></div><x-system-card-link :url="route('admin.branches.index', ['status'=>'active'])" label="View active branches in Branch Management" /></article>
        <article class="sa-panel sa-summary"><div class="sa-summary-heading"><h2>Active Managed Users</h2><span class="sa-summary-icon" aria-hidden="true"><i class="bi bi-people"></i></span></div><div><strong>{{ $activeUsers }}</strong><p>Active admins and staff, excluding your account</p></div><x-system-card-link :url="$usersUrl" label="View active managed accounts in User Management" /></article>
        <article class="sa-panel sa-summary"><div class="sa-summary-heading"><h2>Backup Status</h2><span class="sa-summary-icon" aria-hidden="true"><i class="bi bi-database-check"></i></span></div><div><strong class="sa-status-value">{{ $backupStatus }}</strong><p>{{ $lastBackup?->verified_at ? 'Last verified '.$lastBackup->verified_at->diffForHumans() : 'No verified recovery point' }}</p></div><x-system-card-link :url="route('admin.backups.index')" label="Review backup status in Backup and Recovery" /></article>
    </section>
    <div class="sa-columns sa-operational-columns">
        <section class="sa-panel" aria-labelledby="sa-coverage-title">
            <header class="sa-head"><div><h2 id="sa-coverage-title"><i class="bi bi-buildings" aria-hidden="true"></i> Branch Coverage</h2><p>Branches needing an admin appear first.</p></div><a class="sa-link" href="{{ route('admin.branches.index') }}">View all {{ $branches->count() }}</a></header>
            <div class="sa-body"><label class="sa-filter-label" for="sa-branch-filter">Branch status</label><select id="sa-branch-filter" class="sa-select"><option value="all">All statuses</option><option value="needs-admin">Needs Admin</option><option value="covered">Covered</option><option value="inactive">Inactive</option></select><p class="sa-muted" data-sa-branch-status role="status"></p><div class="sa-list sa-scroll-region" data-sa-branches tabindex="0" role="region" aria-label="Branch coverage list">
                @foreach($branchCards as $branch)
                @php
                    $coverage = $branch->coverage_status;
                    $branchUrl = route('admin.branches.index', ['highlight_branch'=>$branch->id]);
                @endphp
                <article class="sa-branch" data-sa-branch-state="{{ !$branch->is_active ? 'inactive' : ($branch->active_admin_count ? 'covered' : 'needs-admin') }}"><div><h3>{{ $branch->branch_code }} — {{ $branch->branch_name }}</h3><span class="sa-badge">{{ $coverage }}</span><p>{{ $branch->active_admin_count }} active branch admins · {{ $branch->active_staff_count }} active staff</p></div><div class="sa-actions"><x-system-overview :id="'sa-branch-'.$branch->id" :title="$branch->branch_name" :fields="['Branch code'=>$branch->branch_code, 'Status'=>$branch->is_active ? 'Active' : 'Inactive', 'Coverage'=>$coverage, 'Active branch admins'=>$branch->active_admin_count, 'Active staff'=>$branch->active_staff_count]" :url="$branchUrl" label="View Branch" /><x-system-card-link :url="$branchUrl" :label="'View Branch: '.$branch->branch_name" /></div></article>
                @endforeach
            </div><p class="sa-empty" data-sa-branch-empty hidden>No branches match this selection.</p></div>
        </section>
        <section class="sa-panel" aria-labelledby="sa-activity-title"><header class="sa-head"><div><h2 id="sa-activity-title"><i class="bi bi-clock-history" aria-hidden="true"></i> Recent Activity</h2><p>Filter the latest five events. Full history is in Audit Logs.</p></div><a class="sa-link" href="{{ route('admin.audit-logs.index') }}">View all</a></header><div class="sa-body">
            @if($activities->isNotEmpty())<div><label class="sa-filter-label" for="sa-activity-filter">Activity type</label><select id="sa-activity-filter" class="sa-select"><option value="all">All activity</option><option value="configuration">Configuration &amp; access changes</option><option value="security">Sign-in &amp; security</option><option value="other">Other events</option></select></div>@endif<p class="sa-muted" data-sa-activity-status role="status">{{ $activities->count() }} recent events</p><div class="sa-list sa-scroll-region" data-sa-activities tabindex="0" role="region" aria-label="Recent activity list">
            @forelse($activities as $activity)
            @php
                $activityTitle = str_replace('Mfa', 'MFA', $activity->action_label ?: \Illuminate\Support\Str::headline($activity->action));
                $activityGroup = preg_match('/mfa|login|logout|password|sign.in|authentication/i', $activity->action) ? 'security' : (preg_match('/user|branch|package|catalog|casket|freebie|add.on|backup/i', $activity->action) ? 'configuration' : 'other');
                $record = $activity->entity_type ? \Illuminate\Support\Str::headline($activity->entity_type).' #'.$activity->entity_id : 'System event';
                $activityFields = ['Actor'=>$activity->actor?->name ?? 'System', 'Action'=>$activityTitle, 'Affected record'=>$record, 'Branch'=>$activity->branch?->branch_name ?? 'System-wide', 'Recorded at'=>$activity->created_at->format('M d, Y · g:i A'), 'Result'=>$activity->status ?: 'Recorded'];
                foreach (['name', 'role', 'admin_scope', 'branch_id', 'is_active', 'is_available'] as $field) {
                    $before = data_get($activity->metadata, 'before.'.$field);
                    $after = data_get($activity->metadata, 'after.'.$field);
                    if (is_scalar($before) && is_scalar($after) && $before !== $after) {
                        $formatChange = fn ($value) => in_array($field, ['is_active', 'is_available'], true) ? ($value ? 'Yes' : 'No') : (string) $value;
                        $activityFields[\Illuminate\Support\Str::headline($field).' change'] = $formatChange($before).' → '.$formatChange($after);
                    }
                }
            @endphp
            <article class="sa-activity" data-sa-activity-group="{{ $activityGroup }}"><div><h3>{{ $activityTitle }}</h3><p>{{ $record }} @if($activity->branch) · {{ $activity->branch->branch_name }} @endif</p><p>{{ $activity->actor?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }} · {{ \Illuminate\Support\Str::headline($activity->status ?: 'Recorded') }}</p></div><x-system-overview :id="'sa-activity-'.$activity->id" :title="$activityTitle" :fields="$activityFields" description="Summary of the recorded event. Open Audit Logs filtered to this event." :url="route('admin.audit-logs.index', ['audit_log_id'=>$activity->id])" label="View Audit Log" /><x-system-card-link :url="route('admin.audit-logs.index', ['audit_log_id'=>$activity->id])" :label="'View audit event #'.$activity->id" /></article>
            @empty<div class="sa-empty">No recent activity. Future recorded changes will appear here.</div>@endforelse<p class="sa-empty" data-sa-activity-empty hidden>No matching events in the latest five. Open Audit Logs for the full history.</p></div>
        </div></section>
    </div>
    <div class="sa-columns">
        <section class="sa-panel" aria-labelledby="sa-access-title"><header class="sa-head"><div><h2 id="sa-access-title"><i class="bi bi-people" aria-hidden="true"></i> Users &amp; Access</h2><p>Managed accounts, excluding Owner and your account.</p></div><a class="sa-link" href="{{ route('admin.users.index') }}">Manage Users</a></header><div class="sa-body sa-metrics sa-scroll-region" tabindex="0" role="region" aria-label="User access summary">@foreach($userMetrics as [$label,$value,$filters])<article class="sa-metric"><div><h3>{{ $label }}</h3><strong>{{ $value }}</strong></div><span class="sa-metric-arrow" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span><x-system-card-link :url="route('admin.users.index', $filters)" :label="'View '.$label" /></article>@endforeach</div></section>
        <section class="sa-panel" aria-labelledby="sa-catalog-title"><header class="sa-head"><div><h2 id="sa-catalog-title"><i class="bi bi-collection" aria-hidden="true"></i> Service Catalog</h2><p>Availability for future cases.</p></div><a class="sa-link" href="{{ route('admin.service-management.index') }}">Open Catalog</a></header><div class="sa-body sa-scroll-region" tabindex="0" role="region" aria-label="Catalog availability"><div class="sa-metrics sa-catalog-metrics">@foreach($catalogMetrics as [$label,$value,$tab,$status])<article class="sa-metric"><div><h3>{{ $label }}</h3><strong>{{ $value }}</strong></div><span class="sa-metric-arrow" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span><x-system-card-link :url="route('admin.service-management.index', ['tab'=>$tab, 'status'=>$status])" :label="'View '.$label" /></article>@endforeach</div></div></section>
    </div>
</div>
@endsection

