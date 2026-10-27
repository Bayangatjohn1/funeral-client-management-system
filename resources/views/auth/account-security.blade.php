<x-guest-layout :security="true">
    @php($titles = ['setup' => 'Set up your authenticator', 'password' => 'Choose your personal password', 'recovery' => 'Save your recovery codes', 'challenge' => 'Verify your sign-in'])
    <header class="account-security-header">
        <span class="account-security-kicker"><i class="bi bi-shield-lock" aria-hidden="true"></i> {{ auth()->user()->isStaff() ? 'Staff' : (auth()->user()->isBranchAdmin() ? 'Branch Admin' : 'System Admin') }} account security</span>
        <h1>{{ $titles[$mode] }}</h1>
        <p>{{ $mode === 'setup' ? 'Connect your authenticator, then enter a code to secure your account.' : ($mode === 'password' ? 'Replace your temporary password before continuing.' : ($mode === 'recovery' ? 'Keep these codes somewhere safe so you can recover access.' : 'Enter the code from your authenticator to continue.')) }}</p>
        <div class="account-security-account"><i class="bi bi-person" aria-hidden="true"></i><span>{{ auth()->user()->email }}</span></div>
    </header>
    @if($mode === 'recovery')
        <p class="account-security-notice" role="status">Each code works once. These codes are shown only once; save them before continuing.</p>
        @if(count($codes))
            <ul class="account-security-recovery" aria-label="Recovery codes">@foreach($codes as $code)<li>{{ $code }}</li>@endforeach</ul>
        @else
            <p class="account-security-notice">Your recovery codes have already been displayed.</p>
        @endif
        <a href="{{ auth()->user()->must_change_password ? route('security.password') : url(auth()->user()->isStaff() ? '/staff' : '/admin') }}" class="ui-button ui-button-primary account-security-submit">{{ auth()->user()->must_change_password ? 'Continue to password change' : 'Continue to dashboard' }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    @else
        <div class="account-security-content {{ $mode === 'setup' ? 'account-security-content--setup' : '' }}">
            @if($mode === 'setup')
                <section class="account-security-scan" aria-labelledby="scan-title">
                    <h2 id="scan-title"><span class="account-security-step">1</span> Scan the QR code</h2>
                    <p>In your authenticator app, choose <strong>Add account</strong>, then <strong>Scan QR code</strong>.</p>
                    <div class="account-security-qr"><img src="{{ $qrCode }}" alt="Scan with your authenticator app to set up this account" width="240" height="240" /></div>
                    <details class="account-security-manual">
                        <summary>Can't scan? Enter the setup key manually</summary>
                        <p>Choose Enter setup key in your app. Use your email as the account name and select <strong>Time-based</strong>.</p>
                        <code aria-label="Authenticator setup key">{{ $secret }}</code>
                    </details>
                </section>
            @endif
            <section class="account-security-verification" aria-labelledby="verify-title">
                <h2 id="verify-title">@if($mode === 'setup')<span class="account-security-step">2</span> Confirm your setup @else{{ $mode === 'password' ? 'Update password' : 'Authenticator verification' }}@endif</h2>
                @if($mode === 'setup')<p class="account-security-help">Enter your current password and the 6-digit code shown in your app.</p>@endif
                <form method="POST" action="{{ route(in_array($mode, ['setup', 'password']) ? 'security.enroll' : 'security.verify') }}" class="account-security-form">
                    @csrf
                    @if(in_array($mode, ['setup', 'password']))
                        <div><x-input-label for="current_password" value="Current password" /><x-text-input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="w-full" aria-describedby="current-password-error" :aria-invalid="$errors->has('current_password') ? 'true' : 'false'" /><div id="current-password-error"><x-input-error :messages="$errors->get('current_password')" /></div></div>
                        @if($mode === 'password')
                            <p id="password-help" class="account-security-help">Use at least 12 characters, including letters and numbers. Choose a different password from your temporary one.</p>
                            <div><x-input-label for="password" value="New password" /><x-text-input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" class="w-full" aria-describedby="password-help password-error" :aria-invalid="$errors->has('password') ? 'true' : 'false'" /><div id="password-error"><x-input-error :messages="$errors->get('password')" /></div></div>
                            <div><x-input-label for="password_confirmation" value="Confirm new password" /><x-text-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12" class="w-full" /></div>
                        @endif
                    @endif
                    @if($mode !== 'password')
                        <div><x-input-label for="code" :value="$mode === 'setup' ? '6-digit verification code' : 'Verification or recovery code'" /><x-text-input id="code" name="code" type="text" autocomplete="one-time-code" :inputmode="$mode === 'setup' ? 'numeric' : 'text'" :pattern="$mode === 'setup' ? '[0-9]{6}' : null" :maxlength="$mode === 'setup' ? 6 : 64" :placeholder="$mode === 'setup' ? '000000' : 'Enter your code'" required class="w-full account-security-code" aria-describedby="code-help code-error" :aria-invalid="$errors->has('code') ? 'true' : 'false'" /><p id="code-help" class="account-security-help">{{ $mode === 'setup' ? 'Codes refresh every 30 seconds. Use the latest code.' : 'You can also enter an unused recovery code if you cannot access your authenticator.' }}</p><div id="code-error"><x-input-error :messages="$errors->get('code')" /></div></div>
                    @endif
                    <x-primary-button class="account-security-submit">{{ $mode === 'setup' ? 'Enable authenticator' : ($mode === 'password' ? 'Save password and continue' : 'Verify and continue') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></x-primary-button>
                </form>
            </section>
        </div>
    @endif
    <footer class="account-security-footer"><span><i class="bi bi-lock" aria-hidden="true"></i> {{ $mode === 'setup' ? 'Your setup key stays the same during this setup session.' : 'Protected account access' }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form></footer>
</x-guest-layout>
