@extends('layouts.panel')

@section('page_title', 'Audit Logs')
@section('page_desc', 'Track user actions and system activity history.')

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

<div class="admin-table-page">
    <section class="table-system-card admin-table-card">
        <div class="table-system-head">
            <div class="admin-table-head-row">
                <div>
                    <h2 class="table-system-title">System Audit Trail</h2>
                    <p class="admin-table-head-copy">Monitor user activities and data changes across all branches.</p>
                </div>
                <div class="admin-table-head-actions">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status:</span>
                    <span class="status-badge status-badge-success inline-flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Logging
                    </span>
                </div>
            </div>
        </div>

        <div class="table-system-toolbar admin-table-toolbar">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="table-toolbar">
                <div class="table-toolbar-field">
                    <select name="user_id" class="table-toolbar-select" onchange="this.form.submit()">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string)($filters['user_id'] ?? '') === (string)$user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-toolbar-field">
                    <select name="branch_id" class="table-toolbar-select" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string)($filters['branch_id'] ?? '') === (string)$branch->id ? 'selected' : '' }}>{{ $branch->branch_code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-toolbar-field">
                    <select name="action_type" class="table-toolbar-select" onchange="this.form.submit()">
                        <option value="">Any Action</option>
                        @foreach($actionTypes as $type)
                            <option value="{{ $type }}" {{ ($filters['action_type'] ?? '') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-toolbar-field">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="table-toolbar-select" onchange="this.form.submit()" title="Date from">
                </div>

                <div class="table-toolbar-field">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="table-toolbar-select" onchange="this.form.submit()" title="Date to">
                </div>

                <div class="table-toolbar-reset-wrap">
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                    <thead>
                        <tr>
                            <th class="text-left">Event Date</th>
                            <th class="text-left">User Details</th>
                            <th class="text-left">Branch</th>
                            <th class="text-left">Action Taken</th>
                            <th class="text-left">Target Entity</th>
                            <th class="table-col-actions">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="group">
                                <td>
                                    <div class="table-primary">{{ $log->created_at?->format('d M Y') }}</div>
                                    <div class="table-secondary">{{ $log->created_at?->format('h:i A') }}</div>
                                </td>
                                <td>
                                    <div class="table-primary">{{ $log->actor?->name ?? 'System' }}</div>
                                    <div class="table-secondary">{{ $log->actor_role ?? 'Process' }}</div>
                                </td>
                                <td>
                                    <span class="status-badge status-badge-neutral">
                                        {{ $log->branch?->branch_code ?? 'SYS' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @php
                                            $color = $typeColors[$log->action_type] ?? 'bg-slate-400';
                                            $label = $log->action_label
                                                ?? ($actionLabels[$log->action] ?? Str::headline(str_replace(['.', '_'], ' ', $log->action)));
                                            $statusPill = strtolower($log->status ?? 'success') === 'failed'
                                                ? 'status-badge status-badge-danger'
                                                : 'status-badge status-badge-success';
                                        @endphp
                                        <span class="h-2 w-2 rounded-full {{ $color }}"></span>
                                        <span class="text-xs font-bold text-slate-700">{{ $label }}</span>
                                        <span class="{{ $statusPill }}">
                                            {{ ucfirst($log->status ?? 'success') }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="table-secondary">
                                        {{ Str::headline(str_replace('_', ' ', class_basename($log->entity_type))) }} #{{ $log->entity_id }}
                                    </span>
                                </td>
                                <td class="table-col-actions relative">
                                    <details class="group/details">
                                        <summary class="list-none cursor-pointer text-indigo-600 hover:text-indigo-800 text-xs font-bold inline-flex items-center">
                                            Details
                                            <svg class="w-3 h-3 ml-1 group-open/details:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </summary>
                                        @php
                                            $metadataJson = json_encode($log->metadata ?? []);
                                            $isHeavy = strlen($metadataJson) > 2000;
                                        @endphp
                                        <div class="absolute right-0 top-full mt-2 w-80 z-10 bg-slate-900 text-slate-200 text-[11px] p-4 rounded-lg shadow-xl text-left font-mono space-y-3">
                                            @if($log->remarks)
                                                <div>
                                                    <div class="text-indigo-400 font-bold mb-1">REMARKS</div>
                                                    <div class="text-slate-100">{{ $log->remarks }}</div>
                                                </div>
                                            @endif

                                            <div>
                                                <div class="text-indigo-400 font-bold mb-1 flex items-center gap-1">RESULT <span class="text-[9px]">OK</span></div>
                                                <div class="text-slate-100">{{ ucfirst($log->status ?? 'success') }}</div>
                                            </div>

                                            @if(!empty($log->metadata['changes']))
                                                <div>
                                                    <div class="text-indigo-400 font-bold mb-1 border-b border-slate-700 pb-1 flex items-center gap-1">CHANGES <span class="text-[9px]">CHG</span></div>
                                                    @foreach($log->metadata['changes'] as $change)
                                                        <div class="mb-1">
                                                            <span class="text-slate-500">{{ Str::headline($change['field'] ?? 'field') }}:</span>
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
                                                            'receipt_number' => 'Receipt No.',
                                                            'reference_no' => 'Reference No.',
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
                                                <div>
                                                    <div class="text-indigo-400 font-bold mb-1 border-b border-slate-700 pb-1">RECORD SNAPSHOT</div>
                                                    @foreach($snapshot as $label => $value)
                                                        <div class="mb-1">
                                                            <span class="text-slate-500">{{ $label }}:</span>
                                                            @if(is_array($value))
                                                                {{ json_encode($value) }}
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($isHeavy)
                                                <div>
                                                    <div class="text-indigo-400 font-bold mb-1 border-b border-slate-700 pb-1">RECORD SNAPSHOT</div>
                                                    <button class="text-indigo-300 underline hover:text-indigo-100 load-audit-details" data-log-id="{{ $log->id }}">Load details</button>
                                                    <div class="text-slate-500 text-[10px]">Large payload deferred to keep page fast.</div>
                                                </div>
                                            @endif

                                            <div>
                                                <div class="text-indigo-400 font-bold mb-1 flex items-center gap-1">SECURITY <span class="text-[9px]">SEC</span></div>
                                                <div class="text-slate-300">IP Address: {{ $log->ip_address ?? 'n/a' }}</div>
                                                <div class="text-slate-300">Device: {{ $log->user_agent ?? 'n/a' }}</div>
                                                <div class="text-slate-500">Txn: {{ $log->transaction_id ?? 'n/a' }}</div>
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
                {{ $logs->links() }}
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
                    entries.push(`<div class="mb-1"><span class="text-slate-500">${k.replace(/_/g,' ')}:</span> ${typeof v === 'object' ? JSON.stringify(v) : v}</div>`);
                });
                container.innerHTML = `<div class="text-indigo-400 font-bold mb-1 border-b border-slate-700 pb-1">RECORD SNAPSHOT</div>${entries.join('')}`;
            } catch (err) {
                btn.textContent = 'Retry';
                alert('Could not load details.');
            }
        });
    });
});
</script>
@endpush
