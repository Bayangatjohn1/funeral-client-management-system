<div class="nav-section">
    <div class="nav-list">
        <a href="{{ url('/staff') }}" class="{{ $isActive(request()->is('staff')) }}">
            <x-staff-nav-icon name="dashboard" />
            <span>Dashboard</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <div class="nav-list">
        <a href="{{ route('intake.main.create') }}"
           class="{{ $isActive((request()->routeIs('intake.main.create') || request()->routeIs('intake.drafts.edit')) || (request()->routeIs('funeral-cases.index') && request()->boolean('open_wizard'))) }}">
            <x-staff-nav-icon name="new" />
            <span>New Case</span>
        </a>

        <a href="{{ route('payments.history') }}"
           class="{{ $isActive(request()->routeIs('payments.history') || request()->routeIs('payments.index')) }}">
            <x-staff-nav-icon name="payments" />
            <span>Payment Monitoring</span>
        </a>

        <a href="{{ route('staff.reminders.index') }}"
           class="{{ $isActive(request()->routeIs('staff.reminders.index')) }}">
            <x-staff-nav-icon name="reminders" />
            <span>Schedules &amp; Reminders</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <div class="nav-list">
        <a href="{{ route('funeral-cases.index', ['tab' => 'all', 'record_scope' => 'main']) }}"
           class="{{ $isActive((request()->routeIs('funeral-cases.index') || request()->routeIs('funeral-cases.completed')) && !request()->boolean('open_wizard')) }}">
            <x-staff-nav-icon name="cases" />
            <span>Case Records</span>
        </a>

        <a href="{{ route('clients.index') }}"
           class="{{ $isActive(request()->is('clients*')) }}">
            <x-staff-nav-icon name="clients" />
            <span>Client Directory</span>
        </a>

    </div>
</div>
