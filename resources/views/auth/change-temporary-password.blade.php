<x-guest-layout :security="true" :recovery="true">
    <header class="account-security-header">
        <span class="account-security-kicker"><i class="bi bi-shield-lock" aria-hidden="true"></i> ACCOUNT SECURITY</span>
        <h1>Set your personal password</h1>
        <p>You signed in with a temporary password. Create your own password to continue.</p>
    </header>
    <form method="POST" action="{{ route('security.password.update') }}" class="account-security-form" x-data="{ saving: false }" @submit="saving = true">
        @csrf
        <p id="password-help" class="account-security-help">At least 12 characters, including letters and numbers. Choose a password different from your temporary password.</p>
        @foreach(['password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
            <div x-data="{ show: false }">
                <x-input-label :for="$field" :value="$label" />
                <div class="security-password-field">
                    <x-text-input :id="$field" :name="$field" type="password" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password" required minlength="12" maxlength="128" class="w-full" :aria-describedby="$field === 'password' ? 'password-help password-error' : 'confirmation-error'" />
                    <button type="button" @click="show = !show" :aria-label="show ? 'Hide {{ strtolower($label) }}' : 'Show {{ strtolower($label) }}'" :aria-pressed="show"><i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i></button>
                </div>
                <div id="{{ $field === 'password' ? 'password-error' : 'confirmation-error' }}"><x-input-error :messages="$errors->get($field)" /></div>
            </div>
        @endforeach
        <x-primary-button class="account-security-submit" x-bind:disabled="saving"><span x-text="saving ? 'Saving…' : 'Save password and continue'">Save password and continue</span></x-primary-button>
    </form>
    <footer class="account-security-footer"><span><i class="bi bi-lock" aria-hidden="true"></i> MFA verified</span><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form></footer>
</x-guest-layout>
