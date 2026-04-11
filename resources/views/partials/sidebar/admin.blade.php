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
    <p class="nav-group-label">Case Operations</p>
    <div class="nav-list">
        <a href="{{ route('intake.main.create') }}" class="{{ $isActive(request()->routeIs('intake.main.create')) }}">
            <svg class="{{ $iconState(request()->routeIs('intake.main.create')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>New Case</span>
        </a>

        <a href="{{ route('intake.other.create') }}" class="{{ $isActive(request()->routeIs('intake.other.create')) }}">
            <svg class="{{ $iconState(request()->routeIs('intake.other.create')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m0 0l-3-3m3 3l-3 3M6 17h12m0 0l-3-3m3 3l-3 3"/>
            </svg>
            <span>Branch Report</span>
        </a>

        <a href="{{ route('funeral-cases.index', ['tab' => 'active', 'record_scope' => 'main']) }}"
           class="{{ $isActive((request()->routeIs('funeral-cases.index') || request()->routeIs('funeral-cases.completed')) && !request()->boolean('open_wizard')) }}">
            <svg class="{{ $iconState((request()->routeIs('funeral-cases.index') || request()->routeIs('funeral-cases.completed')) && !request()->boolean('open_wizard')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>Case Records</span>
        </a>

        <a href="{{ route('funeral-cases.other-reports') }}" class="{{ $isActive(request()->routeIs('funeral-cases.other-reports')) }}">
            <svg class="{{ $iconState(request()->routeIs('funeral-cases.other-reports')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4 4 4-4m0-5l-4-4-4 4"/>
            </svg>
            <span>Other Branch Reports</span>
        </a>

        <a href="{{ route('payments.index') }}" class="{{ $isActive(request()->routeIs('payments.index')) }}">
            <svg class="{{ $iconState(request()->routeIs('payments.index')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Record Payment</span>
        </a>

        <a href="{{ route('payments.history') }}" class="{{ $isActive(request()->routeIs('payments.history')) }}">
            <svg class="{{ $iconState(request()->routeIs('payments.history')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Payment History</span>
        </a>

        <a href="{{ route('clients.index') }}" class="{{ $isActive(request()->is('clients*')) }}">
            <svg class="{{ $iconState(request()->is('clients*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Client List</span>
        </a>

        <a href="{{ route('deceased.index') }}" class="{{ $isActive(request()->is('deceased*')) }}">
            <svg class="{{ $iconState(request()->is('deceased*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Deceased List</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <p class="nav-group-label">System Configuration</p>
    <div class="nav-list">
        <a href="{{ route('admin.branches.index') }}" class="{{ $isActive(request()->is('admin/branches*')) }}">
            <svg class="{{ $iconState(request()->is('admin/branches*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span>Branch Management</span>
        </a>

        <a href="{{ route('admin.packages.index') }}" class="{{ $isActive(request()->is('admin/packages*')) }}">
            <svg class="{{ $iconState(request()->is('admin/packages*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <span>Service Packages</span>
        </a>

        <a href="{{ route('admin.users.index') }}" class="{{ $isActive(request()->is('admin/users*')) }}">
            <svg class="{{ $iconState(request()->is('admin/users*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>User Accounts</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <p class="nav-group-label">Data Monitoring</p>
    <div class="nav-list">
        <a href="{{ route('admin.cases.index') }}" class="{{ $isActive(request()->routeIs('admin.cases.*')) }}">
            <svg class="{{ $iconState(request()->routeIs('admin.cases.*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Master Case Records</span>
        </a>

        <a href="{{ route('admin.reports.sales') }}" class="{{ $isActive(request()->routeIs('admin.reports.sales')) }}">
            <svg class="{{ $iconState(request()->routeIs('admin.reports.sales')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            <span>Sales Reports</span>
        </a>

        <a href="{{ route('admin.audit-logs.index') }}" class="{{ $isActive(request()->routeIs('admin.audit-logs.index')) }}">
            <svg class="{{ $iconState(request()->routeIs('admin.audit-logs.index')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-3 0v14m7-9H5m12 0a2 2 0 012 2v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7a2 2 0 012-2"/>
            </svg>
            <span>Audit Logs</span>
        </a>
    </div>
</div>
