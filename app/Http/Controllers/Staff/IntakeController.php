<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\Payment;
use App\Support\Discount\CaseDiscountResolver;
use App\Support\Validation\FieldRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IntakeController extends Controller
{
    private const CLIENT_RELATIONSHIP_OPTIONS = [
        'Spouse',
        'Mother',
        'Father',
        'Daughter',
        'Son',
        'Sibling',
        'Grandchild',
        'Relative',
        'Guardian',
        'Friend',
        'Other',
    ];

    public function create()
    {
        return redirect()->route('intake.main.create');
    }

    public function createMain()
    {
        return $this->renderForm('main');
    }

    public function createOther()
    {
        if (!auth()->user()->canEncodeAnyBranch()) {
            abort(403);
        }

        return $this->renderForm('other');
    }

    public function store(Request $request)
    {
        return $this->storeByMode($request, 'main');
    }

    public function storeMain(Request $request)
    {
        return $this->storeByMode($request, 'main');
    }

    public function storeOther(Request $request)
    {
        if (!auth()->user()->canEncodeAnyBranch()) {
            abort(403);
        }

        return $this->storeByMode($request, 'other');
    }

    private function renderForm(string $mode)
    {
        $user = auth()->user();
        $staffBranchId = (int) $user->branch_id;
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();

        $packages = Package::where('is_active', true)
            ->orderBy('name')
            ->get();

        $branchQuery = Branch::where('is_active', true)->orderBy('branch_code');
        if ($mode === 'other') {
            $branchQuery->where('branch_code', '!=', 'BR001');
        } elseif ($canEncodeAnyBranch) {
            $branchQuery->where('branch_code', 'BR001');
        } else {
            $branchQuery->where('id', $staffBranchId);
        }

        $branches = $branchQuery->get();
        $defaultBranchId = old('branch_id') ?: ($branches->first()?->id ?? $staffBranchId);

        return view('staff.intake.create', [
            'packages' => $packages,
            'branches' => $branches,
            'nextCode' => $this->nextCaseCode((int) $defaultBranchId),
            'nextCodeMap' => $branches->mapWithKeys(function ($branch) {
                return [$branch->id => $this->nextCaseCode((int) $branch->id)];
            }),
            'canEncodeAnyBranch' => $canEncodeAnyBranch,
            'entryMode' => $mode,
            'defaultBranchId' => (int) $defaultBranchId,
            'formAction' => $mode === 'other'
                ? route('intake.other.store')
                : route('intake.main.store'),
            'clientRelationshipOptions' => self::CLIENT_RELATIONSHIP_OPTIONS,
            'seniorDiscountPercent' => (float) config('funeral.senior_discount_percent', 20),
        ]);
    }

    private function storeByMode(Request $request, string $mode)
    {
        $trimFields = [
            'client_name',
            'client_relationship',
            'client_contact_number',
            'client_address',
            'deceased_name',
            'deceased_address',
            'wake_location',
            'place_of_cemetery',
            'additional_services',
            'reporter_name',
            'reporter_contact',
            'service_type',
            'custom_package_name',
            'custom_package_inclusions',
            'custom_package_freebies',
        ];
        foreach ($trimFields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                $request->merge([$field => is_string($value) ? trim($value) : $value]);
            }
        }
        if ($request->has('client_contact_number')) {
            $digitsOnly = preg_replace('/\D+/', '', (string) $request->input('client_contact_number'));
            $request->merge(['client_contact_number' => $digitsOnly]);
        }

        $validated = $request->validate([
            'service_requested_at' => 'required|date|before_or_equal:today',
            'client_name' => FieldRules::personName(),
            'client_relationship' => ['required', 'string', 'max:100', Rule::in(self::CLIENT_RELATIONSHIP_OPTIONS)],
            'client_contact_number' => ['required', 'string', 'regex:/^\d{7,15}$/'],
            'client_address' => 'required|string|max:255',

            'deceased_name' => FieldRules::personName(),
            'deceased_address' => 'required|string|max:255',
            'born' => 'required|date|before_or_equal:today',
            'died' => 'required|date|after_or_equal:born|before_or_equal:today',
            'senior_citizen_status' => 'required|boolean',
            'senior_citizen_id_number' => 'nullable|string|max:100|required_if:senior_citizen_status,1',
            'senior_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120|required_if:senior_citizen_status,1',
            'wake_location' => 'required|string|max:255',
            'funeral_service_at' => 'required|date|after_or_equal:died|before_or_equal:today',
            'interment_at' => 'required|date|after:funeral_service_at|before_or_equal:today',
            'wake_days' => 'nullable|integer|min:1|max:30',
            'place_of_cemetery' => 'required|string|max:255',
            'service_type' => 'required|string|max:100',
            'case_status' => 'required|in:DRAFT,ACTIVE,COMPLETED',
            'deceased_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',

            'branch_id' => 'required|integer|exists:branches,id',
            'package_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ((string) $value === 'custom') {
                        return;
                    }
                    if (!is_numeric($value)) {
                        $fail('Please select a valid package.');
                        return;
                    }
                    $exists = Package::where('is_active', true)
                        ->where('id', (int) $value)
                        ->exists();
                    if (!$exists) {
                        $fail('Selected package is unavailable.');
                    }
                },
            ],
            'custom_package_name' => 'required_if:package_id,custom|string|max:150',
            'custom_package_price' => 'required_if:package_id,custom|numeric|min:0',
            'custom_package_inclusions' => 'nullable|string|max:1000',
            'custom_package_freebies' => 'nullable|string|max:1000',
            'additional_services' => 'nullable|string|max:1000',
            'additional_service_amount' => 'nullable|numeric|min:0',
            'reporter_name' => FieldRules::personName(false),
            'reporter_contact' => 'nullable|string|max:50|regex:/^[0-9]+$/',
            'reported_at' => 'nullable|date|before_or_equal:now',
            'tax_rate' => 'nullable|numeric|min:0|max:100',

            'mark_as_paid' => 'nullable|boolean',
            'payment_type' => 'required_if:mark_as_paid,1|in:FULL,PARTIAL',
            'paid_at' => 'required_if:mark_as_paid,1|date|after_or_equal:died',
            'amount_paid' => 'required_if:mark_as_paid,1|numeric|min:0.01',
            'confirm_review' => 'accepted',
        ], [
            'service_requested_at.required' => 'Request date is required.',
            'service_requested_at.before_or_equal' => 'Request date cannot be in the future.',
            'client_name.regex' => 'Client name may contain letters, spaces, apostrophes, periods, and hyphens only.',
            'deceased_name.regex' => 'Deceased name may contain letters, spaces, apostrophes, periods, and hyphens only.',
            'client_relationship.in' => 'Please select a valid relationship to the deceased.',
            'client_contact_number.regex' => 'Contact number must be 7 to 15 digits (numbers only).',
            'reporter_contact.regex' => 'Reporter contact number must contain numbers only.',
            'died.after_or_equal' => 'Died date must be on or after born date.',
            'died.before_or_equal' => 'Date of death cannot be in the future.',
            'funeral_service_at.after_or_equal' => 'Funeral service date must be on or after the date of death.',
            'funeral_service_at.before_or_equal' => 'Funeral service date cannot be in the future.',
            'interment_at.after' => 'Interment date/time must be after the funeral service date/time.',
            'interment_at.before_or_equal' => 'Interment date/time cannot be in the future.',
            'senior_citizen_id_number.required_if' => 'Senior Citizen ID number is required when senior status is Yes.',
            'paid_at.after_or_equal' => 'Paid date/time must be on or after date of death.',
            'reported_at.before_or_equal' => 'Reported date/time cannot be in the future.',
            'confirm_review' => 'You must confirm that the information is correct before saving.',
        ]);

        if (!$this->isIntermentAfterDeathDate($validated['died'] ?? null, $validated['interment_at'] ?? null)) {
            return back()->withErrors([
                'interment_at' => 'Interment date must be after the date of death.',
            ])->withInput();
        }

        $user = auth()->user();
        $staffBranchId = (int) $user->branch_id;
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();

        if ($mode === 'other' && !$canEncodeAnyBranch) {
            abort(403);
        }

        $entrySource = 'MAIN';
        if ($mode === 'main') {
            $mainBranch = Branch::where('is_active', true)
                ->where('branch_code', 'BR001')
                ->first();

            if (!$mainBranch) {
                return back()->withErrors(['branch_id' => 'Main branch (BR001) is not available.'])->withInput();
            }

            $branchId = (int) $mainBranch->id;
        } else {
            $branchId = (int) $validated['branch_id'];
            $branch = Branch::where('id', $branchId)
                ->where('is_active', true)
                ->first();

            if (!$branch || strtoupper((string) $branch->branch_code) === 'BR001') {
                return back()->withErrors([
                    'branch_id' => 'Please select a valid non-main branch for external report entry.',
                ])->withInput();
            }

            if (empty($validated['reporter_name'])) {
                return back()->withErrors([
                    'reporter_name' => 'Reporter name is required for other-branch intake.',
                ])->withInput();
            }

            if (empty($validated['reported_at'])) {
                return back()->withErrors([
                    'reported_at' => 'Reported date/time is required for other-branch batch intake.',
                ])->withInput();
            }

            $entrySource = 'OTHER_BRANCH';
        }

        $isCustomPackage = (string) $validated['package_id'] === 'custom';
        $selectedPackageId = $isCustomPackage ? null : (int) $validated['package_id'];

        $package = null;
        if (!$isCustomPackage) {
            $package = Package::where('id', $selectedPackageId)
                ->where('is_active', true)
                ->first();

            if (!$package) {
                return back()->withErrors(['package_id' => 'Selected package is unavailable.'])->withInput();
            }
        }

        $branch = Branch::where('id', $branchId)
            ->where('is_active', true)
            ->first();
        if (!$branch) {
            return back()->withErrors(['branch_id' => 'Selected branch is unavailable.'])->withInput();
        }
        if ($mode === 'main') {
            $entrySource = strtoupper((string) $branch->branch_code) === 'BR001' ? 'MAIN' : 'OTHER_BRANCH';
        }

        $normalizedClientName = strtolower(trim((string) $validated['client_name']));
        $normalizedClientContact = trim((string) ($validated['client_contact_number'] ?? ''));
        $normalizedClientAddress = strtolower(trim((string) $validated['client_address']));
        $normalizedDeceasedAddress = strtolower(trim((string) $validated['deceased_address']));
        $duplicateClient = Client::query()
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedClientName])
            ->whereRaw('COALESCE(contact_number, "") = ?', [$normalizedClientContact])
            ->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedClientAddress])
            ->first();

        if ($duplicateClient) {
            return back()->withErrors([
                'client_name' => 'Duplicate client detected (same name, contact number, and address).',
            ])->withInput();
        }

        $duplicateDeceasedQuery = Deceased::query()
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim((string) $validated['deceased_name']))])
            ->whereDate('died', $validated['died'])
            ->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedDeceasedAddress]);

        if ($duplicateDeceasedQuery->exists()) {
            return back()->withErrors([
                'deceased_name' => 'Duplicate deceased record detected (same name, date of death, and address).',
            ])->withInput();
        }

        $duplicateActiveCaseQuery = FuneralCase::query()
            ->where('branch_id', $branchId)
            ->whereIn('case_status', ['DRAFT', 'ACTIVE'])
            ->whereHas('deceased', function ($query) use ($validated, $normalizedDeceasedAddress) {
                $query->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim((string) $validated['deceased_name']))])
                    ->whereDate('died', $validated['died']);

                if (!empty($validated['born'])) {
                    $query->whereDate('born', $validated['born']);
                } else {
                    $query->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedDeceasedAddress]);
                }
            });

        if ($duplicateActiveCaseQuery->exists()) {
            return back()->withErrors([
                'deceased_name' => 'An active case already exists for this deceased record.',
            ])->withInput();
        }

        $scheduleConflictCount = FuneralCase::where('branch_id', $branchId)
            ->where(function ($query) use ($validated) {
                $funeralDate = Carbon::parse($validated['funeral_service_at'])->toDateString();
                $intermentDate = Carbon::parse($validated['interment_at'])->toDateString();
                $query->whereDate('funeral_service_at', $funeralDate)
                    ->orWhereDate('interment_at', $intermentDate);
            })
            ->count();

        $servicePackageName = $isCustomPackage
            ? ($validated['custom_package_name'] ?? 'Client Preference')
            : $package->name;
        $coffinType = $isCustomPackage ? 'CUSTOM' : $package->coffin_type;
        $packagePrice = round((float) ($isCustomPackage ? $validated['custom_package_price'] : $package->price), 2);
        $additionalServiceAmount = round((float) ($validated['additional_service_amount'] ?? 0), 2);
        $subtotal = round($packagePrice + $additionalServiceAmount, 2);
        $age = $this->resolveAge($validated['born'] ?? null, $validated['died'] ?? null);
        if ($age === null) {
            return back()->withErrors([
                'age' => 'Age could not be calculated. Please check birthdate and date of death.',
            ])->withInput();
        }
        $postedAge = $request->input('age');
        if ($postedAge !== null && is_numeric($postedAge) && (int) $postedAge !== $age) {
            return back()->withErrors([
                'age' => "Age must match the birthdate and date of death ({$age} years).",
            ])->withInput();
        }
        $wakeDays = $this->resolveWakeDays(
            $validated['wake_days'] ?? null,
            $validated['died'] ?? null,
            $validated['interment_at'] ?? null
        );

        $discountResolver = app(CaseDiscountResolver::class);
        $discountPayload = $this->resolveAutomaticIntakeDiscount($validated, $discountResolver, $packagePrice);
        if (!empty($discountPayload['error_field'])) {
            return back()->withErrors([
                $discountPayload['error_field'] => $discountPayload['error_message'],
            ])->withInput();
        }
        $discountAmount = (float) $discountPayload['discount_amount'];
        $net = round(max($subtotal - $discountAmount, 0), 2);
        $taxRate = round((float) ($validated['tax_rate'] ?? 0), 2);
        $taxAmount = $taxRate > 0 ? round(max($net * ($taxRate / 100), 0), 2) : 0.00;
        $total = round(max($net + $taxAmount, 0), 2);

        $markAsPaid = $request->boolean('mark_as_paid');
        $allowOverpayment = (bool) config('funeral.allow_overpayment', false);
        $photoPath = null;
        $seniorProofPath = null;
        if ($request->hasFile('deceased_photo')) {
            $photoPath = $request->file('deceased_photo')->store('deceased-photos', 'public');
        }
        if ($request->hasFile('senior_proof')) {
            $seniorProofPath = $request->file('senior_proof')->store('senior-proofs', 'public');
        }

        if ($mode === 'other' && !$markAsPaid) {
            return back()->withErrors([
                'mark_as_paid' => 'Other-branch reports must be completed and fully paid before encoding.',
            ])->withInput();
        }

        if ($markAsPaid) {
            $amountPaid = round((float) $validated['amount_paid'], 2);
            if (!$allowOverpayment && $amountPaid > $total) {
                return back()->withErrors([
                    'payment' => "Amount paid cannot exceed total due ({$total}).",
                ])->withInput();
            }

            if ($mode === 'other' && $amountPaid < $total) {
                return back()->withErrors([
                    'amount_paid' => 'Other-branch completed reports require full payment amount.',
                ])->withInput();
            }

            $paymentType = strtoupper((string) ($validated['payment_type'] ?? ''));
            if ($paymentType === 'FULL' && $amountPaid < $total) {
                return back()->withErrors([
                    'payment_type' => 'Full payment must match the total amount due.',
                ])->withInput();
            }
            if ($paymentType === 'PARTIAL' && $amountPaid >= $total) {
                return back()->withErrors([
                    'payment_type' => 'Partial payment must be less than the total amount due.',
                ])->withInput();
            }
        }

        if ($mode === 'other') {
            $reportedAt = Carbon::parse($validated['reported_at']);
            $now = now();
            $cutoffHour = (int) config('funeral.other_branch_report_cutoff_hour', 18);
            $cutoffHour = max(min($cutoffHour, 23), 0);
            $todayStart = $now->copy()->startOfDay();
            $cutoffToday = $now->copy()->startOfDay()->addHours($cutoffHour);

            if ($now->gt($cutoffToday)) {
                return back()->withErrors([
                    'reported_at' => "Batch intake window is closed for today. Submit tomorrow between 00:00 and {$cutoffToday->format('H:i')}.",
                ])->withInput();
            }

            if (!$reportedAt->isSameDay($now)) {
                return back()->withErrors([
                    'reported_at' => 'Reported date/time must be within today for daily batch intake.',
                ])->withInput();
            }

            if ($reportedAt->gt($now)) {
                return back()->withErrors([
                    'reported_at' => 'Reported date/time cannot be in the future.',
                ])->withInput();
            }

            if ($reportedAt->lt($todayStart) || $reportedAt->gt($cutoffToday)) {
                return back()->withErrors([
                    'reported_at' => "Other-branch completed reports must be submitted within today's batch window (00:00 to {$cutoffToday->format('H:i')}).",
                ])->withInput();
            }
        }

        $initialPaid = $markAsPaid ? round((float) $validated['amount_paid'], 2) : 0.00;
        $initialBalance = round(max($total - $initialPaid, 0), 2);
        $initialPaymentStatus = 'UNPAID';
        if ($initialPaid > 0 && $initialBalance > 0) {
            $initialPaymentStatus = 'PARTIAL';
        }
        if ($initialBalance <= 0 && $total > 0) {
            $initialPaymentStatus = 'PAID';
        }

        $caseStatus = $mode === 'other' ? 'COMPLETED' : $validated['case_status'];
        $verificationStatus = $mode === 'other' ? 'PENDING' : 'VERIFIED';
        $verifiedBy = $mode === 'other' ? null : $user->id;
        $verifiedAt = $mode === 'other' ? null : now();
        $verificationNote = $mode === 'other'
            ? 'Pending admin verification (other-branch completed report).'
            : 'Auto-verified main-branch intake.';
        $initialPaymentType = $markAsPaid ? strtoupper((string) ($validated['payment_type'] ?? '')) : null;

        try {
            DB::transaction(function () use (
                $validated,
                $branchId,
                $package,
                $subtotal,
                $additionalServiceAmount,
                $discountAmount,
                $taxRate,
                $taxAmount,
                $total,
                $age,
                $wakeDays,
                
                $markAsPaid,
                $photoPath,
                $user,
                $initialPaid,
                $initialBalance,
                $initialPaymentStatus,
                $entrySource,
                $mode,
                $discountPayload,
                $caseStatus,
                $verificationStatus,
                $verifiedBy,
                $verifiedAt,
                $verificationNote,
                $initialPaymentType
            ) {
                $client = Client::create([
                    'branch_id' => $branchId,
                    'full_name' => $validated['client_name'],
                    'relationship_to_deceased' => $validated['client_relationship'],
                    'contact_number' => $validated['client_contact_number'] ?? null,
                    'address' => $validated['client_address'],
                ]);

                $intermentAt = !empty($validated['interment_at'])
                    ? Carbon::parse($validated['interment_at'])
                    : null;

                $deceased = Deceased::create([
                    'branch_id' => $branchId,
                    'client_id' => $client->id,
                    'address' => $validated['deceased_address'],
                    'full_name' => $validated['deceased_name'],
                    'born' => $validated['born'] ?? null,
                    'died' => $validated['died'] ?? null,
                    'date_of_death' => $validated['died'] ?? null,
                    'age' => $age,
                    'interment' => $intermentAt?->toDateString(),
                    'interment_at' => $intermentAt,
                    'wake_days' => $wakeDays,
                    'place_of_cemetery' => $validated['place_of_cemetery'],
                    'senior_citizen_status' => (bool) $validated['senior_citizen_status'],
                    'senior_citizen_id_number' => $validated['senior_citizen_id_number'] ?? null,
                    'photo_path' => $photoPath,
                    'senior_proof_path' => $seniorProofPath,
                ]);

                $funeralCase = FuneralCase::create([
                    'branch_id' => $branchId,
                    'client_id' => $client->id,
                    'deceased_id' => $deceased->id,
                    'package_id' => $selectedPackageId,
                    'custom_package_name' => $isCustomPackage ? $servicePackageName : null,
                    'custom_package_price' => $isCustomPackage ? $packagePrice : null,
                    'custom_package_inclusions' => $isCustomPackage ? ($validated['custom_package_inclusions'] ?? null) : null,
                    'custom_package_freebies' => $isCustomPackage ? ($validated['custom_package_freebies'] ?? null) : null,
                    'case_code' => $this->nextCaseCode($branchId),
                    'service_type' => $validated['service_type'],
                    'service_requested_at' => Carbon::parse($validated['service_requested_at'])->toDateString(),
                    'service_package' => $servicePackageName,
                    'coffin_type' => $coffinType,
                    'wake_location' => $validated['wake_location'],
                    'funeral_service_at' => Carbon::parse($validated['funeral_service_at'])->toDateString(),
                    'additional_services' => $validated['additional_services'] ?? null,
                    'additional_service_amount' => $additionalServiceAmount,
                    'subtotal_amount' => $subtotal,
                    'discount_type' => $discountPayload['discount_type'],
                    'discount_value_type' => $discountPayload['discount_value_type'],
                    'discount_value' => $discountPayload['discount_value'],
                    'discount_amount' => $discountAmount,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'discount_note' => $discountPayload['discount_note'],
                    'total_amount' => $total,
                    'total_paid' => $initialPaid,
                    'balance_amount' => $initialBalance,
                    'payment_status' => $initialPaymentStatus,
                    'initial_payment_type' => $initialPaymentType,
                    'paid_at' => $markAsPaid ? Carbon::parse($validated['paid_at']) : null,
                    'case_status' => $caseStatus,
                    'reported_branch_id' => $branchId,
                    'reporter_name' => $mode === 'other' ? ($validated['reporter_name'] ?? null) : null,
                    'reporter_contact' => $mode === 'other' ? ($validated['reporter_contact'] ?? null) : null,
                    'reported_at' => $mode === 'other' && !empty($validated['reported_at'])
                        ? Carbon::parse($validated['reported_at'])
                        : now(),
                    'encoded_by' => $user->id,
                    'entry_source' => $entrySource,
                    'verification_status' => $verificationStatus,
                    'verified_by' => $verifiedBy,
                    'verified_at' => $verifiedAt,
                    'verification_note' => $verificationNote,
                ]);

                if ($markAsPaid) {
                    $paidAt = Carbon::parse($validated['paid_at']);
                    $payment = Payment::create([
                        'funeral_case_id' => $funeralCase->id,
                        'branch_id' => $branchId,
                        'method' => 'CASH',
                        'amount' => round((float) $validated['amount_paid'], 2),
                        'balance_after_payment' => $initialBalance,
                        'payment_status_after_payment' => $initialPaymentStatus,
                        'paid_date' => $paidAt->toDateString(),
                        'paid_at' => $paidAt,
                        'recorded_by' => $user->id,
                    ]);

                    $payment->update([
                        'receipt_number' => Payment::buildReceiptNumber($payment->id, $paidAt),
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['deceased_name' => $e->getMessage()])->withInput();
        }

        $redirectRoute = $mode === 'other'
            ? route('funeral-cases.other-reports')
            : route('funeral-cases.index', ['record_scope' => 'main']);

        $redirectResponse = redirect()
            ->to($redirectRoute)
            ->with('success', 'Case intake record has been saved successfully.')
            ->with('summary', [
                'package' => $servicePackageName,
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'total' => $total,
                'payment_status' => $initialPaymentStatus,
                'discount_source' => $discountPayload['source'],
            ]);

        if ($scheduleConflictCount > 0) {
            $redirectResponse->with('warning', "Similar schedule already exists in this branch ({$scheduleConflictCount} case/s). Record saved; please double-check logistics.");
        }

        return $redirectResponse;
    }

    private function resolveAge(?string $born, ?string $died): ?int
    {
        if (!$born || !$died) {
            return null;
        }

        try {
            $bornDate = Carbon::parse($born);
            $diedDate = Carbon::parse($died);
            if ($diedDate->lessThan($bornDate)) {
                return null;
            }

            return $bornDate->diffInYears($diedDate);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isIntermentAfterDeathDate(?string $died, ?string $intermentAt): bool
    {
        if (!$died || !$intermentAt) {
            return true;
        }

        try {
            $diedDate = Carbon::parse($died)->startOfDay();
            $intermentDate = Carbon::parse($intermentAt)->startOfDay();

            return $intermentDate->greaterThan($diedDate);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveWakeDays(?int $wakeDays, ?string $died, ?string $intermentAt): ?int
    {
        if ($wakeDays !== null) {
            return max(1, min(30, (int) $wakeDays));
        }

        if (!$died || !$intermentAt) {
            return null;
        }

        try {
            $diedDate = Carbon::parse($died)->startOfDay();
            $intermentDate = Carbon::parse($intermentAt)->startOfDay();
            if ($intermentDate->lessThanOrEqualTo($diedDate)) {
                return null;
            }

            return max(1, min(30, $diedDate->diffInDays($intermentDate)));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveAutomaticIntakeDiscount(
        array $validated,
        CaseDiscountResolver $discountResolver,
        float $packagePrice
    ): array {
        if ((bool) ($validated['senior_citizen_status'] ?? false)) {
            if (empty($validated['senior_citizen_id_number'])) {
                return [
                    'error_field' => 'senior_citizen_id_number',
                    'error_message' => 'Senior Citizen ID is required to apply the discount.',
                ];
            }

            return $discountResolver->resolveSelected(
                new Package(['name' => 'Automatic Senior Discount']),
                'SENIOR',
                $packagePrice,
                now()
            );
        }

        return [
            'discount_type' => 'NONE',
            'discount_value_type' => 'AMOUNT',
            'discount_value' => 0,
            'discount_amount' => 0,
            'discount_note' => null,
            'source' => 'None',
        ];
    }

    private function nextCaseCode(int $branchId): string
    {
        $max = FuneralCase::where('branch_id', $branchId)
            ->pluck('case_code')
            ->map(function ($code) {
                return (int) preg_replace('/\D+/', '', (string) $code);
            })
            ->max();

        $next = ($max ?? 0) + 1;

        return 'FC' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
