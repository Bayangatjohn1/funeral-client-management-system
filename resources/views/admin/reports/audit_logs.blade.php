@extends('layouts.panel')

@section('page_title', 'Audit Logs')

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
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">System Audit Trail</h1>
            <p class="text-slate-500 text-sm">Monitor user activities and data changes across all branches.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status:</span>
            <span class="flex items-center gap-1.5 py-1 px-2.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Live Logging
            </span>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 overflow-hidden">
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="p-3">
            <div class="flex flex-wrap items-center gap-3">
            
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </span>
                    <select name="user_id" class="pl-10 block w-full bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-indigo-500 rounded-lg text-sm transition-all py-2" onchange="this.form.submit()">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string)($filters['user_id'] ?? '') === (string)$user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="w-full md:w-44">
                <select name="branch_id" class="block w-full bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-indigo-500 rounded-lg text-sm py-2" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string)($filters['branch_id'] ?? '') === (string)$branch->id ? 'selected' : '' }}>{{ $branch->branch_code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-40">
                <select name="action_type" class="block w-full bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-indigo-500 rounded-lg text-sm py-2" onchange="this.form.submit()">
                    <option value="">Any Action</option>
                    @foreach($actionTypes as $type)
                        <option value="{{ $type }}" {{ ($filters['action_type'] ?? '') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center bg-slate-50 rounded-lg px-2 border border-transparent focus-within:bg-white focus-within:ring-2 focus-within:ring-indigo-500 transition-all">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="bg-transparent border-none text-xs focus:ring-0 py-2 w-32" onchange="this.form.submit()" />
                <span class="text-slate-400 px-1 text-xs">–</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="bg-transparent border-none text-xs focus:ring-0 py-2 w-32" onchange="this.form.submit()" />
            </div>

            <div class="flex items-center gap-2 ml-auto">
                <a href="{{ route('admin.audit-logs.index') }}" class="p-2 text-slate-400 hover:text-slate-600 transition" title="Clear All">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </a>
            </div>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-widest font-bold">
                    <th class="px-6 py-4">Event Date</th>
                    <th class="px-6 py-4">User Details</th>
                    <th class="px-6 py-4 text-center">Branch</th>
                    <th class="px-6 py-4">Action Taken</th>
                    <th class="px-6 py-4">Target Entity</th>
                    <th class="px-6 py-4 text-right">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50/50 transition-colors group">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-slate-700">{{ $log->created_at?->format('d M Y') }}</div>
                        <div class="text-[11px] text-slate-400 font-medium tracking-tight">{{ $log->created_at?->format('h:i A') }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-slate-800">{{ $log->actor?->name ?? 'System' }}</div>
                        <div class="text-xs text-slate-500">{{ $log->actor_role ?? 'Process' }}</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-block px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-tight">
                            {{ $log->branch?->branch_code ?? 'SYS' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                             @php
                                $color = $typeColors[$log->action_type] ?? 'bg-slate-400';
                                $label = $log->action_label
                                    ?? ($actionLabels[$log->action] ?? Str::headline(str_replace(['.', '_'], ' ', $log->action)));
                                $statusPill = strtolower($log->status ?? 'success') === 'failed'
                                    ? 'bg-rose-100 text-rose-700 border border-rose-200'
                                    : 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                             @endphp
                             <span class="h-2 w-2 rounded-full {{ $color }}"></span>
                             <span class="text-xs font-bold text-slate-700">{{ $label }}</span>
                             <span class="text-[10px] font-semibold px-2 py-1 rounded-full {{ $statusPill }}">
                                {{ ucfirst($log->status ?? 'success') }}
                             </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-xs text-slate-600 font-medium bg-slate-50 border border-slate-100 px-2 py-0.5 rounded">
                            {{ Str::headline(str_replace('_', ' ', class_basename($log->entity_type))) }} #{{ $log->entity_id }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right relative">
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
                                    <div class="text-indigo-400 font-bold mb-1 flex items-center gap-1">RESULT <span class="text-[9px]">✔</span></div>
                                    <div class="text-slate-100">{{ ucfirst($log->status ?? 'success') }}</div>
                                </div>

                                @if(!empty($log->metadata['changes']))
                                    <div>
                                        <div class="text-indigo-400 font-bold mb-1 border-b border-slate-700 pb-1 flex items-center gap-1">CHANGES <span class="text-[9px]">🔄</span></div>
                                        @foreach($log->metadata['changes'] as $change)
                                            <div class="mb-1">
                                                <span class="text-slate-500">{{ Str::headline($change['field'] ?? 'field') }}:</span>
                                                {{ $change['before'] ?? '-' }} → {{ $change['after'] }}
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
                                    <div class="text-indigo-400 font-bold mb-1 flex items-center gap-1">SECURITY <span class="text-[9px]">🔒</span></div>
                                    <div class="text-slate-300">IP Address: {{ $log->ip_address ?? 'n/a' }}</div>
                                    <div class="text-slate-300">Device: {{ $log->user_agent ?? 'n/a' }}</div>
                                    <div class="text-slate-500">Txn: {{ $log->transaction_id ?? 'n/a' }}</div>
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
                @empty
                    @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
        {{ $logs->links() }}
    </div>
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
