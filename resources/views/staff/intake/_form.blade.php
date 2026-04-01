@php
    $isOtherEntryMode = !empty($entryMode) && $entryMode === 'other';
    $otherBranchCutoffHour = max(min((int) config('funeral.other_branch_report_cutoff_hour', 18), 23), 0);
    $todayStart = now()->copy()->startOfDay();
    $otherBranchCutoffAt = $todayStart->copy()->addHours($otherBranchCutoffHour);
    $otherBranchWindowClosed = $isOtherEntryMode && now()->gt($otherBranchCutoffAt);
    $otherBranchReportedDefault = old('reported_at', $isOtherEntryMode
        ? now()->copy()->min($otherBranchCutoffAt)->format('Y-m-d\TH:i')
        : null);

    $initialSelectedBranchId = old('branch_id', $defaultBranchId ?? auth()->user()->branch_id);
@endphp

<div class="w-full max-w-[1480px] px-4 sm:px-6 lg:px-8 xl:px-10 mx-auto pb-20 text-slate-800 font-sans">
    <style>
        html { scrollbar-gutter: stable; }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        .form-input,
        .form-textarea {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid #cfd8e3;
            background-color: #ffffff;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: #9c5a1a;
            box-shadow: 0 0 0 3px rgba(156, 90, 26, 0.10);
            outline: none;
        }
        .field-label {
            display: block;
            font-size: 0.74rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.5rem;
            padding-left: 2px;
        }

        .package-card-item { position: relative; }

        .package-card-item .package-radio {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0.001;
            cursor: pointer;
            z-index: 5;
        }

        .package-card {
            transition: all 0.2s ease;
        }

        .package-card .check-dot {
            opacity: 0;
            transform: scale(0.6);
            border-color: #cbd5e1;
            transition: all 0.18s ease;
        }

        .package-card.selected {
            border-color: #0f172a;
            background-color: #f8fafc;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .package-card.selected .check-dot {
            opacity: 1;
            transform: scale(1);
            border-color: #059669;
        }

        .intake-locked {
            opacity: 0.55;
            pointer-events: none;
            user-select: none;
            filter: grayscale(0.06);
        }

        .lock-overlay {
            position: absolute;
            inset: 0;
            z-index: 20;
            background: rgba(255,255,255,0.72);
            backdrop-filter: blur(1px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 3.5rem;
            border-radius: 0 0 1rem 1rem;
        }

        .lock-overlay.hidden {
            display: none;
        }

        .toast-pop {
            left: 50% !important;
            top: 1.5rem !important;
            transform: translate(-50%, -8px) scale(0.96);
            min-width: 280px;
            text-align: center;
            pointer-events: none;
            filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.25));
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .toast-pop.toast-visible {
            opacity: 1 !important;
            transform: translate(-50%, 0) scale(1);
        }
                .intake-section-shell {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .subsection-soft {
            border: 1px solid #e9edf3;
            border-radius: 0.95rem;
            background: #f8fafc;
        }

        .wizard-tab {
            position: relative;
            white-space: nowrap;
        }

        .wizard-tab.active-step {
            background: #ffffff;
            color: #9c5a1a;
        }

        .wizard-tab.active-step::after {
            content: "";
            position: absolute;
            left: 1rem;
            right: 1rem;
            bottom: -1px;
            height: 2px;
            border-radius: 999px;
            background: #9c5a1a;
        }

        .section-heading-icon {
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
        }

        .sticky-review-card {
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
        }

        .footer-action-bar {
            backdrop-filter: blur(8px);
        }
        .package-card,
        .payment-type-card,
        .branch-toggle {
            will-change: transform, box-shadow;
        }

        .package-card:hover,
        .payment-type-card:hover,
        .branch-toggle:hover {
            transform: translateY(-1px);
        }
    </style>

    <div id="branch_toast" class="hidden fixed left-1/2 top-16 -translate-x-1/2 z-50 px-5 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold shadow-2xl transition-all duration-300 opacity-0 translate-y-[-8px] toast-pop">
        You're now recording for this branch.
    </div>

    <div id="package_toast" class="hidden fixed left-1/2 top-28 -translate-x-1/2 z-50 px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-bold shadow-2xl transition-all duration-200 opacity-0 translate-y-[-8px] flex items-center gap-2 toast-pop">
        <i class="bi bi-check2-circle text-lg"></i>
        <span class="package-toast-text">You selected a package.</span>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200 pb-5 mb-7">
        <div class="flex items-center gap-3">

            <h2 class="text-2xl md:text-[1.7rem] font-extrabold text-slate-900 tracking-tight">Case Intake</h2>

            <div class="ml-2 px-3 py-1.5 rounded-lg text-[10px] font-extrabold uppercase tracking-[0.12em] shadow-sm {{ $isOtherEntryMode ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                {{ $isOtherEntryMode ? 'External Branch' : 'Main Branch' }}
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-[0_8px_24px_rgba(15,23,42,0.05)] text-sm">
            <div class="flex items-center gap-4 border-r border-slate-200 pr-4">
                <div class="flex items-center gap-1.5 text-slate-500">
                    <span class="text-[10px] uppercase font-bold tracking-wider">Case ID</span>
                    <span id="next_case_code" class="font-black text-slate-900">{{ $nextCode }}</span>
                </div>

                <div class="hidden sm:flex items-center gap-1.5 text-slate-500">
                    <span class="text-[10px] uppercase font-bold tracking-wider">{{ $isOtherEntryMode ? 'Encoded' : 'Request' }}</span>
                    <span id="service_requested_display" class="font-bold text-slate-900">{{ \Illuminate\Support\Carbon::parse(old('service_requested_at', now()->toDateString()))->format('M d, Y') }}</span>
                </div>

                <div class="hidden lg:flex items-center gap-1.5 text-slate-500">
                    <span class="text-[10px] uppercase font-bold tracking-wider">Encoder</span>
                    <span class="font-bold text-slate-900">{{ auth()->user()->name }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    Branch <span class="text-rose-500">*</span>
                </span>

                <div class="flex items-center gap-1">
                    @foreach($branches as $branch)
                        @php
                            $isActive = (string) $initialSelectedBranchId === (string) $branch->id;
                        @endphp
                        <button
                            type="button"
                            class="branch-toggle px-3 py-1.5 rounded-lg text-xs font-extrabold tracking-wide transition-all {{ $isActive ? 'bg-slate-900 text-white shadow-[0_8px_20px_rgba(15,23,42,0.12)]' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300' }}"
                            data-branch-id="{{ $branch->id }}"
                            data-branch-code="{{ $branch->branch_code }}"
                        >
                            {{ $branch->branch_code }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div id="branch_error" class="hidden text-xs font-bold text-rose-500"></div>
        </div>
    </div>

    @if($isOtherEntryMode)
        <div class="mb-6 rounded-2xl border px-5 py-4 text-sm {{ $otherBranchWindowClosed ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-amber-200 bg-amber-50 text-amber-900' }} shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
            <div class="font-bold flex items-center gap-2">
                <i class="bi {{ $otherBranchWindowClosed ? 'bi-x-circle-fill text-rose-500' : 'bi-exclamation-triangle-fill text-amber-500' }}"></i>
                {{ $otherBranchWindowClosed ? 'Intake Window Closed' : 'External Branch Rules Apply' }}
            </div>
            <div class="mt-1 text-xs font-medium opacity-90">
                Reports must be completed, fully paid, and submitted within today only (00:00 to {{ $otherBranchCutoffAt->format('H:i') }}).
            </div>
        </div>
    @endif

    <div class="intake-section-shell overflow-hidden relative">
        <div id="intake_lock_overlay" class="lock-overlay hidden">
            <div class="max-w-md w-full mx-4 rounded-3xl border border-slate-200 bg-white shadow-[0_20px_45px_rgba(15,23,42,0.16)] px-5 py-5">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center shrink-0">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Select Branch to Begin</h4>
                        <p class="text-xs text-slate-600 mt-1">
                            To begin, please select which branch this case belongs to.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex overflow-x-auto hide-scrollbar border-b border-slate-200 bg-slate-50/50" id="wizardSteps">
            @php
                $steps = [
                    1 => 'Client',
                    2 => 'Deceased',
                    3 => 'Package',
                    4 => 'Service Details',
                    5 => 'Billing',
                    6 => $isOtherEntryMode ? 'Payment Confirmation' : 'Payment',
                    7 => 'Review'
                ];
            @endphp

            @foreach($steps as $num => $label)
                <button
                    type="button"
                    data-step="{{ $num }}"
                    class="wizard-tab flex-1 min-w-[118px] px-5 py-4 text-xs font-extrabold uppercase tracking-[0.12em] transition-all border-b-2 {{ $num === 1 ? 'active-step border-[#9c5a1a] text-[#9c5a1a]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-white/70' }}"
                >
                    <span class="mr-1 opacity-50">{{ $num }}.</span> {{ $label }}
                </button>
            @endforeach
        </div>

        <form method="POST" action="{{ $formAction ?? route('intake.main.store') }}" enctype="multipart/form-data" id="intakeWizardForm">
            @csrf

            <input type="hidden" name="service_requested_at" id="service_requested_at" value="{{ old('service_requested_at', now()->toDateString()) }}">
            <input type="hidden" name="branch_id" id="branch_id" value="{{ $initialSelectedBranchId }}">
            <input type="hidden" id="branch_code_main_default" value="{{ optional($branches->first())->branch_code ?? 'BR001' }}">

            <div id="intakeFormContent" class="p-6 md:p-8 lg:p-10 xl:p-12">
                <section class="wizard-panel" data-step="1">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="section-heading-icon w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600 border border-slate-200">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Client Information</h3>
                    </div>

                    @if($isOtherEntryMode)
                        <div id="external_reporter_box" class="subsection-soft mb-8 p-5 md:p-6 border border-amber-200 bg-gradient-to-br from-amber-50 to-white grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div class="sm:col-span-3 text-[10px] font-black uppercase tracking-widest text-amber-800">External Reporter Details</div>

                            <div>
                                <label class="field-label">Reporter Name</label>
                                <input type="text" name="reporter_name" id="reporter_name" value="{{ old('reporter_name') }}" data-validate="letters-spaces" data-label="reporter name" class="form-input">
                            </div>

                            <div>
                                <label class="field-label">Contact Number</label>
                                <input type="text" name="reporter_contact" value="{{ old('reporter_contact') }}" data-validate="digits" data-label="reporter contact number" class="form-input">
                            </div>

                            <div>
                                <label class="field-label">Reported At</label>
                                <input
                                    type="datetime-local"
                                    name="reported_at"
                                    id="reported_at"
                                    value="{{ $otherBranchReportedDefault }}"
                                    min="{{ $isOtherEntryMode ? $todayStart->format('Y-m-d\\TH:i') : '' }}"
                                    max="{{ $isOtherEntryMode ? $otherBranchCutoffAt->min(now())->format('Y-m-d\\TH:i') : '' }}"
                                    data-label="reported date and time"
                                    class="form-input"
                                >
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div class="md:col-span-2 lg:col-span-1">
                            <label class="field-label">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="client_name" value="{{ old('client_name') }}" data-validate="letters-spaces" data-label="client name" class="form-input" placeholder="First Name / MI / Last Name" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4 md:col-span-2 lg:col-span-1">
                            <div>
                                <label class="field-label">Relationship <span class="text-rose-500">*</span></label>
                                <select name="client_relationship" data-label="relationship to the deceased" class="form-input" required>
                                    <option value="">Select...</option>
                                    @foreach(($clientRelationshipOptions ?? []) as $relationship)
                                        <option value="{{ $relationship }}" {{ old('client_relationship') === $relationship ? 'selected' : '' }}>
                                            {{ $relationship }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="field-label">Contact Number <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    name="client_contact_number"
                                    value="{{ old('client_contact_number') }}"
                                    data-validate="digits"
                                    data-label="contact number"
                                    inputmode="numeric"
                                    maxlength="15"
                                    pattern="[0-9]{7,15}"
                                    title="7 to 15 digits only"
                                    class="form-input"
                                    placeholder="e.g. 0912..."
                                    required
                                >
                            </div>
                        </div>

                        <div class="md:col-span-2 border-t border-slate-100 pt-5 mt-2">
                            <label class="field-label">Complete Address <span class="text-rose-500">*</span></label>
                            <input type="text" name="client_address" id="client_address" value="{{ old('client_address') }}" data-label="client address" class="form-input" placeholder="House No, Street, Barangay, City" required>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="2">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="section-heading-icon w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600 border border-slate-200">
                            <i class="bi bi-person-vcard-fill"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Deceased Information</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-5">
                        <div class="md:col-span-8">
                            <label class="field-label">Full Legal Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="deceased_name" value="{{ old('deceased_name') }}" data-validate="letters-spaces" data-label="deceased name" class="form-input" required>
                        </div>

                        <div class="md:col-span-4">
                            <label class="field-label">Date of Birth <span class="text-rose-500">*</span></label>
                            <input type="date" name="born" id="born" value="{{ old('born') }}" data-label="birthdate" class="form-input" required>
                        </div>

                        <div class="md:col-span-12">
                            <label class="field-label">Last Known Address <span class="text-rose-500">*</span></label>
                            <input type="text" name="deceased_address" id="deceased_address" value="{{ old('deceased_address', old('client_address')) }}" data-label="deceased address" class="form-input" required>
                        </div>

                        <div class="md:col-span-3">
                            <label class="field-label">Age</label>
                            <input type="number" name="age" id="age" value="{{ old('age') }}" class="form-input bg-slate-50 text-slate-500 cursor-not-allowed" readonly>
                        </div>

                        <div class="md:col-span-5">
                            <label class="field-label">Date of Death <span class="text-rose-500">*</span></label>
                            <input type="date" name="died" id="died" value="{{ old('died') }}" data-label="date of death" max="{{ now()->toDateString() }}" class="form-input" required>
                        </div>
                    </div>

                    <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <i class="bi bi-shield-check text-emerald-600"></i>
                                    Senior Citizen Verification
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Applies automatic discounts if enabled.</p>
                            </div>

                            <select name="senior_citizen_status" id="senior_citizen_status" data-label="senior citizen status" class="form-input w-full sm:w-48 font-bold text-slate-800">
                                <option value="0" {{ old('senior_citizen_status', '0') === '0' ? 'selected' : '' }}>No / Standard</option>
                                <option value="1" {{ old('senior_citizen_status') === '1' ? 'selected' : '' }}>Yes / Eligible</option>
                            </select>
                        </div>

                        <div id="senior_id_wrap" class="mt-4 pt-4 border-t border-slate-200 hidden">
                            <label class="field-label">Senior Citizen ID Number <span class="text-rose-500">*</span></label>
                            <input type="text" name="senior_citizen_id_number" id="senior_citizen_id_number" value="{{ old('senior_citizen_id_number') }}" data-label="Senior Citizen ID number" class="form-input w-full md:w-1/2" placeholder="Enter Valid ID">
                        </div>

                        <div id="senior_proof_wrap" class="mt-4 hidden">
                            <label class="field-label">Upload Senior ID / Certificate <span class="text-rose-500">*</span></label>
                            <input type="file" name="senior_proof" id="senior_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-input">
                            <p class="text-[11px] text-slate-500 mt-1">Accepted: JPG, PNG, WEBP, PDF. Max 5MB.</p>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="3">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                                <i class="bi bi-box-seam-fill"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Service Package</h3>
                        </div>
                        <div id="package_error" class="hidden text-xs font-bold text-rose-500 bg-rose-50 px-3 py-1 rounded-md">Selection Required</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="packageCardList">
                        @foreach($packages as $pkg)
                            @php
                                $promoNow = $pkg->promo_is_active
                                    && (!$pkg->promo_starts_at || $pkg->promo_starts_at->lte(now()))
                                    && (!$pkg->promo_ends_at || $pkg->promo_ends_at->gte(now()));
                            @endphp

                            <div class="package-card-item">
                                <label class="package-card relative flex flex-col p-5 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-slate-300 transition-all group focus-within:ring-2 focus-within:ring-slate-900">
                                    <input
                                        type="radio"
                                        name="package_id"
                                        value="{{ $pkg->id }}"
                                        class="package-radio"
                                        data-name="{{ $pkg->name }}"
                                        data-price="{{ $pkg->price }}"
                                        data-promo-now="{{ $promoNow ? '1' : '0' }}"
                                        data-promo-type="{{ $pkg->promo_value_type }}"
                                        data-promo-value="{{ $pkg->promo_value }}"
                                        data-promo-label="{{ $pkg->promo_label }}"
                                        data-inclusions="{{ e((string) $pkg->inclusions) }}"
                                        data-freebies="{{ e((string) $pkg->freebies) }}"
                                        {{ (string) old('package_id') === (string) $pkg->id ? 'checked' : '' }}
                                        required
                                    >

                                    <div class="mb-2 flex items-start justify-between">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $pkg->coffin_type ?? 'Standard' }}</span>

                                        @if($promoNow)
                                            <span class="bg-emerald-100 text-emerald-800 text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                                                {{ $pkg->promo_label }}
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="package-title text-base font-bold text-slate-900 leading-tight">{{ $pkg->name }}</h4>

                                    <div class="mt-6 flex items-baseline justify-between">
                                        <div class="text-xl font-black text-slate-900">&#8369;{{ number_format($pkg->price, 2) }}</div>
                                        <div class="check-dot w-6 h-6 rounded-full border-2 flex items-center justify-center bg-white transition-all">
                                            <i class="bi bi-check text-emerald-600 text-base"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach

                        @if(empty($entryMode) || $entryMode === 'main')
                            <div class="package-card-item relative">
                                <label class="package-card relative flex flex-col p-5 rounded-xl border-2 border-dashed border-amber-200 bg-amber-50/40 cursor-pointer hover:border-amber-300 transition-all group focus-within:ring-2 focus-within:ring-amber-500/50">
                                    <input
                                        type="radio"
                                        name="package_id"
                                        value="custom"
                                        id="custom_package_radio"
                                        class="package-radio"
                                        data-name="Client Preference"
                                        data-price="0"
                                        data-inclusions=""
                                        data-freebies=""
                                    >

                                    <div class="mb-2 flex items-start justify-between">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-amber-700">Client Preference</span>
                                        <span class="bg-white text-amber-700 text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider border border-amber-200">Custom</span>
                                    </div>

                                    <h4 class="package-title text-base font-bold text-amber-900 leading-tight">Enter package name</h4>

                                    <div class="mt-6 flex items-baseline justify-between">
                                        <div class="text-xl font-black text-amber-900">&#8369;<span id="custom_package_price_display">0.00</span></div>
                                        <div class="check-dot w-6 h-6 rounded-full border-2 border-amber-300 flex items-center justify-center bg-white transition-all">
                                            <i class="bi bi-check text-emerald-600 text-base"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endif
                    </div>

                    @if(empty($entryMode) || $entryMode === 'main')
                        <div id="custom_package_fields" class="hidden mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 rounded-xl border border-amber-100 bg-amber-50/70 px-4 py-4">
                            <div class="md:col-span-2">
                                <label class="field-label">Client Preference Package Name</label>
                                <input type="text" name="custom_package_name" id="client_pref_package" value="{{ old('custom_package_name') }}" class="form-input" placeholder="Custom package name">
                            </div>

                            <div>
                                <label class="field-label">Inclusions (comma or line separated)</label>
                                <textarea name="custom_package_inclusions" id="client_pref_inclusions" class="form-textarea" rows="3" placeholder="Hearse, viewing chapel, etc.">{{ old('custom_package_inclusions') }}</textarea>
                            </div>

                            <div>
                                <label class="field-label">Freebies / Notes</label>
                                <textarea name="custom_package_freebies" id="client_pref_freebies" class="form-textarea" rows="3" placeholder="Flower standee, mass card, etc.">{{ old('custom_package_freebies') }}</textarea>
                            </div>

                            <div>
                                <label class="field-label">Preferred Price</label>
                                <input type="number" step="0.01" min="0" name="custom_package_price" id="client_pref_price" value="{{ old('custom_package_price') }}" class="form-input" placeholder="0.00">
                            </div>
                        </div>
                    @endif

                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">
                                <i class="bi bi-list-check mr-1"></i> Inclusions
                            </h5>
                            <ul id="selected_package_inclusions" class="space-y-2 text-xs font-medium text-slate-700">
                                <li class="text-slate-400 italic">Select a package to view.</li>
                            </ul>
                        </div>

                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">
                                <i class="bi bi-gift mr-1"></i> Freebies & Notes
                            </h5>
                            <ul id="selected_package_freebies" class="space-y-2 text-xs font-medium text-slate-700">
                                <li class="text-slate-400 italic">Package freebies will appear here.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="4">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="section-heading-icon w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600 border border-slate-200">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Service Details</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div>
                            <label class="field-label">Funeral Service Date <span class="text-rose-500">*</span></label>
                            <input type="date" name="funeral_service_at" id="funeral_service_at" value="{{ old('funeral_service_at') }}" data-label="funeral service date" class="form-input" required>
                        </div>

                        <div>
                            <label class="field-label">Service Type <span class="text-rose-500">*</span></label>
                            <input type="text" name="service_type" id="service_type" value="{{ old('service_type') }}" data-label="service type" class="form-input" placeholder="e.g., Burial, Cremation" required>
                        </div>

                        <div class="md:col-span-1">
                            <label class="field-label">Wake Location <span class="text-rose-500">*</span></label>
                            <input type="text" name="wake_location" id="wake_location" value="{{ old('wake_location') }}" data-label="wake location" class="form-input" placeholder="Chapel or House Address" required>
                        </div>

                        <div>
                            <label class="field-label">Wake Days</label>
                            <input type="number" name="wake_days" id="wake_days" value="{{ old('wake_days') }}" data-label="wake days" class="form-input" placeholder="e.g. 5">
                        </div>

                        <div>
                            <label class="field-label">Interment / Burial Schedule <span class="text-rose-500">*</span></label>
                            <input type="datetime-local" name="interment_at" id="interment_at" value="{{ old('interment_at') }}" data-label="interment or burial date" class="form-input" required>
                            <div id="interment_at_error" class="hidden text-xs font-bold text-rose-500 mt-1">Must be after funeral date.</div>
                        </div>

                        <div>
                            <label class="field-label">Place of Interment / Cemetery <span class="text-rose-500">*</span></label>
                            <input type="text" name="place_of_cemetery" value="{{ old('place_of_cemetery') }}" data-label="place of interment or cemetery" class="form-input" placeholder="Cemetery Name" required>
                        </div>

                        <div>
                            <label class="field-label">Case Status <span class="text-rose-500">*</span></label>

                            @if($isOtherEntryMode)
                                <input type="hidden" name="case_status" id="case_status" value="COMPLETED">
                                <div class="form-input bg-emerald-50 border-emerald-200 text-emerald-800 font-bold flex items-center gap-2 pointer-events-none">
                                    <i class="bi bi-check-circle-fill"></i> Completed
                                </div>
                            @else
                                <select name="case_status" id="case_status" data-label="case status" class="form-input" required>
                                    <option value="DRAFT" {{ old('case_status', 'ACTIVE') === 'DRAFT' ? 'selected' : '' }}>Pending</option>
                                    <option value="ACTIVE" {{ old('case_status', 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>Ongoing</option>
                                    <option value="COMPLETED" {{ old('case_status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                </select>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">
                        <label class="field-label">Upload Deceased Photo (Optional)</label>
                        <input type="file" name="deceased_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300">
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="5">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="section-heading-icon w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600 border border-slate-200">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Billing Statement</h3>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Package Price</label>
                                    <div class="flex items-center rounded-lg border border-slate-300 bg-slate-50 focus-within:ring-2 focus-within:ring-slate-900">
                                        <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                                        <input type="number" step="0.01" name="package_amount" id="package_amount" value="{{ old('package_amount') }}" class="w-full border-0 focus:outline-none focus:ring-0 bg-slate-50 font-bold p-3" readonly>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label">Additional Charges</label>
                                    <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                                        <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                                        <input type="number" step="0.01" min="0" name="additional_service_amount" id="additional_service_amount" value="{{ old('additional_service_amount') }}" data-label="additional charges" class="w-full border-0 focus:outline-none focus:ring-0 font-bold p-3" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="field-label">Description of Extras</label>
                                <textarea name="additional_services" id="additional_services" rows="2" data-label="additional services" class="form-textarea" placeholder="Detail any add-ons here...">{{ old('additional_services') }}</textarea>
                            </div>

                            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50 grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Discounts & Tax</h4>
                                </div>

                                <div>
                                    <label class="field-label">Discount Applied</label>
                                    <input type="text" id="auto_discount_type" value="None" class="form-input bg-white text-xs font-bold" readonly>
                                </div>

                                <div>
                                    <label class="field-label">Discount Value</label>
                                    <input type="text" id="auto_discount_amount" value="PHP 0.00" class="form-input bg-white text-xs font-bold text-emerald-600" readonly>
                                </div>

                                <div>
                                    <label class="field-label">Tax Rate (%)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="tax_rate" value="{{ old('tax_rate', '0') }}" data-label="tax rate" class="form-input pr-10">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">%</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label">Tax Amount</label>
                                    <input type="text" id="tax_amount_display" value="PHP 0.00" class="form-input bg-white text-xs font-bold text-rose-600" readonly>
                                </div>

                                <div id="discount_help_wrap" class="col-span-2 text-[11px] font-medium text-slate-500 flex gap-2 items-start mt-1">
                                    <i class="bi bi-info-circle text-blue-500"></i>
                                    <span id="discount_help_text_secondary">Discount is tied to Senior Citizen status.</span>
                                </div>
                            </div>
                        </div>

                        <div class="self-start sticky top-6">
                            <div class="sticky-review-card bg-slate-900 rounded-[1.35rem] p-6 text-white">
                                <h4 class=" text-white text-center uppercase tracking-widest opacity-90 mb-5">Summary Dashboard</h4>

                                <div class="space-y-4 text-sm">
                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70">Package</span>
                                        <span class="font-bold"><span class="mr-1">&#8369;</span><span id="summary_package_price">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70">Extras</span>
                                        <span class="font-bold"><span class="mr-1">&#8369;</span><span id="summary_additional">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70 text-xs">Subtotal</span>
                                        <span class="font-bold text-xs"><span class="mr-1">&#8369;</span><span id="summary_subtotal">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between text-emerald-400">
                                        <span class="text-xs">Discount (<span id="summary_discount_source">None</span>)</span>
                                        <span class="font-bold">- <span class="mr-1">&#8369;</span><span id="summary_discount">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-b border-white/10 pb-3">
                                        <span class="opacity-70 text-xs">Tax</span>
                                        <span class="font-bold text-xs"><span class="mr-1">&#8369;</span><span id="summary_tax">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between items-end pt-2">
                                        <span class="text-base font-bold">Total Due</span>
                                        <span class="text-2xl font-black"><span class="mr-1">&#8369;</span><span id="summary_total">0.00</span></span>
                                    </div>

                                    <div class="flex justify-between border-t border-white/10 pt-3 text-xs">
                                        <span class="opacity-70">Payment Status</span>
                                        <span id="summary_payment_status" class="font-bold">UNPAID</span>
                                    </div>

                                    <div class="flex justify-between text-xs">
                                        <span class="opacity-70">Balance</span>
                                        <span class="font-bold"><span class="mr-1">&#8369;</span><span id="summary_balance">0.00</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="6">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="section-heading-icon w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600 border border-slate-200">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">{{ $isOtherEntryMode ? 'Payment Confirmation' : 'Process Payment' }}</h3>
                    </div>

                    @if($isOtherEntryMode)
                        <input type="hidden" name="mark_as_paid" id="mark_as_paid" value="1">
                        <input type="hidden" name="payment_type" id="payment_type" value="FULL">

                        <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900 flex gap-3">
                            <i class="bi bi-shield-check text-amber-600 text-lg"></i>
                            <div>
                                <strong class="block text-xs uppercase tracking-wider mb-0.5">Verification Rule Applied</strong>
                                This external branch report will be saved as fully paid and routed automatically.
                            </div>
                        </div>
                    @endif

                    @if(!$isOtherEntryMode)
                        <div class="mb-8">
                            <label class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="checkbox" name="mark_as_paid" id="mark_as_paid" value="1" {{ old('mark_as_paid') ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                <div>
                                    <span class="block text-sm font-bold text-slate-800">Record Initial Payment Now</span>
                                    <span class="block text-xs text-slate-500">Toggle this to log a deposit or full settlement.</span>
                                </div>
                            </label>
                        </div>
                    @endif

                    <div id="payment_form_fields" class="{{ $isOtherEntryMode ? '' : 'hidden' }} space-y-6">
                        @if(!$isOtherEntryMode)
                            <div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="payment_type_group">
                                    <label class="payment-type-card flex items-center justify-center py-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-slate-300 transition-colors">
                                        <input type="radio" name="payment_type" value="FULL" class="payment-type-radio sr-only" {{ old('payment_type') === 'FULL' ? 'checked' : '' }}>
                                        <span class="text-sm font-bold text-slate-800">Full Payment</span>
                                    </label>

                                    <label class="payment-type-card flex items-center justify-center py-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-slate-300 transition-colors">
                                        <input type="radio" name="payment_type" value="PARTIAL" class="payment-type-radio sr-only" {{ old('payment_type') === 'PARTIAL' ? 'checked' : '' }}>
                                        <span class="text-sm font-bold text-slate-800">Partial Payment</span>
                                    </label>
                                </div>

                                <div id="payment_type_error" class="hidden text-xs font-bold text-rose-500 mt-2">Please select a type.</div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="field-label">Amount Tendered</label>
                                <div class="flex items-center rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-slate-900">
                                    <span class="pl-3 pr-2 text-slate-500 font-bold">&#8369;</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        name="amount_paid"
                                        id="amount_paid"
                                        value="{{ old('amount_paid') }}"
                                        data-label="amount paid"
                                        class="w-full border-0 focus:outline-none focus:ring-0 font-black p-3 text-lg"
                                        placeholder="0.00"
                                    >
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2" id="payment_amount_hint">
                                    {{ $isOtherEntryMode ? 'Must equal full amount' : 'Enter amount received' }}
                                </p>
                            </div>

                            <div>
                                <label class="field-label">Transaction Timestamp</label>
                                <input type="datetime-local" name="paid_at" id="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}" data-label="payment date" class="form-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Status</div>
                                <div id="payment_status_preview" class="text-base font-black text-slate-800">UNPAID</div>
                            </div>

                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                <div class="text-[9px] font-black uppercase tracking-widest text-emerald-600 mb-1">Paid Amount</div>
                                <div class="text-base font-black text-emerald-800">&#8369; <span id="payment_paid_preview">0.00</span></div>
                            </div>

                            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-white">
                                <div class="text-[9px] font-black uppercase tracking-widest opacity-50 mb-1">Remaining Balance</div>
                                <div class="text-base font-black">&#8369; <span id="payment_balance_preview">0.00</span></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="wizard-panel hidden" data-step="7">
                    <div class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                            <i class="bi bi-clipboard-check"></i>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Final Review</h3>
                            <p class="text-xs text-slate-500">Ensure all encoded data is accurate.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        @foreach(['client' => 'Client', 'deceased' => 'Deceased', 'package' => 'Package', 'service' => 'Service', 'billing' => 'Billing', 'payment' => 'Payment'] as $id => $title)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 group relative shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
                                <button
                                    type="button"
                                    class="review-edit absolute top-4 right-4 text-[10px] font-bold uppercase tracking-wider text-slate-400 hover:text-slate-900 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                    data-jump-step="{{ $loop->iteration }}"
                                >
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">{{ $title }}</h4>
                                <dl class="space-y-2 text-xs text-slate-700" id="review_{{ $id }}"></dl>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8 rounded-xl border border-amber-300 bg-amber-100 p-5 text-slate-900 flex items-start sm:items-center gap-4 shadow-md ring-1 ring-amber-200/70">
                        <input type="checkbox" name="confirm_review" id="confirm_review" value="1" class="w-5 h-5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300" required>
                        <label for="confirm_review" class="text-base font-semibold leading-snug">
                            I verify that I have reviewed this data and it is accurate to the best of my knowledge.
                        </label>
                    </div>
                </section>
            </div>

            <div class="footer-action-bar border-t border-slate-200 bg-white/90 p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <button type="button" id="wizardPrev" class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all disabled:opacity-30 disabled:hover:bg-white">
                    Back
                </button>

                <div class="flex gap-3 w-full sm:w-auto">
                    <button type="button" id="wizardNext" class="w-full sm:w-auto flex-1 px-8 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold transition-all shadow-[0_10px_24px_rgba(15,23,42,0.14)] hover:bg-[#9c5a1a] hover:-translate-y-0.5">
                        Continue
                    </button>

                    <button type="submit" id="saveIntakeRecord" class="hidden w-full sm:w-auto flex-1 px-8 py-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 transition-all shadow-[0_10px_24px_rgba(5,150,105,0.16)] disabled:opacity-50">
                        Save Record
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const f = document.getElementById('intakeWizardForm');
    if (!f) return;

    const panels = [...document.querySelectorAll('.wizard-panel')];
    const tabs = [...document.querySelectorAll('.wizard-tab')];
    const prev = document.getElementById('wizardPrev');
    const next = document.getElementById('wizardNext');
    const save = document.getElementById('saveIntakeRecord');

    const formContent = document.getElementById('intakeFormContent');
    const lockOverlay = document.getElementById('intake_lock_overlay');

    const branch = document.getElementById('branch_id');
    const branchError = document.getElementById('branch_error');
    const branchBtns = [...document.querySelectorAll('.branch-toggle')];
    const nextCode = document.getElementById('next_case_code');
    const nextMap = @json($nextCodeMap ?? []);
    const defCode = @json($nextCode ?? 'FC0001');
    const mainBranchCodeDefault = document.getElementById('branch_code_main_default')?.value || 'BR001';

    const requestDate = document.getElementById('service_requested_at');
    const requestDateDisplay = document.getElementById('service_requested_display');

    const reporterBox = document.getElementById('external_reporter_box');
    const reporterName = document.getElementById('reporter_name');
    const reportedAt = document.getElementById('reported_at');

    const born = document.getElementById('born');
    const died = document.getElementById('died');
    const age = document.getElementById('age');

    const clientAddr = document.getElementById('client_address');
    const deceasedAddr = document.getElementById('deceased_address');

    const wakeDays = document.getElementById('wake_days');
    const funeral = document.getElementById('funeral_service_at');
    const interment = document.getElementById('interment_at');
    const intermentErr = document.getElementById('interment_at_error');

    const senior = document.getElementById('senior_citizen_status');
    const seniorIdWrap = document.getElementById('senior_id_wrap');
    const seniorId = document.getElementById('senior_citizen_id_number');
    const proofWrap = document.getElementById('senior_proof_wrap');
    const proofInput = document.getElementById('senior_proof');

    const pkgList = document.getElementById('packageCardList');
    const pkgRadios = [...document.querySelectorAll('.package-radio')];
    const pkgCards = [...document.querySelectorAll('.package-card')];
    const pkgAmount = document.getElementById('package_amount');
    const packageError = document.getElementById('package_error');

    const customPkgRadio = document.getElementById('custom_package_radio');
    const prefPkg = document.getElementById('client_pref_package');
    const prefPrice = document.getElementById('client_pref_price');
    const prefIncl = document.getElementById('client_pref_inclusions');
    const prefFree = document.getElementById('client_pref_freebies');
    const customPkgFields = document.getElementById('custom_package_fields');
    const customPkgPriceDisplay = document.getElementById('custom_package_price_display');

    const inclusions = document.getElementById('selected_package_inclusions');
    const freebies = document.getElementById('selected_package_freebies');

    const addAmt = document.getElementById('additional_service_amount');
    const taxRate = document.getElementById('tax_rate');
    const taxAmountDisplay = document.getElementById('tax_amount_display');
    const additionalServices = document.getElementById('additional_services');
    const autoDiscountType = document.getElementById('auto_discount_type');
    const autoDiscountAmount = document.getElementById('auto_discount_amount');
    const discountHelpSecondary = document.getElementById('discount_help_text_secondary');

    const mark = document.getElementById('mark_as_paid');
    const payWrap = document.getElementById('payment_form_fields');
    const payTypeRadios = [...document.querySelectorAll('.payment-type-radio')];
    const payTypeCards = [...document.querySelectorAll('.payment-type-card')];
    const paymentTypeError = document.getElementById('payment_type_error');
    const amountPaid = document.getElementById('amount_paid');
    const paidAt = document.getElementById('paid_at');
    const payHint = document.getElementById('payment_amount_hint');

    const summaryPackage = document.getElementById('summary_package_price');
    const summaryAdd = document.getElementById('summary_additional');
    const summarySubtotal = document.getElementById('summary_subtotal');
    const summaryDiscountSource = document.getElementById('summary_discount_source');
    const summaryDiscount = document.getElementById('summary_discount');
    const summaryTax = document.getElementById('summary_tax');
    const summaryTotal = document.getElementById('summary_total');
    const summaryStatus = document.getElementById('summary_payment_status');
    const summaryBalance = document.getElementById('summary_balance');

    const paymentStatusPreview = document.getElementById('payment_status_preview');
    const paymentPaidPreview = document.getElementById('payment_paid_preview');
    const paymentBalancePreview = document.getElementById('payment_balance_preview');

    const reviewClient = document.getElementById('review_client');
    const reviewDeceased = document.getElementById('review_deceased');
    const reviewService = document.getElementById('review_service');
    const reviewPackage = document.getElementById('review_package');
    const reviewBilling = document.getElementById('review_billing');
    const reviewPayment = document.getElementById('review_payment');
    const reviewEditButtons = [...document.querySelectorAll('.review-edit')];

    const branchToast = document.getElementById('branch_toast');
    const pkgToast = document.getElementById('package_toast');

    const seniorPct = {{ (float) ($seniorDiscountPercent ?? config('funeral.senior_discount_percent', 20)) }};
    const isOtherEntryMode = @json($isOtherEntryMode);
    const otherBranchWindowClosed = @json($otherBranchWindowClosed);
    const totalSteps = panels.length;
    const initialStep = Math.max(1, Math.min(totalSteps, Number(@json($initialStep ?? 1))));
    const serverInitialBranchId = @json((string) $initialSelectedBranchId);
    const serverOldBranchExists = @json(old('branch_id') !== null);

    let step = 1;
    let branchToastTimer = null;
    let packageToastTimer = null;
    const today = new Date().toLocaleDateString('en-CA');

    const num = (value) => Number.parseFloat(value || 0) || 0;
    const fmt = (value) => num(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const cardRadio = (card) => card?.querySelector?.('.package-radio') || null;
    const pkg = () => pkgRadios.find((radio) => radio.checked);
    const payNow = () => !mark ? false : (mark.type === 'checkbox' ? mark.checked : String(mark.value) === '1');
    const payType = () => payTypeRadios.find((radio) => radio.checked)?.value || document.getElementById('payment_type')?.value || '';
    const list = (value) => String(value || '').split(/\r?\n|,|;/).map((item) => item.trim()).filter(Boolean);
    const textOrDash = (value) => String(value || '').trim() || '-';

    const formatDateOnly = (value) => {
        if (!value) return '-';
        const parsed = new Date(`${value}T00:00:00`);
        return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    };

    const formatDateTime = (value) => {
        if (!value) return '-';
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleString(undefined, { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    };

    const detailRow = (label, value) => `
        <div class="flex items-start justify-between gap-4 py-1.5">
            <dt class="text-slate-500">${escapeHtml(label)}</dt>
            <dd class="text-right font-bold text-slate-800 break-words max-w-[50%]">${escapeHtml(value)}</dd>
        </div>
    `;

    const setHidden = (wrap, input, hidden) => {
        if (!wrap) return;
        wrap.classList.toggle('hidden', hidden);
        if (input) {
            input.disabled = hidden;
            if (hidden) input.setCustomValidity('');
        }
    };

    const clearFieldMessage = (field) => {
        if (field?.setCustomValidity) field.setCustomValidity('');
    };

    const labelFor = (field) => field?.dataset?.label || field?.getAttribute('placeholder') || 'this field';

    const branchCode = () => {
        return branchBtns.find((button) => String(button.dataset.branchId) === String(branch.value))?.dataset.branchCode || '-';
    };

    const isBranchSelectedForOtherMode = () => {
        if (!isOtherEntryMode) return true;
        return branchCode() !== '-';
    };

    const shouldLockOtherBranchIntake = () => {
        return isOtherEntryMode && !isBranchSelectedForOtherMode();
    };

    const showToast = (element, message = null, duration = 2400, type = 'branch') => {
        if (!element) return;

        if (type === 'branch' && branchToastTimer) clearTimeout(branchToastTimer);
        if (type === 'package' && packageToastTimer) clearTimeout(packageToastTimer);

        if (message) {
            const textNode = element.querySelector('.package-toast-text');
            if (textNode) {
                textNode.textContent = message;
            } else {
                element.textContent = message;
            }
        }

        element.classList.remove('hidden', 'opacity-0');
        element.classList.add('opacity-100', 'toast-visible');

        const timer = setTimeout(() => {
            element.classList.remove('opacity-100', 'toast-visible');
            element.classList.add('opacity-0');
            setTimeout(() => element.classList.add('hidden'), 260);
        }, duration);

        if (type === 'branch') branchToastTimer = timer;
        if (type === 'package') packageToastTimer = timer;
    };

    const showBranchToast = (message) => showToast(branchToast, message, 2600, 'branch');
    const showPackageToast = (message) => showToast(pkgToast, message, 2300, 'package');

    const branchPromptMessage = () => {
        const code = branchCode() !== '-' ? branchCode() : mainBranchCodeDefault;

        if (!isOtherEntryMode) {
            return `You're now recording for the Main Branch (${code}). You can start entering the case details.`;
        }

        if (!isBranchSelectedForOtherMode()) {
            return "You're recording for another branch. Please choose which branch to continue.";
        }

        return `Great, you're now recording for Branch ${code}. You can continue with the case details.`;
    };

    const syncBranchUiLock = () => {
        const locked = shouldLockOtherBranchIntake();

        formContent?.classList.toggle('intake-locked', locked);
        lockOverlay?.classList.toggle('hidden', !locked);

        if (locked) {
            next?.setAttribute('disabled', 'disabled');
            save?.setAttribute('disabled', 'disabled');
        } else {
            next?.removeAttribute('disabled');
            if (!otherBranchWindowClosed) save?.removeAttribute('disabled');
        }

        if (otherBranchWindowClosed) {
            save?.setAttribute('disabled', 'disabled');
        }
    };

    const hasSeniorDiscount = () => {
        return senior?.value === '1'
            && Boolean(String(seniorId?.value || '').trim())
            && (proofInput?.files?.length > 0);
    };

    const autoDiscountMeta = () => {
        if (hasSeniorDiscount()) {
            return {
                type: 'Senior Citizen Discount',
                source: `Senior (${seniorPct}%)`,
                amount: num(pkgAmount?.value) * (seniorPct / 100),
                message: `Senior Citizen discount is applied automatically at ${seniorPct}% of the package price.`,
            };
        }

        if (senior?.value === '1' && !String(seniorId?.value || '').trim()) {
            return {
                type: 'Senior Citizen Discount',
                source: 'Pending Senior ID',
                amount: 0,
                message: 'Senior Citizen ID is required to apply the discount.',
            };
        }

        if (senior?.value === '1' && String(seniorId?.value || '').trim() && !(proofInput?.files?.length > 0)) {
            return {
                type: 'Senior Citizen Discount',
                source: 'Pending Senior Proof',
                amount: 0,
                message: 'Senior Citizen proof is required to apply the discount.',
            };
        }

        return {
            type: 'None',
            source: 'None',
            amount: 0,
            message: 'No automatic discount applies when Senior Citizen is marked No.',
        };
    };

    const discount = () => {
        const meta = autoDiscountMeta();
        return { amount: meta.amount, source: meta.source, type: meta.type, message: meta.message };
    };

    const totals = () => {
        const packagePrice = num(pkgAmount?.value);
        const additional = num(addAmt?.value);
        const subtotal = packagePrice + additional;
        const disc = discount();
        const taxableBase = Math.max(subtotal - Math.min(disc.amount, subtotal), 0);
        const rate = Math.max(0, Math.min(num(taxRate?.value), 100));
        const tax = Math.max(taxableBase * (rate / 100), 0);
        const total = Math.max(taxableBase + tax, 0);

        if (payNow() && payType() === 'FULL' && amountPaid) {
            amountPaid.value = total > 0 ? total.toFixed(2) : '';
        }

        const paid = payNow() ? num(amountPaid?.value) : 0;
        const balance = Math.max(total - paid, 0);
        const status = !payNow() || paid <= 0 ? 'UNPAID' : paid < total ? 'PARTIAL' : 'PAID';

        return { packagePrice, additional, subtotal, disc, tax, total, paid, balance, status, rate };
    };

    const syncRequestDate = () => {
        if (!requestDateDisplay || !requestDate?.value) return;
        requestDateDisplay.textContent = formatDateOnly(requestDate.value);
    };

    const syncAge = () => {
        if (!born?.value || !died?.value) {
            if (age) age.value = '';
            return;
        }

        const birth = new Date(`${born.value}T00:00:00`);
        const death = new Date(`${died.value}T00:00:00`);

        if (Number.isNaN(birth.getTime()) || Number.isNaN(death.getTime()) || death < birth) {
            age.value = '';
            return;
        }

        let years = death.getFullYear() - birth.getFullYear();
        if (death.getMonth() < birth.getMonth() || (death.getMonth() === birth.getMonth() && death.getDate() < birth.getDate())) {
            years -= 1;
        }

        age.value = String(years);
    };

    const syncDateConstraints = () => {
        if (died) died.max = today;
        if (funeral) funeral.min = died?.value || '';
        if (paidAt && died?.value) paidAt.min = `${died.value}T00:00`;
    };

    const syncPreferredPackage = () => {
        if (!customPkgRadio) return;

        if (prefPkg) customPkgRadio.dataset.name = prefPkg.value || 'Client Preference';
        if (prefPrice) customPkgRadio.dataset.price = prefPrice.value || '0';
        if (prefIncl) customPkgRadio.dataset.inclusions = prefIncl.value || '';
        if (prefFree) customPkgRadio.dataset.freebies = prefFree.value || '';

        if (customPkgPriceDisplay) customPkgPriceDisplay.textContent = fmt(customPkgRadio.dataset.price || 0);
        if (pkgAmount && customPkgRadio.checked) pkgAmount.value = customPkgRadio.dataset.price || '';
    };

    const renderPkg = (shouldToast = false) => {
        const selected = pkg();

        pkgCards.forEach((card) => {
            const radio = cardRadio(card);
            const active = !!radio?.checked;
            card.classList.toggle('border-slate-800', active);
            card.classList.toggle('bg-slate-50', active);
            card.classList.toggle('ring-2', active);
            card.classList.toggle('selected', active);
        });

        if (pkgAmount) pkgAmount.value = selected?.dataset.price || '';
        if (packageError) packageError.classList.add('hidden');

        if (inclusions) {
            inclusions.innerHTML = list(selected?.dataset.inclusions)
                .map((item) => `<li class="flex items-start gap-2"><i class="bi bi-check2 text-emerald-500 mt-0.5"></i> <span>${escapeHtml(item)}</span></li>`)
                .join('') || '<li class="text-slate-400 italic">Select a package to view inclusions.</li>';
        }

        if (freebies) {
            freebies.innerHTML = list(selected?.dataset.freebies)
                .map((item) => `<li class="flex items-start gap-2"><i class="bi bi-gift text-amber-500 mt-0.5"></i> <span>${escapeHtml(item)}</span></li>`)
                .join('') || '<li class="text-slate-400 italic">Package freebies and notes will appear here.</li>';
        }

        syncPreferredPackage();

        if (customPkgFields) {
            customPkgFields.classList.toggle('hidden', selected !== customPkgRadio);
        }

        if (selected && shouldToast) {
            const name = selected.dataset.name || 'this package';
            showPackageToast(`You selected the ${name} package.`);
        }
    };

    const syncControls = () => {
        if (deceasedAddr && clientAddr && deceasedAddr.dataset.manual !== '1') deceasedAddr.value = clientAddr.value;

        setHidden(seniorIdWrap, seniorId, senior?.value !== '1');
        if (seniorId) seniorId.required = senior?.value === '1';

        setHidden(proofWrap, proofInput, senior?.value !== '1');
        if (proofInput) proofInput.required = senior?.value === '1';

        if (payWrap && mark?.type === 'checkbox') payWrap.classList.toggle('hidden', !payNow());

        if (amountPaid) {
            amountPaid.disabled = !payNow();
            amountPaid.readOnly = payType() === 'FULL';
        }

        payTypeCards.forEach((card) => {
            const active = card.querySelector('.payment-type-radio')?.checked;
            card.classList.toggle('border-slate-800', !!active);
            card.classList.toggle('bg-slate-50', !!active);
            card.classList.toggle('ring-2', !!active);
        });

        if (discountHelpSecondary) discountHelpSecondary.textContent = autoDiscountMeta().message;
        syncBranchUiLock();
    };

    const renderReview = () => {
        const selected = pkg();
        const t = totals();

        if (reviewClient) {
            reviewClient.innerHTML = [
                detailRow('Branch', branchCode() !== '-' ? branchCode() : (!isOtherEntryMode ? mainBranchCodeDefault : '-')),
                detailRow('Client Name', textOrDash(f.elements.client_name?.value)),
                detailRow('Relationship', textOrDash(f.elements.client_relationship?.value)),
                detailRow('Contact Number', textOrDash(f.elements.client_contact_number?.value)),
                detailRow('Address', textOrDash(f.elements.client_address?.value)),
                ...(isOtherEntryMode ? [
                    detailRow('Reporter Name', textOrDash(f.elements.reporter_name?.value)),
                    detailRow('Reporter Contact', textOrDash(f.elements.reporter_contact?.value)),
                    detailRow('Reported Date', formatDateTime(f.elements.reported_at?.value)),
                ] : []),
            ].join('');
        }

        if (reviewDeceased) {
            reviewDeceased.innerHTML = [
                detailRow('Deceased Name', textOrDash(f.elements.deceased_name?.value)),
                detailRow('Address', textOrDash(f.elements.deceased_address?.value)),
                detailRow('Birthdate', formatDateOnly(f.elements.born?.value)),
                detailRow('Age', textOrDash(f.elements.age?.value)),
                detailRow('Date of Death', formatDateOnly(f.elements.died?.value)),
                detailRow('Senior Citizen', senior?.value === '1' ? `Yes${seniorId?.value ? ` - ${seniorId.value}` : ''}` : 'No'),
            ].join('');
        }

        if (reviewService) {
            reviewService.innerHTML = [
                detailRow('Service Type', textOrDash(f.elements.service_type?.value)),
                detailRow('Wake Location', textOrDash(f.elements.wake_location?.value)),
                detailRow('Funeral Service Date', formatDateOnly(f.elements.funeral_service_at?.value)),
                detailRow('Interment Date', formatDateTime(f.elements.interment_at?.value)),
                detailRow('Place of Interment', textOrDash(f.elements.place_of_cemetery?.value)),
                detailRow('Case Status', textOrDash(f.elements.case_status?.value)),
                detailRow('Wake Days', textOrDash(f.elements.wake_days?.value)),
            ].join('');
        }

        if (reviewPackage) {
            reviewPackage.innerHTML = [
                detailRow('Selected Package', selected?.dataset?.name || '-'),
                detailRow('Package Price', `PHP ${fmt(t.packagePrice)}`),
                detailRow('Inclusions', list(selected?.dataset.inclusions).join(', ') || '-'),
                detailRow('Freebies / Notes', list(selected?.dataset.freebies).join(', ') || '-'),
            ].join('');
        }

        if (reviewBilling) {
            reviewBilling.innerHTML = [
                detailRow('Additional Services', textOrDash(additionalServices?.value)),
                detailRow('Additional Charges', `PHP ${fmt(t.additional)}`),
                detailRow('Discount Type', t.disc.type),
                detailRow('Discount Amount', `PHP ${fmt(t.disc.amount)}`),
                detailRow('Tax Amount', `PHP ${fmt(t.tax)}`),
                detailRow('Total Amount', `PHP ${fmt(t.total)}`),
            ].join('');
        }

        if (reviewPayment) {
            reviewPayment.innerHTML = [
                detailRow('Payment Recorded', payNow() ? 'Yes' : 'No'),
                detailRow('Payment Type', isOtherEntryMode ? 'FULL (Required)' : (payType() || '-')),
                detailRow('Amount Paid', `PHP ${fmt(t.paid)}`),
                detailRow('Remaining Balance', `PHP ${fmt(t.balance)}`),
                detailRow('Payment Date', formatDateTime(paidAt?.value)),
                detailRow('Payment Status', t.status),
            ].join('');
        }
    };

    const render = () => {
        const t = totals();

        if (summaryPackage) summaryPackage.textContent = fmt(t.packagePrice);
        if (summaryAdd) summaryAdd.textContent = fmt(t.additional);
        if (summarySubtotal) summarySubtotal.textContent = fmt(t.subtotal);
        if (summaryDiscountSource) summaryDiscountSource.textContent = t.disc.source;
        if (summaryDiscount) summaryDiscount.textContent = fmt(t.disc.amount);
        if (summaryTax) summaryTax.textContent = fmt(t.tax);
        if (summaryTotal) summaryTotal.textContent = fmt(t.total);
        if (summaryStatus) summaryStatus.textContent = t.status;
        if (summaryBalance) summaryBalance.textContent = fmt(t.balance);

        if (taxAmountDisplay) taxAmountDisplay.value = `PHP ${fmt(t.tax)}`;
        if (taxRate) taxRate.value = t.rate.toFixed(2);
        if (autoDiscountType) autoDiscountType.value = t.disc.type;
        if (autoDiscountAmount) autoDiscountAmount.value = `PHP ${fmt(t.disc.amount)}`;
        if (discountHelpSecondary) discountHelpSecondary.textContent = t.disc.message;

        if (paymentStatusPreview) paymentStatusPreview.textContent = t.status;
        if (paymentPaidPreview) paymentPaidPreview.textContent = fmt(t.paid);
        if (paymentBalancePreview) paymentBalancePreview.textContent = fmt(t.balance);

        if (payHint) {
            payHint.textContent = payType() === 'FULL'
                ? 'Full payment must equal the total amount due.'
                : payType() === 'PARTIAL'
                    ? 'Partial payment must be less than the total amount due.'
                    : (mark?.type === 'checkbox' && !payNow()
                        ? 'Leave payment unchecked if the client has not paid yet.'
                        : 'Select full or partial payment and enter the amount received.');
        }

        renderReview();
    };

    const clearMessages = () => {
        branchError?.classList.add('hidden');
        packageError?.classList.add('hidden');
        paymentTypeError?.classList.add('hidden');
        intermentErr?.classList.add('hidden');
        [...f.querySelectorAll('input, select, textarea')].forEach(clearFieldMessage);
    };

    const validatePanelFields = (panel) => {
        for (const field of [...panel.querySelectorAll('input, select, textarea')].filter((element) => element.type !== 'hidden' && !element.disabled)) {
            clearFieldMessage(field);

            const value = typeof field.value === 'string' ? field.value.trim() : field.value;

            if (field.required && !value && field.type !== 'checkbox' && field.type !== 'radio') {
                const message = field.tagName === 'SELECT' || field.type === 'date' || field.type === 'datetime-local'
                    ? `Please select ${labelFor(field).toLowerCase()}.`
                    : `Please enter ${labelFor(field).toLowerCase()}.`;

                field.setCustomValidity(message);
                field.reportValidity();
                return false;
            }

            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }

        return true;
    };

    const validate = (targetStep) => {
        clearMessages();

        if (shouldLockOtherBranchIntake()) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Before you continue, please select a branch so we can record the case correctly.';
            showBranchToast('Before you continue, please select a branch so we can record the case correctly.');
            return false;
        }

        const panel = panels.find((element) => Number(element.dataset.step) === targetStep);
        if (!panel) return true;

        if (!validatePanelFields(panel)) return false;

        if (targetStep === 1 && (!branch.value || (isOtherEntryMode && !isBranchSelectedForOtherMode()))) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Before you continue, please select a branch so we can record the case correctly.';
            return false;
        }

        if (targetStep === 2 && died?.value) {
            if (new Date(`${died.value}T00:00:00`) > new Date(`${today}T23:59:59`)) {
                died.setCustomValidity('Date of death cannot be in the future.');
                died.reportValidity();
                return false;
            }
        }

        if (targetStep === 3 && !pkg()) {
            packageError?.classList.remove('hidden');
            return false;
        }

        if (targetStep === 4 && funeral?.value && interment?.value && new Date(interment.value) <= new Date(`${funeral.value}T00:00:00`)) {
            intermentErr?.classList.remove('hidden');
            if (intermentErr) intermentErr.textContent = 'Interment date must be after the funeral service date.';
            return false;
        }

        if (targetStep === 4 && died?.value && funeral?.value && new Date(`${funeral.value}T00:00:00`) < new Date(`${died.value}T00:00:00`)) {
            funeral.setCustomValidity('Funeral service date must be on or after the date of death.');
            funeral.reportValidity();
            return false;
        }

        if (targetStep === 6 && payNow()) {
            const t = totals();

            if (!payType()) {
                paymentTypeError?.classList.remove('hidden');
                return false;
            }

            if (!amountPaid?.value) {
                amountPaid.setCustomValidity('Please enter amount paid.');
                amountPaid.reportValidity();
                return false;
            }

            if (!paidAt?.value) {
                paidAt.setCustomValidity('Please select payment date.');
                paidAt.reportValidity();
                return false;
            }

            if (payType() === 'FULL' && t.paid < t.total) {
                amountPaid.setCustomValidity('Full payment must match the total amount due.');
                amountPaid.reportValidity();
                return false;
            }

            if (payType() === 'PARTIAL' && t.paid >= t.total) {
                amountPaid.setCustomValidity('Partial payment must be less than the total amount due.');
                amountPaid.reportValidity();
                return false;
            }
        }

        if (targetStep === 7) {
            const confirmBox = document.getElementById('confirm_review');
            if (confirmBox && !confirmBox.checked) {
                confirmBox.setCustomValidity('Please confirm that the information is correct.');
                confirmBox.reportValidity();
                return false;
            }
        }

        return true;
    };

    const go = (targetStep) => {
        if (shouldLockOtherBranchIntake() && targetStep > 1) {
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Please select a branch first before encoding.';
            showBranchToast('Select Branch 2 or Branch 3 before entering case details.');
            targetStep = 1;
        }

        step = Math.max(1, Math.min(totalSteps, targetStep));

        panels.forEach((panel) => panel.classList.toggle('hidden', Number(panel.dataset.step) !== step));

        tabs.forEach((tab) => {
            const active = Number(tab.dataset.step) === step;
            tab.classList.toggle('active-step', active);
            tab.classList.toggle('border-[#9c5a1a]', active);
            tab.classList.toggle('text-[#9c5a1a]', active);
            tab.classList.toggle('border-transparent', !active);
            tab.classList.toggle('text-slate-500', !active);
            tab.classList.toggle('hover:bg-white/70', !active);

            const span = tab.querySelector('span');
            if (span) {
                span.classList.toggle('opacity-50', !active);
                span.classList.toggle('text-[#9c5a1a]', active);
            }
        });

        if (prev) prev.disabled = step === 1;
        next?.classList.toggle('hidden', step === totalSteps);
        save?.classList.toggle('hidden', step !== totalSteps);

        if (next) {
            next.innerHTML = step === totalSteps - 1
                ? 'Review <i class="bi bi-chevron-right text-[10px] ml-1"></i>'
                : 'Continue <i class="bi bi-chevron-right text-[10px] ml-1"></i>';
        }

        if (step === totalSteps) renderReview();
    };

    const syncBranch = (showNotice = false) => {
        branchBtns.forEach((button) => {
            const active = String(button.dataset.branchId) === String(branch.value);

            button.classList.toggle('bg-slate-900', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('shadow-sm', active);

            button.classList.toggle('bg-white', !active);
            button.classList.toggle('text-slate-600', !active);
            button.classList.toggle('border', !active);
            button.classList.toggle('border-slate-200', !active);
        });

        if (nextCode) nextCode.textContent = nextMap[String(branch.value)] || defCode;

        reporterBox?.classList.toggle('hidden', !isOtherEntryMode);
        if (reporterName) reporterName.required = isOtherEntryMode;
        if (reportedAt) reportedAt.required = isOtherEntryMode;

        syncBranchUiLock();

        if (showNotice) {
            showBranchToast(branchPromptMessage());
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const targetStep = Number(tab.dataset.step);

            if (targetStep > step) {
                for (let index = step; index < targetStep; index += 1) {
                    if (!validate(index)) return;
                }
            }

            go(targetStep);
        });
    });

    reviewEditButtons.forEach((button) => {
        button.addEventListener('click', () => go(Number(button.dataset.jumpStep)));
    });

    prev?.addEventListener('click', () => go(step - 1));

    next?.addEventListener('click', () => {
        if (!validate(step)) return;
        go(step + 1);
    });

    branchBtns.forEach((button) => {
        button.addEventListener('click', () => {
            branch.value = button.dataset.branchId;
            syncBranch(true);
            render();
        });
    });

    if (deceasedAddr && clientAddr && String(deceasedAddr.value || '').trim() !== '' && deceasedAddr.value !== clientAddr.value) {
        deceasedAddr.dataset.manual = '1';
    }

    clientAddr?.addEventListener('input', syncControls);

    deceasedAddr?.addEventListener('input', () => {
        deceasedAddr.dataset.manual = deceasedAddr.value ? '1' : '';
    });

    born?.addEventListener('change', () => {
        syncAge();
        syncDateConstraints();
        render();
    });

    died?.addEventListener('change', () => {
        syncAge();
        syncDateConstraints();
        render();
    });

    interment?.addEventListener('change', render);

    wakeDays?.addEventListener('input', () => {
        wakeDays.dataset.manual = wakeDays.value ? '1' : '';
    });

    [senior, seniorId, addAmt, taxRate, amountPaid, paidAt].forEach((element) => {
        element?.addEventListener('input', () => {
            clearFieldMessage(element);
            syncControls();
            render();
        });

        element?.addEventListener('change', () => {
            clearFieldMessage(element);
            syncControls();
            render();
        });
    });

    proofInput?.addEventListener('change', () => {
        syncControls();
        render();
    });

    if (mark?.type === 'checkbox') {
        mark.addEventListener('change', () => {
            syncControls();
            render();
        });
    }

    payTypeRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            syncControls();
            render();
        });
    });

    pkgRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            renderPkg(true);
            syncControls();
            render();
        });
    });

    pkgCards.forEach((card) => {
        card.addEventListener('click', () => {
            const radio = cardRadio(card);
            if (radio && !radio.checked) radio.checked = true;
            renderPkg(true);
            syncControls();
            render();
        });
    });

    if (pkgList) {
        pkgList.addEventListener('click', (event) => {
            const card = event.target.closest('.package-card');
            if (!card) return;
            const radio = cardRadio(card);
            if (radio && !radio.checked) radio.checked = true;
            renderPkg(true);
            syncControls();
            render();
        });
    }

    prefPkg?.addEventListener('input', () => {
        syncPreferredPackage();
        render();
    });

    prefPrice?.addEventListener('input', () => {
        syncPreferredPackage();
        render();
    });

    prefIncl?.addEventListener('input', () => {
        syncPreferredPackage();
        renderPkg(false);
        render();
    });

    prefFree?.addEventListener('input', () => {
        syncPreferredPackage();
        renderPkg(false);
        render();
    });

    document.querySelectorAll('[data-validate]').forEach((element) => {
        element.addEventListener('input', () => {
            clearFieldMessage(element);

            if (element.dataset.validate === 'letters-spaces') {
                element.value = element.value.replace(/[^A-Za-zÀ-ÿ\s.'-]/g, '');
            }

            if (element.dataset.validate === 'digits') {
                element.value = element.value.replace(/\D/g, '');
            }
        });
    });

    f.addEventListener('submit', (event) => {
        if (isOtherEntryMode && otherBranchWindowClosed) {
            event.preventDefault();
            return;
        }

        if (shouldLockOtherBranchIntake()) {
            event.preventDefault();
            branchError?.classList.remove('hidden');
            if (branchError) branchError.textContent = 'Please select a branch first before encoding.';
            showBranchToast('Select Branch 2 or Branch 3 before entering case details.');
            return;
        }

        for (let index = 1; index <= totalSteps - 1; index += 1) {
            if (!validate(index)) {
                event.preventDefault();
                go(index);
                return;
            }
        }
    });

    const initializeBranchState = () => {
        if (isOtherEntryMode) {
            if (serverOldBranchExists) {
                branch.value = serverInitialBranchId;
            } else {
                branch.value = '';
            }
        } else {
            branch.value = serverInitialBranchId || branch.value;
        }
    };

    initializeBranchState();
    syncBranch(false);
    syncRequestDate();
    syncControls();
    syncAge();
    syncDateConstraints();
    syncPreferredPackage();
    renderPkg(false);
    render();
    go(initialStep);
    showBranchToast(branchPromptMessage());
})();
</script>
