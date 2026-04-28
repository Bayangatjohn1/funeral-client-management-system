<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Login | Sabangan Caguioa</title>
    <script>
        (function () {
            const stored = localStorage.getItem('app-theme');
            const theme = stored === 'dark' || stored === 'light' ? stored : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    {{-- Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Page-specific animation --}}
    <style>
        @keyframes floatUp {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .animate-float-up {
            opacity: 0;
            animation: floatUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.2s;
        }

        .login-page {
            font-family: 'DM Sans', sans-serif;
        }

        .login-page h1,
        .login-page h2,
        .login-page h3,
        .login-page .font-heading {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
        }

        .login-page label,
        .login-page input,
        .login-page button,
        .login-page p,
        .login-page a,
        .login-page span {
            font-family: 'DM Sans', sans-serif;
        }

        .login-form-label {
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .login-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 0.5rem;
        }

        .login-label-row .login-form-label {
            margin-bottom: 0;
        }

        .login-field {
            position: relative;
        }

        .login-field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            width: 20px;
            height: 20px;
        }

        .login-field-icon i,
        .login-field-icon svg {
            display: block;
            width: 15px;
            height: 15px;
            line-height: 1;
            flex-shrink: 0;
        }

        .login-field-input {
            min-height: 46px;
            height: 46px;
            padding-left: 48px;
            padding-right: 16px;
            font-weight: 400;
            line-height: 1.2;
        }

        .login-field-input.password-input {
            padding-right: 82px;
        }

        .login-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            background: #e2e8f0;
            border-radius: 0.5rem;
            width: 34px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color .15s ease, background-color .15s ease;
        }

        .login-toggle-btn:hover {
            color: #1f2937;
            background: #cbd5e1;
        }

        .login-toggle-btn i {
            font-size: 15px;
            line-height: 1;
        }

        .login-remember {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 2px;
        }

        .login-remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            min-height: 16px;
            margin: 0;
            padding: 0;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            flex-shrink: 0;
            cursor: pointer;
        }

        .login-remember label {
            margin: 0;
            font-size: 14px;
            font-weight: 400;
            color: #475569;
            cursor: pointer;
        }

        .login-footer-note {
            font-size: 11px;
            font-weight: 400;
            color: #94a3b8;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            line-height: 1.55;
        }

        .login-hero-slide {
            background:
                radial-gradient(circle at 18% 24%, rgba(212, 163, 115, 0.34), transparent 34%),
                radial-gradient(circle at 82% 18%, rgba(255, 255, 255, 0.14), transparent 28%),
                linear-gradient(160deg, rgba(15, 23, 42, 0.94), rgba(34, 32, 29, 0.90));
        }

        .login-hero-slide::before,
        .login-hero-slide::after {
            content: "";
            position: absolute;
            inset: auto;
            border-radius: 9999px;
            pointer-events: none;
        }

        .login-hero-slide::before {
            width: 20rem;
            height: 20rem;
            right: -4rem;
            top: -4rem;
            background: rgba(140, 64, 4, 0.2);
            filter: blur(20px);
        }

        .login-hero-slide::after {
            width: 16rem;
            height: 16rem;
            left: -3rem;
            bottom: -3rem;
            background: rgba(255, 255, 255, 0.08);
            filter: blur(18px);
        }
    </style>
</head>
<body class="login-page font-sans text-slate-900 antialiased h-screen overflow-hidden bg-white relative">

{{-- Subtle background glow --}}
<div class="absolute right-0 top-0 w-1/2 h-full bg-gradient-to-bl from-[#9C5A1A]/5 to-transparent pointer-events-none z-0"></div>

<div class="absolute right-4 top-4 z-30 sm:right-6 sm:top-6">
    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">
        <span class="theme-toggle__meta">
            <span class="theme-toggle__eyebrow">Theme</span>
            <span class="theme-toggle__value" data-theme-label>Light</span>
        </span>
        <span class="theme-toggle__switch" aria-hidden="true">
            <span class="theme-toggle__sun"><i class="bi bi-brightness-high-fill"></i></span>
            <span class="theme-toggle__moon"><i class="bi bi-moon-stars-fill"></i></span>
            <span class="theme-toggle__thumb"></span>
        </span>
    </button>
