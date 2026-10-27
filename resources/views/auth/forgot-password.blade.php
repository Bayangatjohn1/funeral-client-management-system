<x-guest-layout :security="true" :recovery="true">
    <header class="account-security-header"><span class="account-security-kicker">ACCOUNT RECOVERY</span><h1>Forgot your password?</h1></header>
    <div class="mb-4 text-sm text-gray-600">
        Enter your account email address. We'll send a link to choose a new password. Links expire after 60 minutes and can be used once.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(!$recoveryAvailable)<p class="account-security-notice" role="status">Email recovery is currently unavailable. Contact your administrator for assistance.</p>@endif
    <form method="POST" action="{{ route('password.email') }}" class="account-security-form">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="account-security-submit" :disabled="!$recoveryAvailable">
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
    <footer class="account-security-footer"><a href="{{ route('login') }}">← Back to sign in</a></footer>
</x-guest-layout>

