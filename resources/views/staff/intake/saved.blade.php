@extends('layouts.panel')
@section('page_title', 'Case saved')
@section('content')
@php
    $other = session('intake_saved_mode') === 'other';
    $recordsUrl = $other ? route('funeral-cases.other-reports') : route('funeral-cases.index', ['record_scope' => 'main']);
@endphp
<dialog id="intakeSavedConfirmation" aria-labelledby="intakeSavedTitle" aria-describedby="intakeSavedDescription">
    <h2 id="intakeSavedTitle">What would you like to do next?</h2>
    <p id="intakeSavedDescription">Case {{ session('intake_payment.case_code') }} has been saved successfully.</p>
    <div class="saved-actions">
        <a id="intakeSavedNew" href="{{ route($other ? 'intake.other.create' : 'intake.main.create') }}">Create New Case</a>
        <a id="intakeSavedDone" href="{{ $recordsUrl }}">View Case Records</a>
    </div>
</dialog>
<style>
body .main-area,body .flash-toast-stack{display:none !important}
body .app-shell{pointer-events:none}
body.panel-shell-body{background:var(--staff-bg,#edf2e9) !important;min-height:100dvh}
#intakeSavedConfirmation{width:min(460px,calc(100% - 32px));margin:auto;padding:28px;border:1px solid var(--staff-border,#dce3da);border-radius:18px;background:var(--staff-card,#fff);color:var(--staff-ink,#203329);box-shadow:0 24px 80px rgb(0 0 0 / .2)}
#intakeSavedConfirmation::backdrop{background:var(--modal-scrim)}
#intakeSavedConfirmation h2{margin:0;font-size:22px;line-height:1.3;color:var(--staff-ink,#203329) !important}
#intakeSavedConfirmation p{margin:12px 0 24px;color:var(--staff-muted,#5a6b60) !important}
#intakeSavedConfirmation .saved-actions{display:flex;gap:12px;flex-wrap:wrap}
#intakeSavedConfirmation a{flex:1;min-height:44px;display:flex;align-items:center;justify-content:center;padding:12px;border:1px solid var(--staff-border,#dce3da);border-radius:9px;text-decoration:none;font-weight:700;color:var(--staff-ink,#203329)}
#intakeSavedConfirmation #intakeSavedNew{background:#31553a;color:#fff}
#intakeSavedConfirmation a:focus-visible{outline:2px solid var(--staff-action,#31553a);outline-offset:3px}
</style>
<script>
(() => {
    const confirmation = document.getElementById('intakeSavedConfirmation');
    document.body.appendChild(confirmation);
    document.querySelector('.app-shell').inert = true;
    confirmation.addEventListener('cancel', event => event.preventDefault());
})();
</script>
@endsection