</div>

<div class="flex h-full w-full relative z-10">

    <section class="hidden lg:flex lg:w-1/2 relative bg-slate-900 flex-col justify-between overflow-hidden">

        <div id="hero-slider" class="absolute inset-0 w-full h-full z-0">
            <div class="slide login-hero-slide absolute inset-0 w-full h-full opacity-100 transition-opacity duration-1000 ease-in-out">
                <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(15,23,42,0.92),rgba(37,99,235,0.22))]"></div>
                <div class="absolute right-16 top-16 h-44 w-44 rounded-[2rem] border border-white/15 bg-white/5 backdrop-blur-sm shadow-2xl"></div>
                <div class="absolute right-28 top-28 h-24 w-24 rounded-3xl border border-white/10 bg-[#60a5fa]/20"></div>
                <div class="absolute bottom-12 left-12 max-w-lg z-20">
                    <span class="px-3 py-1 bg-[#2563eb] text-white text-[10px] font-medium uppercase tracking-widest rounded-md mb-4 inline-block shadow-sm">Our Services</span>
                    <h3 class="text-4xl font-bold text-white font-heading leading-tight mb-3">Premium Viewing <br>Chapel Setup</h3>
                    <p class="text-white/80 font-medium text-sm leading-relaxed">Providing a peaceful and dignified environment for families to honor the lives of their loved ones.</p>
                </div>
            </div>

            <div class="slide login-hero-slide absolute inset-0 w-full h-full opacity-0 transition-opacity duration-1000 ease-in-out">
                <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(22,28,45,0.88),rgba(8,47,73,0.22))]"></div>
                <div class="absolute right-16 top-12 h-52 w-64 rounded-[2.25rem] border border-white/15 bg-white/5 backdrop-blur-md shadow-2xl"></div>
                <div class="absolute right-24 top-20 flex gap-3">
                    <div class="h-24 w-16 rounded-2xl bg-white/10 border border-white/10"></div>
                    <div class="h-24 w-16 rounded-2xl bg-[#2563eb]/30 border border-[#60a5fa]/20"></div>
                    <div class="h-24 w-16 rounded-2xl bg-white/10 border border-white/10"></div>
                </div>
                <div class="absolute bottom-12 left-12 max-w-lg z-20">
                    <span class="px-3 py-1 bg-white text-[#2563eb] text-[10px] font-medium uppercase tracking-widest rounded-md mb-4 inline-block shadow-sm">Transport</span>
                    <h3 class="text-4xl font-bold text-white font-heading leading-tight mb-3">State-of-the-art <br>Funeral Fleet</h3>
                    <p class="text-white/80 font-medium text-sm leading-relaxed">Ensuring a solemn and respectful journey with our well-maintained and elegant vehicles.</p>
                </div>
            </div>

            <div class="slide login-hero-slide absolute inset-0 w-full h-full opacity-0 transition-opacity duration-1000 ease-in-out">
                <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(30,41,59,0.88),rgba(37,99,235,0.18))]"></div>
                <div class="absolute right-16 top-16 w-72 rounded-[2rem] border border-white/15 bg-white/5 p-6 backdrop-blur-md shadow-2xl">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-2xl bg-[#2563eb]/30 border border-[#60a5fa]/30"></div>
                        <div class="space-y-2">
                            <div class="h-3 w-24 rounded-full bg-white/20"></div>
                            <div class="h-3 w-32 rounded-full bg-white/10"></div>
                        </div>
                    </div>
                    <div class="mt-6 space-y-3">
                        <div class="h-3 rounded-full bg-white/10"></div>
                        <div class="h-3 rounded-full bg-white/10"></div>
                        <div class="h-3 w-3/4 rounded-full bg-white/10"></div>
                    </div>
                </div>
                <div class="absolute bottom-12 left-12 max-w-lg z-20">
                    <span class="px-3 py-1 bg-[#9C5A1A] text-white text-[10px] font-medium uppercase tracking-widest rounded-md mb-4 inline-block shadow-sm">Commitment</span>
                    <h3 class="text-4xl font-bold text-white font-heading leading-tight mb-3">Compassionate & <br>Professional Care</h3>
                    <p class="text-white/80 font-medium text-sm leading-relaxed">Our dedicated team is here to guide and support your family every step of the way.</p>
                </div>
            </div>
        </div>

        <div class="absolute inset-0 bg-gradient-to-b from-slate-900/85 via-slate-900/30 to-slate-900/95 z-10 pointer-events-none"></div>

        {{-- Top branding --}}
        <div class="relative z-20 p-12">
            <div class="flex items-center gap-5">
                <div class="bg-white/10 backdrop-blur-md p-2 rounded-2xl border border-white/20 shadow-2xl">
                    <img src="{{ asset('images/login-logo.png') }}" alt="Logo" class="w-14 h-14 object-contain rounded-xl bg-white p-1">
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white font-heading tracking-tight drop-shadow-lg">Sabangan Caguioa</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-6 h-1 bg-[#9C5A1A] rounded-full shadow-sm"></div>
                        <p class="text-[11px] font-medium text-white/80 uppercase tracking-widest drop-shadow-md">Funeral Home System</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dots --}}
        <div class="relative z-20 p-12 flex justify-end">
            <div class="flex gap-3 bg-slate-900/30 backdrop-blur-sm px-4 py-3 rounded-full border border-white/10" id="slider-dots">
                <button class="w-2.5 h-2.5 rounded-full bg-white shadow-[0_0_10px_rgba(255,255,255,0.8)] transition-all"></button>
                <button class="w-2.5 h-2.5 rounded-full bg-white/30 hover:bg-white/60 transition-all"></button>
                <button class="w-2.5 h-2.5 rounded-full bg-white/30 hover:bg-white/60 transition-all"></button>
            </div>
        </div>
    </section>

    <section class="w-full lg:w-1/2 relative z-20 overflow-y-auto">
        <div class="w-full min-h-screen lg:min-h-full flex items-center justify-center px-6 py-8 sm:px-10 sm:py-10 lg:px-12">

            {{-- Bordered form container --}}
            <div class="w-full max-w-[500px] bg-white border border-slate-200 rounded-[2rem] shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] hover:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.12)] p-8 sm:p-10 animate-float-up relative transition-shadow duration-300">

            {{-- Decorative accent line --}}
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-1.5 bg-gradient-to-r from-[#9C5A1A] to-[#c76512] rounded-b-full"></div>

            {{-- Mobile logo --}}
            <div class="flex lg:hidden items-center gap-4 mb-8 pt-4">
                <img src="{{ asset('images/login-logo.png') }}" alt="Logo" class="w-12 h-12 rounded-lg border border-slate-200 p-1 shadow-sm">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 font-heading tracking-tight">Sabangan Caguioa</h1>
                    <p class="text-[11px] font-medium text-[#9C5A1A] uppercase tracking-widest mt-0.5">System Portal</p>
                </div>
            </div>

            {{-- Welcome header --}}
            <div class="mb-9 text-center lg:text-left pt-2">
                <h2 class="text-2xl sm:text-3xl font-bold font-heading mb-2 tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-slate-800 to-[#9C5A1A]">
                    Welcome Back
                </h2>
                <p class="text-sm font-medium text-slate-500">Enter your credentials to securely manage operations.</p>
            </div>

            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-sm px-4 py-3 mb-6 font-medium flex items-center gap-3 shadow-sm">
                    <i class="bi bi-check-circle-fill text-emerald-500 text-lg"></i>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email input --}}
                <div class="group">
                    <div class="login-label-row">
                        <label for="email" class="login-form-label transition-colors group-focus-within:text-[#9C5A1A]">
                            Username
                        </label>
                    </div>
                    <div class="login-field">
                        <span class="login-field-icon transition-colors group-focus-within:text-[#9C5A1A]">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M4 20c0-3.2 3.6-5.5 8-5.5s8 2.3 8 5.5"></path>
                            </svg>
                        </span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="login-field-input w-full bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-[#9C5A1A] focus:ring-4 focus:ring-[#9C5A1A]/10 transition-all outline-none shadow-sm"
                            placeholder="name@example.com"
                        >
                    </div>
                    @error('email')
                        <p class="mt-2 text-[11px] font-medium text-red-500 flex items-center gap-1">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password input --}}
                <div class="group">
                    <div class="login-label-row">
                        <label for="password" class="login-form-label transition-colors group-focus-within:text-[#9C5A1A]">
                            Password
                        </label>
                    </div>
                    <div class="login-field">
                        <span class="login-field-icon transition-colors group-focus-within:text-[#9C5A1A]">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                <path d="M8 11V8a4 4 0 118 0v3"></path>
                            </svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="login-field-input password-input w-full bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:border-[#9C5A1A] focus:ring-4 focus:ring-[#9C5A1A]/10 transition-all outline-none shadow-sm"
                            placeholder="Enter your password"
                        >
                        <button
                            type="button"
                            id="togglePassword"
                            class="login-toggle-btn focus:outline-none"
                            aria-label="Show password"
                        >
                            <i id="togglePasswordIcon" class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-[11px] font-medium text-red-500 flex items-center gap-1">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="login-remember">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="text-[#9C5A1A] bg-white focus:ring-[#9C5A1A] focus:ring-2 transition-all"
                    >
                    <label for="remember">
                        Keep me signed in
                    </label>
                </div>

                {{-- Primary button --}}
                <div class="pt-3">
                    <button
                        type="submit"
                        class="group w-full inline-flex items-center justify-center px-6 py-3.5 bg-[#9C5A1A] border border-transparent rounded-xl font-medium text-sm text-white uppercase tracking-widest hover:bg-[#6a3003] hover:shadow-[0_10px_20px_-10px_rgba(140,64,4,0.6)] hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-[#9C5A1A]/30 active:scale-95 transition-all duration-300"
                    >
                        Sign In
                    </button>
                </div>
            </form>

            {{-- Inner footer --}}
            <div class="mt-10 text-center pt-8 border-t border-slate-100">
                <p class="login-footer-note">
                    &copy; {{ date('Y') }} Sabangan Caguioa <br>
                    Secure access monitored
                </p>
            </div>
            </div>
        </div>
    </section>
</div>

{{-- Scripts --}}
<script>
    // 1. Password toggle script
    (function () {
        const toggle = document.getElementById('togglePassword');
        const icon = document.getElementById('togglePasswordIcon');
        const password = document.getElementById('password');
        if (!toggle || !password || !icon) return;

        toggle.addEventListener('click', function () {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    })();

    // 2. Slideshow script
    document.addEventListener("DOMContentLoaded", () => {
        const slides = document.querySelectorAll(".slide");
        const dots = document.querySelectorAll("#slider-dots button");
        if (slides.length === 0) return;

        let currentSlide = 0;
        const slideInterval = 5000;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove("opacity-100", "z-10");
                slide.classList.add("opacity-0", "z-0");
                dots[i].classList.remove("bg-white", "shadow-[0_0_10px_rgba(255,255,255,0.8)]");
                dots[i].classList.add("bg-white/30");
            });

            slides[index].classList.remove("opacity-0", "z-0");
            slides[index].classList.add("opacity-100", "z-10");
            dots[index].classList.remove("bg-white/30");
            dots[index].classList.add("bg-white", "shadow-[0_0_10px_rgba(255,255,255,0.8)]");
            currentSlide = index;
        }

        function nextSlide() {
            let next = (currentSlide + 1) % slides.length;
            showSlide(next);
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
            });
        });

        setInterval(nextSlide, slideInterval);
    });
</script>
</body>
</html>
