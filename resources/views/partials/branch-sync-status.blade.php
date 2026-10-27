@if(config('branch_sync.mode') !== 'standalone' && auth()->check())
    @php
        $syncMode = config('branch_sync.mode');
        $syncStatus = $syncMode === 'branch' ? app(\App\Services\BranchSync\BranchSyncService::class)->status() : null;
        $syncPeers = $syncMode === 'central'
            ? \Illuminate\Support\Facades\DB::table('branches')
                ->leftJoin('branch_sync_peers', 'branches.branch_code', '=', 'branch_sync_peers.branch_code')
                ->when(!auth()->user()->isOwner() && !auth()->user()->isSystemAdmin(), fn($query) => $query->where('branches.id', auth()->user()->branch_id))
                ->select('branches.branch_name', 'branches.branch_code', 'branch_sync_peers.last_received_at', 'branch_sync_peers.last_seen_at', 'branch_sync_peers.pending_changes', 'branch_sync_peers.enabled', 'branch_sync_peers.activation_state')
                ->orderBy('branches.branch_code')->get()
            : collect();
        $formatSyncTime = fn($value) => $value ? \Illuminate\Support\Carbon::parse($value)->timezone('Asia/Manila')->format('M d, Y g:i A').' PHT' : 'Never';
    @endphp
    <details class="branch-sync-banner">
        <summary>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            @if($syncMode === 'branch')
                <span>Branch synchronization · {{ $syncStatus['branch'] }} ·
                    {{ $syncStatus['sync_state'] }}
                </span>
            @else
                <span>Branch data freshness · Reports reflect the last received branch snapshot</span>
            @endif
        </summary>
        <div class="branch-sync-banner__body">
            @if($syncStatus)
                <dl>
                    <div><dt>Connection</dt><dd>{{ $syncStatus['connection_state'] }}</dd></div>
                    <div><dt>Records</dt><dd>{{ $syncStatus['saved_locally'] ? 'Saved locally · Pending sync' : $syncStatus['sync_state'] }}</dd></div>
                    <div><dt>Last successful synchronization</dt><dd>{{ $formatSyncTime($syncStatus['last_success_at']) }}</dd></div>
                    <div><dt>Catalog last checked</dt><dd>{{ $formatSyncTime($syncStatus['catalog_last_synced_at']) }}</dd></div>
                    <div><dt>Pending snapshots</dt><dd>{{ $syncStatus['pending_snapshots'] }}{{ $syncStatus['uncaptured_changes'] ? ' · Additional local changes await capture' : '' }}</dd></div>
                </dl>
                @if($syncStatus['last_error'])<p role="status">{{ $syncStatus['last_error'] }}</p>@endif
                <p>Connection reflects the last central check. Saved local records stay on this server. Status refreshes when you load a page.</p>
            @else
                <ul>
                    @foreach($syncPeers as $syncPeer)
                        <li><strong>{{ $syncPeer->branch_name }}</strong> · Last received: {{ $formatSyncTime($syncPeer->last_received_at) }}
                            · Last contact: {{ $formatSyncTime($syncPeer->last_seen_at) }}
                            @if($syncPeer->enabled === null) · Local sync not configured
                            @elseif($syncPeer->activation_state === 'prepared') · Preparing branch · Recording remains central
                            @elseif($syncPeer->activation_state === 'frozen') · Cutover checks in progress · Recording paused
                            @elseif($syncPeer->activation_state === 'cancelled') · Preparation cancelled · Recording remains central
                            @elseif(!$syncPeer->enabled) · Sync access revoked
                            @elseif($syncPeer->pending_changes || !$syncPeer->last_seen_at || \Illuminate\Support\Carbon::parse($syncPeer->last_seen_at)->lt(now()->subMinutes(5))) · Data may be outdated
                            @else · No changes pending at last contact
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p>This indicates data freshness, not a live internet connection check.</p>
            @endif
        </div>
    </details>
@endif
