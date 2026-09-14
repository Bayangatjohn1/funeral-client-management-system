@extends('layouts.panel')

@section('page_title', 'Audit Logs')
@section('page_desc', 'Track user actions and system activity history.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    use Illuminate\Support\Str;

    $actionLabels = [
        'case.created' => 'Case created',
        'case.updated' => 'Case updated',
        'case.status_changed' => 'Case status changed',
        'payment.created' => 'Payment recorded',
        'payment.voided' => 'Payment voided',
        'package.created' => 'Package created',
        'package.updated' => 'Package updated',
        'package.price_changed' => 'Package price updated',
    ];

    $typeColors = [
        'create' => 'bg-emerald-400',
        'update' => 'bg-blue-400',
        'delete' => 'bg-rose-400',
        'status_change' => 'bg-amber-400',
        'financial' => 'bg-indigo-400',
        'security' => 'bg-purple-400',
    ];
@endphp

<style>
    .audit-ref-page {
        min-height:100%;
        width:100%;
        max-width:none;
        margin:0;
        padding:18px;
        background:
            linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
            linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
            repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
            #C4D2BE;
        background-size:44px 44px,44px 44px,16px 16px,auto;
    }

    .audit-toast {
        position:fixed;
        top:1rem;
        right:1rem;
        z-index:1200;
        display:flex;
        align-items:center;
        gap:.55rem;
        max-width:calc(100vw - 2rem);
        border:1px solid #8EA083;
        border-radius:.75rem;
        background:#2F3A2E;
        color:#F7FAF3;
        padding:.72rem .9rem;
        font-size:.88rem;
        font-weight:650;
        line-height:1.35;
        box-shadow:none !important;
        pointer-events:none;
        animation:auditToastIn .18s ease-out, auditToastOut .22s ease-in 3.8s forwards;
    }

    @keyframes auditToastIn {
        from { opacity:0; transform:translateY(-.35rem); }
        to { opacity:1; transform:translateY(0); }
    }

    @keyframes auditToastOut {
        to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
    }

    .audit-ref-page .table-system-card,
    .audit-ref-page .table-system-toolbar,
    .audit-ref-page .table-system-list,
    .audit-ref-page .table-system-wrap,
    .audit-ref-page .table-system-pagination {
        border-color:#AEBFA6 !important;
        background:#D3DEC9 !important;
        box-shadow:none !important;
    }

    .audit-ref-page .table-system-card {
        border-radius:.75rem;
        overflow:visible;
    }

    .audit-ref-page .table-system-toolbar {
        padding:.75rem;
        border-bottom:1px solid #AEBFA6;
    }

    .audit-toolbar {
        display:grid;
        grid-template-columns:minmax(12rem,1.05fr) minmax(12rem,1fr) minmax(11rem,.85fr) minmax(10rem,.75fr) minmax(10rem,.75fr) minmax(9rem,.65fr) auto auto;
        gap:.65rem;
        align-items:center;
        width:100%;
    }

    .audit-filter-control {
        position:relative;
        display:flex;
        align-items:center;
        min-width:0;
    }

    .audit-filter-control > i {
        position:absolute;
        left:1rem;
        z-index:2;
        color:#657563;
        pointer-events:none;
    }

    .audit-filter-control.is-select::after {
        content:"\F282";
        font-family:"bootstrap-icons";
        position:absolute;
        right:1rem;
        color:#657563;
        font-size:.85rem;
        pointer-events:none;
        transition:transform .16s ease,color .16s ease,opacity .16s ease;
        transform-origin:center;
    }
    .audit-filter-control.is-select.is-open::after {
        color:#2f3a2e;
        transform:rotate(180deg);
    }

    .audit-select {
        width:100%;
        min-height:2.65rem;
        border:1px solid #AEBFA6;
        border-radius:.5rem;
        background:#E9F0E4;
        background-image:none !important;
        color:#293229;
        font-size:.9rem;
        font-weight:650;
        padding:.45rem 2.45rem .45rem 2.55rem;
        box-shadow:none !important;
        outline:none;
        appearance:none;
        cursor:pointer;
    }

    .audit-select:hover,
    .audit-action:hover,
    .audit-details-button:hover {
        background:#DDE8D6;
    }

    .audit-select:focus {
        background:#F4F8EF;
        border-color:#8EA083;
        box-shadow:none;
    }

    .audit-action {
        min-height:2.65rem;
        border-radius:.5rem;
        border:1px solid #AEBFA6;
        background:#E9F0E4;
        color:#3E4A3D;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:.45rem;
        padding:0 .95rem;
        font-size:.84rem;
        font-weight:650;
        text-decoration:none;
        box-shadow:none !important;
        cursor:pointer;
        white-space:nowrap;
    }

    .audit-action-primary {
        background:#344333;
        border-color:#344333;
        color:#fff;
    }

    .audit-action-primary:hover {
        background:#2F3A2E;
        color:#fff;
    }

    .audit-scope-chip {
        min-height:2.65rem;
        border:1px solid #AEBFA6;
        border-radius:.5rem;
        background:#E1E7D9;
        color:#293229;
        display:flex;
        align-items:center;
        gap:.6rem;
        padding:.45rem .8rem;
        font-size:.84rem;
        font-weight:650;
    }

    .audit-ref-page .table-system-table {
        width:100%;
        table-layout:fixed;
        border-collapse:separate;
        border-spacing:0;
        background:#F7F9F3;
    }

    .audit-ref-page .table-system-table thead th {
        background:#C7D5BE !important;
        color:#566653 !important;
        font-size:.74rem;
        font-weight:720;
        letter-spacing:.05em;
        text-transform:uppercase;
        padding:.92rem 1rem;
        vertical-align:middle;
        white-space:nowrap;
    }

    .audit-ref-page .table-system-table tbody tr {
        background:#F7F9F3;
        transition:background-color .16s ease;
    }

    .audit-ref-page .table-system-table tbody tr:hover {
        background:#E9F0E4;
    }

    .audit-ref-page .table-system-table td {
        padding:1rem;
        vertical-align:top;
        border-top:1px solid #D4DEC9;
    }

    .audit-col-date { width:11rem; }
    .audit-col-user { width:15rem; }
    .audit-col-branch { width:8rem; }
    .audit-col-action { width:20rem; }
    .audit-col-target { width:13rem; }
    .audit-col-data { width:9rem; text-align:right; }

    .audit-primary {
        color:#293229;
        font-size:.95rem;
        font-weight:680;
        line-height:1.35;
    }

    .audit-secondary {
        color:#566653;
        font-size:.82rem;
        font-weight:560;
        line-height:1.35;
        margin-top:.15rem;
    }

    .audit-action-cell {
        display:flex;
        align-items:flex-start;
        gap:.55rem;
        min-width:0;
    }

    .audit-dot {
        width:.55rem;
        height:.55rem;
        border-radius:999px;
        margin-top:.35rem;
        flex:0 0 auto;
    }

    .audit-action-text {
        display:flex;
        flex-direction:column;
        gap:.35rem;
        min-width:0;
    }

    .audit-details-button {
        border:1px solid #AEBFA6;
        border-radius:.5rem;
        background:#E9F0E4;
        color:#3E4A3D;
        display:inline-flex;
        align-items:center;
        gap:.4rem;
        min-height:2.1rem;
        padding:0 .7rem;
        font-size:.78rem;
        font-weight:680;
        cursor:pointer;
        list-style:none;
    }

    .audit-details-button::-webkit-details-marker {
        display:none;
    }

    .audit-details-panel {
        position:absolute;
        right:0;
        top:calc(100% + .45rem);
        z-index:30;
        width:min(34rem, calc(100vw - 8rem));
        border:1px solid #8EA083;
        border-radius:.75rem;
        background:#E9F0E4;
        color:#293229;
        padding:1rem;
        text-align:left;
        box-shadow:none !important;
    }

    .audit-detail-section + .audit-detail-section {
        margin-top:.9rem;
        padding-top:.9rem;
        border-top:1px solid #AEBFA6;
    }

    .audit-detail-title {
        color:#566653;
        font-size:.72rem;
        font-weight:720;
        letter-spacing:.05em;
        text-transform:uppercase;
        margin-bottom:.45rem;
    }

    .audit-detail-line {
        color:#293229;
        font-size:.82rem;
        font-weight:560;
        line-height:1.45;
        overflow-wrap:anywhere;
    }

    .audit-detail-line strong {
        color:#566653;
        font-weight:680;
    }

    .audit-detail-load {
        color:#2F3A2E;
        text-decoration:underline;
        font-weight:680;
        cursor:pointer;
    }

    @media (max-width:1320px) {
        .audit-toolbar {
            grid-template-columns:repeat(3,minmax(12rem,1fr));
        }
    }

    @media (max-width:860px) {
        .audit-ref-page { padding:12px; }
        .audit-toolbar { grid-template-columns:1fr; }
        .audit-ref-page .table-system-wrap { overflow-x:auto; }
        .audit-ref-page .table-system-table { min-width:58rem; }
    }
</style>

<div class="admin-table-page audit-ref-page">
    <div class="audit-toast no-print" role="status" aria-live="polite" data-page-context-toast>
        <i class="bi bi-activity"></i>
        <span>You are viewing audit logs.</span>
    </div>

    <section class="table-system-card admin-table-card">
        <div class="table-system-toolbar admin-table-toolbar">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="audit-toolbar">
                <span class="audit-filter-control is-select">
                    <i class="bi bi-person"></i>
                    <select name="user_id" class="audit-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string)($filters['user_id'] ?? '') === (string)$user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </span>

                @if($isBranchAdminView ?? false)
                    {{-- Branch admins are locked to their assigned branch --}}
                    @php $lockedBranch = $branches->first(); @endphp
                    <div class="audit-scope-chip" title="Your audit log view is restricted to your assigned branch only.">
                        <i class="bi bi-lock"></i>
                        <span>
                            {{ $lockedBranch?->branch_code ?? 'Assigned Branch' }}
                            @if($lockedBranch?->branch_name)
                                - {{ $lockedBranch->branch_name }}
                            @endif
                        </span>
                    </div>
                @else
                    <span class="audit-filter-control is-select">
                        <i class="bi bi-building"></i>
                        <select name="branch_id" class="audit-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string)($filters['branch_id'] ?? '') === (string)$branch->id ? 'selected' : '' }}>{{ $branch->branch_code }}</option>
                            @endforeach
                        </select>
                    </span>
                @endif

                <span class="audit-filter-control is-select">
                    <i class="bi bi-lightning-charge"></i>
                    <select name="action_type" class="audit-select">
                        <option value="">Any Action</option>
                        @foreach($actionTypes as $type)
                            <option value="{{ $type }}" {{ ($filters['action_type'] ?? '') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </span>

                <span class="audit-filter-control">
                    <i class="bi bi-calendar-event"></i>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="audit-select" title="Date from">
                </span>

                <span class="audit-filter-control">
                    <i class="bi bi-calendar-check"></i>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="audit-select" title="Date to">
                </span>

                <span class="audit-filter-control is-select">
                    <i class="bi bi-list-ol"></i>
                    <select name="per_page" class="audit-select" title="Records per page">
                        @foreach(($perPageOptions ?? [25, 50, 100, 200]) as $size)
                            <option value="{{ $size }}" {{ (int)($filters['per_page'] ?? 25) === (int)$size ? 'selected' : '' }}>
                                {{ $size }} / page
                            </option>
                        @endforeach
                    </select>
                </span>

                <a href="{{ route('admin.audit-logs.index') }}" class="audit-action">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Clear</span>
                </a>
                <button type="submit" class="audit-action audit-action-primary">
                    <i class="bi bi-funnel"></i>
                    <span>Apply</span>
                </button>
            </form>
        </div>

        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                    <thead>
                        <tr>
                            <th class="text-left audit-col-date">Event Date</th>
                            <th class="text-left audit-col-user">User Details</th>
                            <th class="text-left audit-col-branch">Branch</th>
                            <th class="text-left audit-col-action">Action Taken</th>
                            <th class="text-left audit-col-target">Target Entity</th>
                            <th class="audit-col-data">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="group">
                                <td>
                                    @php
                                        $eventAt = $log->created_at?->copy()->setTimezone(config('app.audit_timezone', 'Asia/Manila'));
                                    @endphp
                                    <div class="audit-primary">{{ $eventAt?->format('d M Y') }}</div>
                                    <div class="audit-secondary">{{ $eventAt?->format('h:i A') }}</div>
                                </td>
                                <td>
                                    <div class="audit-primary">{{ $log->actor?->name ?? 'System' }}</div>
                                    <div class="audit-secondary">{{ $log->actor_role ?? 'Process' }}</div>
                                </td>
                                <td>
                                    <span class="status-badge status-badge-neutral">
                                        {{ $log->branch?->branch_code ?? 'SYS' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="audit-action-cell">
                                        @php
                                            $color = $typeColors[$log->action_type] ?? 'bg-slate-400';
                                            $label = $log->action_label
                                                ?? ($actionLabels[$log->action] ?? Str::headline(str_replace(['.', '_'], ' ', $log->action)));
                                            $statusPill = strtolower($log->status ?? 'success') === 'failed'
                                                ? 'status-badge status-badge-danger'
                                                : 'status-badge status-badge-success';
                                        @endphp
                                        <span class="audit-dot {{ $color }}"></span>
                                        <span class="audit-action-text">
                                            <span class="audit-primary">{{ $label }}</span>
                                            <span class="{{ $statusPill }}">
                                                {{ ucfirst($log->status ?? 'success') }}
                                            </span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="audit-secondary">
                                        {{ Str::headline(str_replace('_', ' ', class_basename($log->entity_type))) }} #{{ $log->entity_id }}
                                    </span>
                                </td>
                                <td class="audit-col-data relative">
                                    <details>
                                        <summary class="audit-details-button">
                                            <span>Details</span>
                                            <i class="bi bi-chevron-down"></i>
                                        </summary>
                                        @php
                                            $metadataJson = json_encode($log->metadata ?? []);
                                            $isHeavy = strlen($metadataJson) > 2000;
                                        @endphp
                                        <div class="audit-details-panel">
                                            @if($log->remarks)
                                                <div class="audit-detail-section">
                                                    <div class="audit-detail-title">Remarks</div>
                                                    <div class="audit-detail-line">{{ $log->remarks }}</div>
                                                </div>
                                            @endif

                                            <div class="audit-detail-section">
                                                <div class="audit-detail-title">Result</div>
                                                <div class="audit-detail-line">{{ ucfirst($log->status ?? 'success') }}</div>
                                            </div>

                                            @if(!empty($log->metadata['changes']))
                                                <div class="audit-detail-section">
                                                    <div class="audit-detail-title">Changes</div>
                                                    @foreach($log->metadata['changes'] as $change)
                                                        <div class="audit-detail-line">
                                                            <strong>{{ Str::headline($change['field'] ?? 'field') }}:</strong>
                                                            {{ $change['before'] ?? '-' }} -> {{ $change['after'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @php
                                                $snapshot = collect($log->metadata ?? [])
                                                    ->except(['changes'])
                                                    ->mapWithKeys(function ($value, $key) {
                                                        $labelMap = [
                                                            'case_status' => 'Case Status',
                                                            'payment_status' => 'Payment Status',
                                                            'payment_status_after' => 'Payment Status',
                                                            'entry_source' => 'Entry Source',
                                                            'package_id' => 'Package ID',
                                                            'amount' => 'Amount',
                                                            'receipt_number' => 'Payment Record No.',
                                                            'payment_record_no' => 'Payment Record No.',
                                                            'reference_no' => 'Receipt / OR No.',
                                                            'accounting_reference_no' => 'Receipt / OR No.',
                                                            'receipt_or_no' => 'Receipt / OR No.',
                                                            'reason' => 'Reason',
                                                        ];
                                                        $label = $labelMap[$key] ?? Str::headline(str_replace('_', ' ', $key));

                                                        if (is_string($value)) {
                                                            $value = Str::headline($value);
                                                        }
                                                        return [$label => $value];
                                                    });
                                            @endphp

                                            @if($snapshot->isNotEmpty() && !$isHeavy)
                                                <div class="audit-detail-section">
                                                    <div class="audit-detail-title">Record Snapshot</div>
                                                    @foreach($snapshot as $label => $value)
                                                        <div class="audit-detail-line">
                                                            <strong>{{ $label }}:</strong>
                                                            @if(is_array($value))
                                                                {{ json_encode($value) }}
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($isHeavy)
                                                <div class="audit-detail-section">
                                                    <div class="audit-detail-title">Record Snapshot</div>
                                                    <button class="audit-detail-load load-audit-details" data-log-id="{{ $log->id }}">Load details</button>
                                                    <div class="audit-secondary">Large payload deferred to keep page fast.</div>
                                                </div>
                                            @endif

                                            <div class="audit-detail-section">
                                                <div class="audit-detail-title">Security</div>
                                                <div class="audit-detail-line"><strong>IP Address:</strong> {{ $log->ip_address ?? 'n/a' }}</div>
                                                <div class="audit-detail-line"><strong>Device:</strong> {{ $log->user_agent ?? 'n/a' }}</div>
                                                <div class="audit-detail-line"><strong>Transaction:</strong> {{ $log->transaction_id ?? 'n/a' }}</div>
                                            </div>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-system-empty">No audit logs found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-system-pagination">
                @if($logs->hasPages()){{ $logs->links() }}@endif
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.load-audit-details').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-log-id');
            const container = btn.parentElement;
            btn.textContent = 'Loading...';
            try {
                const res = await fetch(`{{ route('admin.audit-logs.index') }}/${id}`, { headers: { 'Accept': 'application/json' }});
                if (!res.ok) throw new Error('Failed to load');
                const data = await res.json();
                const entries = [];
                const snapshot = data.metadata || {};
                delete snapshot.changes;
                Object.entries(snapshot).forEach(([k,v]) => {
                    entries.push(`<div class="audit-detail-line"><strong>${k.replace(/_/g,' ')}:</strong> ${typeof v === 'object' ? JSON.stringify(v) : v}</div>`);
                });
                container.innerHTML = `<div class="audit-detail-title">Record Snapshot</div>${entries.join('')}`;
            } catch (err) {
                btn.textContent = 'Retry';
                alert('Could not load details.');
            }
        });
    });
});
</script>
@endpush
