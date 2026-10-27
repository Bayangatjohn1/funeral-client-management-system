@props(['name'])
<div class="staff-nav-icon" aria-hidden="true">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        @switch($name)
            @case('dashboard')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
            @case('new')<path d="M12 5v14M5 12h14"/>@break
            @case('payments')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
            @case('reminders')
                <rect x="3" y="4.5" width="18" height="16.5" rx="2.25" />
                <path d="M7 2.5v4M17 2.5v4M3 9h18" />
                <circle cx="15.5" cy="14.75" r="3.25" />
                <path d="M15.5 13v2l1.35.8" />
                @break
            @case('cases')<rect x="5" y="4" width="14" height="17" rx="2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 11h6M9 15h6"/>@break
            @case('clients')<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M21 21v-2a6 6 0 0 0-4-5.65"/>@break
        @endswitch
    </svg>
</div>
