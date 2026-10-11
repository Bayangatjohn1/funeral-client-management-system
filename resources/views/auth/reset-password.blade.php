<x-guest-layout :security="true" :recovery="true">
    <header class="account-security-header"><span class="account-security-kicker">ACCOUNT RECOVERY</span><h1>Choose a new password</h1><p>Use at least 12 characters, including letters and numbers.</p></header>
    <form method="POST" action="{{ route('password.store') }}" class="account-security-form">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            @if($errors->has('email'))<a class="underline text-sm" href="{{ route('password.request') }}">Request a new reset link</a>@endif
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="account-security-submit">
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
    <p class="account-security-help mt-4">Your existing sessions will be signed out. Sign in again with your new password and authenticator code, if enabled.</p>
    <footer class="account-security-footer"><a href="{{ route('login') }}">← Back to sign in</a></footer>
</x-guest-layout>

