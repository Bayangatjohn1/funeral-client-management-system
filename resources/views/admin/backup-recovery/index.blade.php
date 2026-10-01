@extends('layouts.panel')

@section('page_title', 'Backup & Recovery')
@section('page_desc', 'Keep a secure copy of system records and private uploaded files.')

@section('topbar_actions')
    <button type="button" class="btn btn-primary-custom btn-sm" data-open-backup-dialog>
        <i class="bi bi-plus-circle" aria-hidden="true"></i>
        <span>Create backup</span>
    </button>
@endsection

@section('content')
@php
    $isHealthy = $lastSuccessful?->verified_at?->gte(now()->subHours(26)) ?? false;
    try {
        $scheduledBackupTime = \Carbon\Carbon::createFromFormat('H:i', config('backup.daily_time'))->format('g:i A');
    } catch (\Throwable) {
        $scheduledBackupTime = config('backup.daily_time');
    }
    $statusStyles = [
        'verified' => ['Verified', 'success', 'bi-shield-check'],
        'failed' => ['Failed', 'danger', 'bi-exclamation-octagon'],
        'expired' => ['Expired', 'muted', 'bi-clock-history'],
        'restore_pending' => ['Restore pending', 'warning', 'bi-arrow-counterclockwise'],
    ];
@endphp
<style>
.recovery-page{width:min(100%,1640px);margin:auto;padding:clamp(.6rem,1.5vw,1.25rem) clamp(1rem,2.4vw,2.25rem) 2rem;color:var(--color-text-primary)}
.recovery-page *{min-width:0}.recovery-stack{display:grid;gap:1rem}.recovery-icon{display:grid;place-items:center;width:44px;height:44px;flex:0 0 auto;border-radius:12px;background:#fff9;color:#315f43;font-size:1.15rem}
.recovery-panel h2{margin:0;font-family:var(--font-heading)}.recovery-panel p{margin:.2rem 0 0;color:var(--color-text-secondary);font-size:.78rem}
.recovery-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem}.recovery-metric{display:grid;grid-template-columns:auto 1fr;align-items:center;gap:.72rem;min-height:94px;padding:.85rem;border:1px solid #c5d0bf;border-radius:14px;background:#e4ecdf}.recovery-metric:nth-child(2){background:#eee3cf}.recovery-metric:nth-child(3){background:#e9ddd2}.recovery-metric:nth-child(4){background:#e8e7d7}
.recovery-metric i{display:grid;place-items:center;width:36px;height:36px;border-radius:10px;background:#fff8;color:#315f43}.recovery-metric span{display:block;color:var(--color-text-secondary);font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.recovery-metric strong{display:block;margin-top:.12rem;font-size:.82rem;line-height:1.3}
.recovery-panel{overflow:hidden;border:1px solid #c4d0be;border-radius:16px;background:#e3eddf}.recovery-panel-head{display:flex;align-items:center;gap:.62rem;padding:.85rem 1rem;border-bottom:1px solid #788a7429}.recovery-panel-head .recovery-icon{width:34px;height:34px;font-size:.9rem}.recovery-panel h2{font-size:.94rem}.recovery-panel-body{padding:1rem}
.recovery-form{display:grid;gap:.8rem}.recovery-label{display:grid;gap:.32rem;font-size:.7rem;font-weight:850}.recovery-input{width:100%;min-height:43px;border:1px solid #aebda8!important;border-radius:9px!important;background:#f5f7f1!important}.recovery-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;min-height:42px;padding:.65rem .85rem;border:1px solid #315f43;border-radius:9px;background:#315f43;color:#fff;font-size:.7rem;font-weight:850;cursor:pointer}.recovery-btn:hover,.recovery-btn:focus-visible{background:#234b34;outline:3px solid #315f432b;outline-offset:2px}.recovery-note{display:flex;gap:.55rem;padding:.7rem;border-radius:10px;background:#d2e2cf;color:#3c5945;font-size:.65rem;line-height:1.5}
.recovery-alert{display:flex;gap:.6rem;align-items:flex-start;padding:.75rem .85rem;border:1px solid;border-radius:11px;font-size:.7rem}.recovery-alert.success{background:#d6e7d3;border-color:#a8c4a3;color:#285d3e}.recovery-alert.error{background:#efd7d1;border-color:#d5aaa1;color:#853c34}.recovery-alert ul{margin:0;padding-left:1rem}
.recovery-table-wrap{overflow-x:auto}.recovery-table{width:100%;min-width:760px;border-collapse:collapse}.recovery-table th{padding:.72rem .8rem;background:#d3dfce;color:#526158;font-size:.58rem;text-align:left;text-transform:uppercase;letter-spacing:.08em}.recovery-table td{padding:.75rem .8rem;border-top:1px solid #becbb8;font-size:.7rem;vertical-align:middle}.recovery-table tbody tr:hover td{background:#d9e6d5}.recovery-id{font-weight:850}.recovery-sub{display:block;color:var(--color-text-secondary);font-size:.58rem}.status-pill{display:inline-flex;align-items:center;gap:.3rem;padding:.28rem .48rem;border-radius:999px;font-size:.58rem;font-weight:850}.status-pill.success{background:#cce1c9;color:#285d3e}.status-pill.danger{background:#efd0ca;color:#923d34}.status-pill.warning{background:#f0dfbc;color:#75521f}.status-pill.muted{background:#d9ded5;color:#586158}
.recovery-actions{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}.recovery-action{display:inline-flex;align-items:center;gap:.3rem;min-height:34px;padding:.4rem .52rem;border:1px solid #a8b9a2;border-radius:8px;background:#eef3e9;color:#315f43;font-size:.61rem;font-weight:850;cursor:pointer}.recovery-action:hover,.recovery-action:focus-visible{background:#cadfc7;outline:2px solid #315f4326;outline-offset:1px}.recovery-action.warning{border-color:#d0a66a;background:#f2e4c8;color:#7c531d}.recovery-pagination{padding:.7rem .85rem;border-top:1px solid #bdcab7;background:#d8e3d3}
.recovery-empty{padding:2.5rem 1rem;text-align:center;color:var(--color-text-secondary)}.recovery-empty i{display:block;margin-bottom:.45rem;font-size:1.4rem;color:#71816e}.recovery-empty strong{display:block;color:var(--color-text-primary)}
.backup-dialog{width:min(94vw,480px);padding:0;border:1px solid #aebda8;border-radius:16px;background:#e8efe3;color:var(--color-text-primary);box-shadow:0 24px 70px #142c2145}.backup-dialog::backdrop{background:#13291f80;backdrop-filter:blur(3px)}.backup-dialog-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1rem;border-bottom:1px solid #becbb8}.backup-dialog-title{display:flex;align-items:center;gap:.65rem}.backup-dialog-title .recovery-icon{width:38px;height:38px;font-size:1rem}.backup-dialog-title h2{margin:0;font:700 1rem var(--font-heading)}.backup-dialog-title p{margin:.15rem 0 0;color:var(--color-text-secondary);font-size:.67rem}.backup-dialog-close{display:grid;place-items:center;width:36px;height:36px;border:1px solid #b8c6b2;border-radius:9px;background:#f3f6ef;color:#405242;cursor:pointer}.backup-dialog-body{display:grid;gap:.85rem;padding:1rem}.backup-includes{display:grid;gap:.45rem;margin:0;padding:0;list-style:none}.backup-includes li{display:flex;align-items:center;gap:.5rem;padding:.62rem .7rem;border-radius:9px;background:#d8e5d4;color:#3e5343;font-size:.68rem;font-weight:700}.backup-includes i{color:#315f43}.backup-dialog-actions{display:flex;justify-content:flex-end;gap:.5rem;padding-top:.15rem}.backup-cancel{min-height:42px;padding:.6rem .85rem;border:1px solid #aebda8;border-radius:9px;background:#f2f5ef;color:#405242;font-size:.7rem;font-weight:800;cursor:pointer}.backup-field-error{display:flex;align-items:center;gap:.35rem;margin-top:.05rem;color:#923d34;font-size:.64rem;font-weight:750}.recovery-input.has-error{border-color:#b95b50!important;background:#fff7f5!important;box-shadow:0 0 0 3px #b95b5017!important}.recovery-input.has-error:focus{border-color:#923d34!important;box-shadow:0 0 0 3px #b95b502b!important}
.restore-dialog{width:min(94vw,520px);background:#f2ead9;border-color:#c6a875}.restore-dialog .backup-dialog-head{border-bottom-color:#d6bf94}.restore-dialog .recovery-icon{color:#80571f}.restore-summary{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.7rem .75rem;border:1px solid #d5bd92;border-radius:10px;background:#fbf6eb}.restore-summary strong{font-size:.72rem}.restore-summary span{color:#725936;font-size:.62rem}.restore-warning{display:flex;gap:.5rem;padding:.7rem .75rem;border-radius:10px;background:#ead5ab;color:#684818;font-size:.64rem;line-height:1.45}.restore-fields{display:grid;gap:.7rem}.restore-fields label{display:grid;gap:.3rem;font-size:.68rem;font-weight:850}.restore-fields textarea{min-height:82px;resize:vertical}.restore-confirm-hint{color:#725936;font-size:.59rem;line-height:1.4}.restore-submit{border-color:#8a5e23;background:#8a5e23}.restore-submit:hover,.restore-submit:focus-visible{background:#704816}
@media(max-width:1050px){.recovery-metrics{grid-template-columns:repeat(2,1fr)}}@media(max-width:640px){.recovery-page{padding:.6rem .75rem 1.5rem}.recovery-metrics{grid-template-columns:1fr}.backup-dialog-actions{display:grid}.backup-dialog-actions>*{width:100%}}
</style>

<div class="recovery-page recovery-stack animate-float-up">
    @if(session('success'))<div class="recovery-alert success" role="status"><i class="bi bi-check-circle-fill"></i><strong>{{ session('success') }}</strong></div>@endif
    @if($errors->any())<div class="recovery-alert error" role="alert"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif

    <section class="recovery-metrics" aria-label="Backup status overview">
        <article class="recovery-metric"><i class="bi bi-check2-circle"></i><div><span>Last verified</span><strong>{{ $lastSuccessful?->verified_at?->format('M d, Y · g:i A') ?? 'No verified backup' }}</strong></div></article>
        <article class="recovery-metric"><i class="bi bi-activity"></i><div><span>Readiness</span><strong>{{ $isHealthy ? 'Healthy' : 'Action required' }}</strong></div></article>
        <article class="recovery-metric"><i class="bi bi-calendar2-check"></i><div><span>Automatic schedule</span><strong>Daily at {{ $scheduledBackupTime }}</strong></div></article>
        <article class="recovery-metric"><i class="bi bi-lock"></i><div><span>Storage protection</span><strong>{{ ucfirst(config('backup.disk')) }} · Encrypted</strong></div></article>
    </section>

    <section class="recovery-panel">
            <header class="recovery-panel-head"><span class="recovery-icon"><i class="bi bi-clock-history"></i></span><div><h2>Backup history</h2><p>Review saved backups and available actions.</p></div></header>
            <div class="recovery-table-wrap"><table class="recovery-table"><thead><tr><th>Backup</th><th>Created by</th><th>Status</th><th>Size</th><th>Available until</th><th>Actions</th></tr></thead><tbody>
            @forelse($backups as $backup)
                @php $status = $statusStyles[$backup->status] ?? [\Illuminate\Support\Str::headline($backup->status), 'muted', 'bi-circle']; @endphp
                <tr><td><span class="recovery-id">Backup #{{ $backup->id }}</span><span class="recovery-sub">{{ $backup->created_at->format('M d, Y - g:i A') }}</span></td><td>{{ $backup->creator?->name ?? ($backup->type === 'scheduled' ? 'Automatic schedule' : 'System Admin') }}</td><td><span class="status-pill {{ $status[1] }}"><i class="bi {{ $status[2] }}"></i>{{ $status[0] }}</span></td><td>{{ $backup->size_bytes ? number_format($backup->size_bytes / 1048576, 2).' MB' : '-' }}</td><td>{{ $backup->expires_at?->format('M d, Y') ?? '-' }}</td><td><div class="recovery-actions">@if($backup->path)
                    <form method="POST" action="{{ route('admin.backups.verify', $backup) }}" data-submit-lock>@csrf<button type="submit" class="recovery-action" aria-label="Check backup {{ $backup->id }}"><i class="bi bi-patch-check"></i>Check</button></form>
                    <a class="recovery-action" href="{{ route('admin.backups.download', $backup) }}" download><i class="bi bi-download"></i>Download</a>
                    <button type="button" class="recovery-action warning" data-open-restore-dialog data-action="{{ route('admin.backups.restore-request', $backup) }}" data-backup-label="Backup #{{ $backup->id }}" data-backup-date="{{ $backup->created_at->format('M d, Y - g:i A') }}"><i class="bi bi-arrow-counterclockwise"></i>Prepare restore</button>@endif</div></td></tr>
            @empty
                <tr><td colspan="6"><div class="recovery-empty"><i class="bi bi-database"></i><strong>No backups yet</strong><span>Use Create backup at the top of the page to make the first secure copy.</span></div></td></tr>
            @endforelse
            </tbody></table></div><div class="recovery-pagination">{{ $backups->links() }}</div>
    </section>
</div>
<dialog class="backup-dialog" data-backup-dialog aria-labelledby="backup-dialog-title">
    <header class="backup-dialog-head"><div class="backup-dialog-title"><span class="recovery-icon"><i class="bi bi-database-add"></i></span><div><h2 id="backup-dialog-title">Create a new backup</h2><p>A secure copy will be created and checked automatically.</p></div></div><button type="button" class="backup-dialog-close" data-close-backup-dialog aria-label="Close"><i class="bi bi-x-lg"></i></button></header>
    <form method="POST" action="{{ route('admin.backups.store') }}" class="backup-dialog-body" data-submit-lock>@csrf
        <ul class="backup-includes" aria-label="Included in this backup"><li><i class="bi bi-database-check"></i>System records and database information</li><li><i class="bi bi-folder-check"></i>Private documents and uploaded files</li><li><i class="bi bi-lock"></i>Encrypted and checked before saving</li></ul>
        <label class="recovery-label" for="backup-password">Enter your password to continue<input id="backup-password" type="password" name="password" autocomplete="current-password" required class="recovery-input {{ $errors->createBackup->has('password') ? 'has-error' : '' }}" placeholder="Your account password" aria-invalid="{{ $errors->createBackup->has('password') ? 'true' : 'false' }}" @if($errors->createBackup->has('password')) aria-describedby="backup-password-error" @endif></label>
        @error('password', 'createBackup')<div id="backup-password-error" class="backup-field-error" role="alert"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span>{{ $message }}</span></div>@enderror
        <div class="backup-dialog-actions"><button type="button" class="backup-cancel" data-close-backup-dialog>Cancel</button><button type="submit" class="recovery-btn"><i class="bi bi-database-check"></i><span>Create backup</span></button></div>
    </form>
</dialog>
<dialog class="backup-dialog restore-dialog" data-restore-dialog aria-labelledby="restore-dialog-title">
    <header class="backup-dialog-head"><div class="backup-dialog-title"><span class="recovery-icon"><i class="bi bi-arrow-counterclockwise"></i></span><div><h2 id="restore-dialog-title">Prepare a restore request</h2><p>Confirm the backup and explain why it may be needed.</p></div></div><button type="button" class="backup-dialog-close" data-close-restore-dialog aria-label="Close"><i class="bi bi-x-lg"></i></button></header>
    <form method="POST" action="" class="backup-dialog-body" data-restore-form data-submit-lock>@csrf
        <div class="restore-summary"><strong data-restore-backup-label>Selected backup</strong><span data-restore-backup-date></span></div>
        <div class="restore-warning"><i class="bi bi-info-circle-fill" aria-hidden="true"></i><span>This only prepares and checks a restore request. Your current system data will not be replaced yet.</span></div>
        <div class="restore-fields">
            <label for="restore-reason">Why do you need this backup?<textarea id="restore-reason" name="reason" required minlength="10" placeholder="Briefly explain the problem or reason for recovery"></textarea></label>
            <label for="restore-password">Enter your password to continue<input id="restore-password" type="password" name="password" required autocomplete="current-password" placeholder="Your account password"></label>
            <label for="restore-confirmation">Type the confirmation phrase<input id="restore-confirmation" name="confirmation" required placeholder="RESTORE FUNERAL SYSTEM" autocomplete="off"></label>
            <div class="restore-confirm-hint">Type <strong>RESTORE FUNERAL SYSTEM</strong> exactly as shown. This prevents an accidental request.</div>
        </div>
        <div class="backup-dialog-actions"><button type="button" class="backup-cancel" data-close-restore-dialog>Cancel</button><button type="submit" class="recovery-btn restore-submit"><i class="bi bi-shield-check"></i><span>Prepare restore request</span></button></div>
    </form>
</dialog>
<script>
const backupDialog=document.querySelector('[data-backup-dialog]');document.querySelectorAll('[data-open-backup-dialog]').forEach((button)=>button.addEventListener('click',()=>backupDialog?.showModal()));document.querySelectorAll('[data-close-backup-dialog]').forEach((button)=>button.addEventListener('click',()=>backupDialog?.close()));backupDialog?.addEventListener('click',(event)=>{if(event.target===backupDialog)backupDialog.close();});
const restoreDialog=document.querySelector('[data-restore-dialog]');const restoreForm=document.querySelector('[data-restore-form]');document.querySelectorAll('[data-open-restore-dialog]').forEach((button)=>button.addEventListener('click',()=>{if(!restoreDialog||!restoreForm)return;restoreForm.reset();restoreForm.action=button.dataset.action||'';restoreDialog.querySelector('[data-restore-backup-label]').textContent=button.dataset.backupLabel||'Selected backup';restoreDialog.querySelector('[data-restore-backup-date]').textContent=button.dataset.backupDate||'';restoreDialog.showModal();requestAnimationFrame(()=>document.getElementById('restore-reason')?.focus());}));document.querySelectorAll('[data-close-restore-dialog]').forEach((button)=>button.addEventListener('click',()=>restoreDialog?.close()));restoreDialog?.addEventListener('click',(event)=>{if(event.target===restoreDialog)restoreDialog.close();});
@if(session('open_backup_dialog'))
if(backupDialog&&!backupDialog.open){backupDialog.showModal();requestAnimationFrame(()=>document.getElementById('backup-password')?.focus());}
@endif
document.querySelectorAll('[data-submit-lock]').forEach((form)=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"]');if(!button)return;button.disabled=true;button.setAttribute('aria-busy','true');const label=button.querySelector('span');if(label)label.textContent='Processing…';}));
</script>
@endsection
