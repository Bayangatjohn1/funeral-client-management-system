@props(['security' => false, 'recovery' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $security ? 'Account security | ' : '' }}{{ config('app.name', 'Laravel') }}</title>
        @if($security)
            <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('app-theme') === 'dark' ? 'dark' : 'light');</script>
        @endif

        <!-- Fonts -->
        <link href="{{ asset('vendor/offline/fonts.css') }}" rel="stylesheet">
        <link rel="icon" href="{{ asset('images/login-logo.png') }}" type="image/png">
        <link rel="shortcut icon" href="{{ asset('images/login-logo.png') }}" type="image/png">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased guest-shell {{ $security ? 'account-security-page' : '' }} {{ $recovery ? 'account-recovery-page' : '' }}">
        <div class="guest-shell__inner">
            <div>
                <a href="/">
                    @if($security)
                        <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa" width="64" height="64" class="account-security-logo" />
                    @else
                        <x-application-logo class="guest-logo w-20 h-20 fill-current" />
                    @endif
                </a>
            </div>

            <main class="guest-card w-full sm:max-w-md mt-6 px-6 py-4 overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </main>
        </div>
        @auth @include('partials.idle_session') @endauth
    </body>
</html>
