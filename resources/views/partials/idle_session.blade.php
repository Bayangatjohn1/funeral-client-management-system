@auth
<div id="idle-session-config" hidden data-state="{{ json_encode(\App\Support\IdleSession::state(request())) }}" data-status-url="{{ route('session.status') }}" data-keep-alive-url="{{ route('session.keep_alive') }}" data-login-url="{{ route('login') }}"></div>
<dialog id="idle-session-dialog" class="idle-session-dialog" aria-labelledby="idle-session-title" aria-describedby="idle-session-description">
    <h2 id="idle-session-title"><i class="bi bi-clock" aria-hidden="true"></i> Still working?</h2>
    <p id="idle-session-description">Your session will expire due to inactivity in <strong data-idle-countdown>60 seconds</strong>. Stay signed in to continue.</p>
    <p>Unsaved changes may be lost when you sign out. Save your work before leaving.</p>
    <p data-idle-error role="status" hidden></p>
    <div class="idle-session-actions"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="ui-button ui-button-secondary">Sign out</button></form><button type="button" data-idle-stay class="ui-button ui-button-primary">Stay signed in</button></div>
</dialog>
@endauth
