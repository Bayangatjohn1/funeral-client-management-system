<div class="nav-section">
    <div class="nav-list">
        <a href="{{ url('/staff') }}" class="{{ $isActive(request()->is('staff')) }}">
            <svg class="{{ $iconState(request()->is('staff')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <p class="nav-group-label">Case Management</p>
    <div class="nav-list">
        <a href="{{ route('intake.main.create') }}"
           class="{{ $isActive(request()->routeIs('intake.main.create') || (request()->routeIs('funeral-cases.index') && request()->boolean('open_wizard'))) }}">
            <svg class="{{ $iconState(request()->routeIs('intake.main.create') || (request()->routeIs('funeral-cases.index') && request()->boolean('open_wizard'))) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>New Case</span>
        </a>

        <a href="{{ route('intake.other.create') }}"
           class="{{ $isActive(request()->routeIs('intake.other.create')) }}">
            <svg class="{{ $iconState(request()->routeIs('intake.other.create')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m0 0l-3-3m3 3l-3 3M6 17h12m0 0l-3-3m3 3l-3 3"/>
            </svg>
            <span>Branch Report</span>
        </a>

        <a href="{{ route('funeral-cases.index', ['record_scope' => 'main']) }}"
           class="{{ $isActive(request()->routeIs('funeral-cases.index') && request('record_scope', 'main') === 'main') }}">
            <svg class="{{ $iconState(request()->routeIs('funeral-cases.index') && request('record_scope', 'main') === 'main') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>Active Cases</span>
        </a>

        <a href="{{ route('funeral-cases.completed', ['record_scope' => 'main']) }}"
           class="{{ $isActive(request()->routeIs('funeral-cases.completed') && request('record_scope', 'main') === 'main') }}">
            <svg class="{{ $iconState(request()->routeIs('funeral-cases.completed') && request('record_scope', 'main') === 'main') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Completed Cases</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <p class="nav-group-label">Payments</p>
    <div class="nav-list">
        <a href="{{ route('payments.index') }}"
           class="{{ $isActive(request()->routeIs('payments.index')) }}">
            <svg class="{{ $iconState(request()->routeIs('payments.index')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Record Payment</span>
        </a>

        <a href="{{ route('payments.history') }}"
           class="{{ $isActive(request()->routeIs('payments.history')) }}">
            <svg class="{{ $iconState(request()->routeIs('payments.history')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Payment History</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <p class="nav-group-label">Records</p>
    <div class="nav-list">
        <a href="{{ route('clients.index') }}"
           class="{{ $isActive(request()->is('clients*')) }}">
            <svg class="{{ $iconState(request()->is('clients*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Client List</span>
        </a>

        <a href="{{ route('deceased.index') }}"
           class="{{ $isActive(request()->is('deceased*')) }}">
            <svg class="{{ $iconState(request()->is('deceased*')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Deceased List</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <div class="nav-list">
        <a href="{{ route('funeral-cases.other-reports') }}"
           class="{{ $isActive(request()->routeIs('funeral-cases.other-reports')) }}">
            <svg class="{{ $iconState(request()->routeIs('funeral-cases.other-reports')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <span>Branch Reports</span>
        </a>
    </div>
</div>
