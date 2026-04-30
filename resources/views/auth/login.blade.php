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

        .login-hero-panel {
            background:
                radial-gradient(circle at 22% 18%, rgba(212, 163, 115, 0.22), transparent 32%),
                radial-gradient(circle at 88% 14%, rgba(255, 255, 255, 0.12), transparent 26%),
                linear-gradient(145deg, #0f172a 0%, #1e1a16 54%, #2a1607 100%);
        }

        .login-hero-panel::before,
        .login-hero-panel::after {
            content: "";
            position: absolute;
            inset: auto;
            border-radius: 9999px;
            pointer-events: none;
        }

        .login-hero-panel::before {
            width: 20rem;
            height: 20rem;
            right: -4rem;
            top: -4rem;
            background: rgba(156, 90, 26, 0.24);
            filter: blur(20px);
        }

        .login-hero-panel::after {
            width: 16rem;
            height: 16rem;
            left: -3rem;
            bottom: -3rem;
            background: rgba(255, 255, 255, 0.08);
            filter: blur(18px);
        }

        .login-slide-visual {
            min-height: 320px;
            border: 1px solid rgba(255, 255, 255, 0.13);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 28px 70px -34px rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(18px);
        }

        .login-hero-card {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.06));
            box-shadow: 0 22px 50px -28px rgba(0, 0, 0, 0.85);
        }

        .login-slide {
            pointer-events: none;
        }

        .login-slide.is-active {
            pointer-events: auto;
        }

        @media (max-height: 760px) {
            #hero-slider {
                min-height: 500px;
            }

            .login-slide-visual {
                min-height: 260px;
            }
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

    <section class="hidden lg:flex lg:w-1/2 relative login-hero-panel flex-col overflow-hidden">

        {{-- Top branding --}}
        <div class="relative z-20 px-12 pt-12 pb-8">
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

        <div class="relative z-20 flex flex-1 flex-col justify-center px-12 pb-12">
            <div id="hero-slider" class="relative min-h-[560px]">
                <article class="slide login-slide is-active absolute inset-0 flex flex-col justify-center opacity-100 transition-opacity duration-700 ease-in-out">
                    <div class="login-slide-visual relative overflow-hidden rounded-[2rem] p-7">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_12%,rgba(156,90,26,0.3),transparent_30%),linear-gradient(145deg,rgba(15,23,42,0.2),rgba(15,23,42,0.78))]"></div>
                        <div class="relative grid h-full gap-5">
                            <div class="login-hero-card rounded-3xl p-5">
                                <div class="flex items-center justify-between gap-5">
                                    <div>
                                        <p class="text-[10px] font-medium uppercase tracking-[0.28em] text-white/55">Operations Overview</p>
                                        <p class="mt-2 text-2xl font-semibold text-white">Today&apos;s branch activity</p>
                                    </div>
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#9C5A1A] text-white shadow-lg">
                                        <i class="bi bi-speedometer2 text-xl"></i>
                                    </div>
                                </div>
                                <div class="mt-6 grid grid-cols-3 gap-3">
                                    <div class="rounded-2xl bg-white/10 p-4">
                                        <p class="text-2xl font-semibold text-white">18</p>
                                        <p class="mt-1 text-[11px] text-white/55">Active cases</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/10 p-4">
                                        <p class="text-2xl font-semibold text-white">6</p>
                                        <p class="mt-1 text-[11px] text-white/55">Services</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/10 p-4">
                                        <p class="text-2xl font-semibold text-white">3</p>
                                        <p class="mt-1 text-[11px] text-white/55">Branches</p>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-[1.1fr_0.9fr] gap-5">
                                <div class="login-hero-card rounded-3xl p-5">
                                    <div class="mb-4 flex items-center justify-between">
                                        <span class="text-xs font-medium text-white/70">Case monitoring</span>
                                        <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-[10px] font-medium uppercase tracking-widest text-emerald-200">Live</span>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="h-3 w-full rounded-full bg-white/12"><div class="h-3 w-4/5 rounded-full bg-[#d4a373]"></div></div>
                                        <div class="h-3 w-full rounded-full bg-white/12"><div class="h-3 w-2/3 rounded-full bg-white/50"></div></div>
                                        <div class="h-3 w-full rounded-full bg-white/12"><div class="h-3 w-3/5 rounded-full bg-[#9C5A1A]"></div></div>
                                    </div>
                                </div>
                                <div class="login-hero-card flex items-center justify-center rounded-3xl p-5">
                                    <div class="relative h-28 w-36">
                                        <div class="absolute bottom-2 left-2 h-16 w-28 rounded-2xl border border-white/20 bg-white/10"></div>
                                        <div class="absolute bottom-8 left-0 h-12 w-20 rounded-t-2xl border border-white/20 bg-[#9C5A1A]/45"></div>
                                        <div class="absolute bottom-0 left-8 h-5 w-5 rounded-full border border-white/30 bg-slate-950"></div>
                                        <div class="absolute bottom-0 right-6 h-5 w-5 rounded-full border border-white/30 bg-slate-950"></div>
                                        <div class="absolute right-1 top-3 h-9 w-9 rounded-full border border-[#d4a373]/50 bg-[#d4a373]/25"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 max-w-xl">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.26em] text-[#f3d8bd]">Branch Intelligence</span>
                        <h2 class="mt-4 text-4xl font-bold leading-tight text-white font-heading">A calmer way to monitor daily funeral operations.</h2>
                        <p class="mt-4 text-sm leading-7 text-white/72">View active cases, service schedules, and branch activity from one secure access point built for focused staff work.</p>
                    </div>
                </article>

                <article class="slide login-slide absolute inset-0 flex flex-col justify-center opacity-0 transition-opacity duration-700 ease-in-out">
                    <div class="login-slide-visual relative overflow-hidden rounded-[2rem] p-7">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_82%_16%,rgba(212,163,115,0.28),transparent_28%),linear-gradient(145deg,rgba(15,23,42,0.24),rgba(15,23,42,0.8))]"></div>
                        <div class="relative grid h-full grid-cols-[0.95fr_1.05fr] gap-5">
                            <div class="login-hero-card flex flex-col justify-between rounded-3xl p-5">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-[#9C5A1A] shadow-lg">
                                    <i class="bi bi-calendar2-check text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium uppercase tracking-[0.28em] text-white/55">Service Flow</p>
                                    <p class="mt-2 text-2xl font-semibold leading-tight text-white">Coordinated schedules from intake to service.</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="login-hero-card rounded-3xl p-5">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#9C5A1A]/55 text-white">
                                            <i class="bi bi-truck-front text-xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-white">Transport assignment</p>
                                            <div class="mt-2 h-2 rounded-full bg-white/12"><div class="h-2 w-3/4 rounded-full bg-[#d4a373]"></div></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="login-hero-card rounded-3xl p-5">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/12 text-white">
                                            <i class="bi bi-clipboard2-pulse text-xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-white">Preparation checklist</p>
                                            <div class="mt-2 h-2 rounded-full bg-white/12"><div class="h-2 w-4/5 rounded-full bg-white/55"></div></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="login-hero-card rounded-3xl p-5">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#d4a373]/25 text-[#f3d8bd]">
                                            <i class="bi bi-building-check text-xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-white">Branch handoff</p>
                                            <div class="mt-2 h-2 rounded-full bg-white/12"><div class="h-2 w-2/3 rounded-full bg-[#9C5A1A]"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 max-w-xl">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.26em] text-[#f3d8bd]">Service Coordination</span>
                        <h2 class="mt-4 text-4xl font-bold leading-tight text-white font-heading">Keep every service detail visible and organized.</h2>
                        <p class="mt-4 text-sm leading-7 text-white/72">Track transport, chapel preparation, and branch responsibilities with a polished workflow that supports timely decisions.</p>
                    </div>
                </article>

                <article class="slide login-slide absolute inset-0 flex flex-col justify-center opacity-0 transition-opacity duration-700 ease-in-out">
                    <div class="login-slide-visual relative overflow-hidden rounded-[2rem] p-7">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_22%,rgba(255,255,255,0.14),transparent_28%),linear-gradient(145deg,rgba(15,23,42,0.2),rgba(15,23,42,0.82))]"></div>
                        <div class="relative grid h-full grid-cols-[1.05fr_0.95fr] gap-5">
                            <div class="login-hero-card rounded-3xl p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-medium uppercase tracking-[0.28em] text-white/55">Secure Records</p>
                                        <p class="mt-2 text-2xl font-semibold text-white">Access by role and branch</p>
                                    </div>
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-400/15 text-emerald-200">
                                        <i class="bi bi-shield-lock text-xl"></i>
                                    </div>
                                </div>
                                <div class="mt-7 space-y-4">
                                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4">
                                        <i class="bi bi-person-badge text-[#f3d8bd]"></i>
                                        <span class="text-sm font-medium text-white/82">Staff login verification</span>
                                    </div>
                                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4">
                                        <i class="bi bi-file-earmark-lock text-[#f3d8bd]"></i>
                                        <span class="text-sm font-medium text-white/82">Protected case files</span>
                                    </div>
                                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4">
                                        <i class="bi bi-clock-history text-[#f3d8bd]"></i>
                                        <span class="text-sm font-medium text-white/82">Auditable activity trail</span>
                                    </div>
                                </div>
                            </div>
                            <div class="login-hero-card flex items-center justify-center rounded-3xl p-6">
                                <div class="relative h-56 w-44 rounded-[2rem] border border-white/15 bg-slate-950/35 p-5 shadow-2xl">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[#9C5A1A] text-white shadow-lg">
                                        <i class="bi bi-lock-fill text-2xl"></i>
                                    </div>
                                    <div class="mt-8 space-y-3">
                                        <div class="h-3 rounded-full bg-white/20"></div>
                                        <div class="h-3 rounded-full bg-white/12"></div>
                                        <div class="h-3 w-2/3 rounded-full bg-white/12"></div>
                                    </div>
                                    <div class="absolute -right-5 bottom-8 rounded-2xl border border-emerald-300/20 bg-emerald-400/15 px-4 py-3 text-xs font-medium text-emerald-100 shadow-xl">
                                        Verified
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 max-w-xl">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.26em] text-[#f3d8bd]">Access Control</span>
                        <h2 class="mt-4 text-4xl font-bold leading-tight text-white font-heading">Protect sensitive records without slowing the team down.</h2>
                        <p class="mt-4 text-sm leading-7 text-white/72">Role-aware access and monitored sessions help keep family, payment, and service records handled with care.</p>
                    </div>
                </article>
            </div>

            <div class="mt-2 flex items-center justify-between gap-6">
                <div class="grid grid-cols-3 gap-3 text-white/68">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3">
                        <p class="text-[10px] font-medium uppercase tracking-widest">Secure Access</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3">
                        <p class="text-[10px] font-medium uppercase tracking-widest">Case Monitoring</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3">
                        <p class="text-[10px] font-medium uppercase tracking-widest">Branch Ops</p>
                    </div>
                </div>
                <div class="flex shrink-0 gap-3 bg-slate-950/25 backdrop-blur-sm px-4 py-3 rounded-full border border-white/10" id="slider-dots" aria-label="Hero slides">
                    <button type="button" class="h-2.5 w-8 rounded-full bg-white shadow-[0_0_10px_rgba(255,255,255,0.55)] transition-all" aria-label="Show branch intelligence slide"></button>
                    <button type="button" class="h-2.5 w-2.5 rounded-full bg-white/30 hover:bg-white/60 transition-all" aria-label="Show service coordination slide"></button>
                    <button type="button" class="h-2.5 w-2.5 rounded-full bg-white/30 hover:bg-white/60 transition-all" aria-label="Show access control slide"></button>
                </div>
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
                            Email Address
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
                <div class="flex items-center justify-between gap-4">
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
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-[#9C5A1A] hover:text-[#6a3003] transition-colors">
                            Forgot Password?
                        </a>
                    @endif
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
        const dotsWrap = document.getElementById("slider-dots");
        if (slides.length === 0) return;
        if (slides.length <= 1 && dotsWrap) {
            dotsWrap.classList.add("hidden");
            return;
        }

        let currentSlide = 0;
        const slideInterval = 5000;
        let timer = null;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove("opacity-100", "z-10", "is-active");
                slide.classList.add("opacity-0", "z-0");

                if (dots[i]) {
                    dots[i].classList.remove("w-8", "bg-white", "shadow-[0_0_10px_rgba(255,255,255,0.55)]");
                    dots[i].classList.add("w-2.5", "bg-white/30");
                    dots[i].setAttribute("aria-current", "false");
                }
            });

            slides[index].classList.remove("opacity-0", "z-0");
            slides[index].classList.add("opacity-100", "z-10", "is-active");

            if (dots[index]) {
                dots[index].classList.remove("w-2.5", "bg-white/30");
                dots[index].classList.add("w-8", "bg-white", "shadow-[0_0_10px_rgba(255,255,255,0.55)]");
                dots[index].setAttribute("aria-current", "true");
            }

            currentSlide = index;
        }

        function nextSlide() {
            let next = (currentSlide + 1) % slides.length;
            showSlide(next);
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                clearInterval(timer);
                timer = setInterval(nextSlide, slideInterval);
            });
        });

        showSlide(0);
        timer = setInterval(nextSlide, slideInterval);
    });
</script>
</body>
</html>
