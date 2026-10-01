<div class="nav-section">
    <div class="nav-list">
        <a href="/admin" class="{{ $isActive(request()->is('admin')) }}">
            <svg class="{{ $iconState(request()->is('admin')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span>Dashboard</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <div class="nav-list">
        <a href="{{ route('admin.cases.index') }}" class="{{ $isActive(request()->routeIs('admin.cases.*')) }}">
            <svg class="{{ $iconState(request()->routeIs('admin.cases.*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Master Case Records</span>
        </a>

        <a href="{{ route('admin.payments.index') }}" class="{{ $isActive(request()->routeIs('payments.history') || request()->routeIs('admin.payments.index') || request()->routeIs('admin.payment-monitoring')) }}">
            <svg class="{{ $iconState(request()->routeIs('payments.history') || request()->routeIs('admin.payments.index') || request()->routeIs('admin.payment-monitoring')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h.01M11 15h2m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span>Payment Monitoring</span>
        </a>
    </div>
</div>

@if(auth()->user()?->isMainBranchAdmin() || auth()->user()?->isBranchAdmin())
<div class="nav-section">
    <div class="nav-list">
        @if(auth()->user()?->isMainBranchAdmin())
            <a href="{{ route('admin.branches.index') }}" class="{{ $isActive(request()->is('admin/branches*')) }}">
                <svg class="{{ $iconState(request()->is('admin/branches*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>Branch Management</span>
            </a>
        @endif

        <a href="{{ route('admin.users.index') }}" class="{{ $isActive(request()->is('admin/users*')) }}">
            <svg class="{{ $iconState(request()->is('admin/users*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>User Management</span>
        </a>
    </div>
</div>
@endif

@if(auth()->user()?->isMainBranchAdmin() || auth()->user()?->isBranchAdmin())
@php
    $serviceManagementActive = request()->routeIs('admin.service-management.*')
        || request()->is('admin/packages*')
        || request()->is('admin/casket-catalogs*')
        || request()->is('admin/add-on-catalogs*')
        || request()->is('admin/freebie-catalogs*');
    $serviceNavigationLabel = auth()->user()?->isBranchAdmin() ? 'Services' : 'Service Management';
@endphp
<div class="nav-section">
    <div class="nav-list nav-list--service" data-service-nav data-service-active="{{ $serviceManagementActive ? '1' : '0' }}">
        <a
            href="{{ route('admin.service-management.index') }}"
            class="{{ $isActive($serviceManagementActive) }}"
            title="{{ $serviceNavigationLabel }}"
            aria-label="{{ $serviceNavigationLabel }}"
        >
            <svg class="nav-link__icon {{ $serviceManagementActive ? 'is-service-icon-active' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <span>{{ $serviceNavigationLabel }}</span>
        </a>
    </div>
</div>
@endif

<div class="nav-section">
    <div class="nav-list">
        <a href="{{ route('reports.index') }}" class="{{ $isActive(request()->routeIs('reports.*')) }}">
            <svg class="{{ $iconState(request()->routeIs('reports.*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            <span>Reports &amp; Analytics</span>
        </a>

        @if(auth()->user()?->isMainBranchAdmin() || auth()->user()?->isBranchAdmin())
            <a href="{{ route('admin.audit-logs.index') }}" class="{{ $isActive(request()->routeIs('admin.audit-logs.index')) }}">
                <svg class="{{ $iconState(request()->routeIs('admin.audit-logs.index')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-3 0v14m7-9H5m12 0a2 2 0 012 2v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7a2 2 0 012-2"/>
                </svg>
                <span>Audit Logs</span>
            </a>
        @endif

        @if(auth()->user()?->isMainBranchAdmin())
            <a href="{{ route('admin.backups.index') }}" class="{{ $isActive(request()->routeIs('admin.backups.*')) }}">
                <svg class="{{ $iconState(request()->routeIs('admin.backups.*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7c0-1.657 3.582-3 8-3s8 1.343 8 3-3.582 3-8 3-8-1.343-8-3zm0 0v5c0 1.657 3.582 3 8 3s8-1.343 8-3V7m-16 5v5c0 1.657 3.582 3 8 3s8-1.343 8-3v-5"/></svg>
                <span>Backup &amp; Recovery</span>
            </a>
            <a href="{{ route('admin.retention.index') }}" class="{{ $isActive(request()->routeIs('admin.retention.*')) }}">
                <svg class="{{ $iconState(request()->routeIs('admin.retention.*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Record Retention</span>
            </a>
        @endif

        @if(auth()->user()?->isBranchAdmin())
            <a href="{{ route('admin.payment-corrections.index') }}" class="{{ $isActive(request()->routeIs('admin.payment-corrections.*')) }}">
                <svg class="{{ $iconState(request()->routeIs('admin.payment-corrections.*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Payment Corrections</span>
            </a>
        @endif
    </div>
</div>
