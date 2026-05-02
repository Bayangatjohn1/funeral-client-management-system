<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\Payment;
use App\Models\ServiceDetail;
use App\Support\AuditLogger;
use App\Support\Discount\CaseDiscountResolver;
use App\Support\Validation\FieldRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IntakeController extends Controller
{
    private const CLIENT_RELATIONSHIP_OPTIONS = [
        'Father',
        'Mother',
        'Spouse',
        'Child',
        'Daughter',
        'Son',
        'Sibling',
        'Grandchild',
        'Relative',
        'Guardian',
        'Friend',
        'Other',
    ];

    private const SUFFIX_OPTIONS = ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];

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
        $user = auth()->user();
        if ($user?->isBranchAdmin()) {
            abort(403);
        }

        if (!$user?->isMainBranchAdmin()) {
            abort(403, 'Only Main Branch Admin can record other-branch reports.');
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
        $user = auth()->user();
        if ($user?->isBranchAdmin()) {
            abort(403);
        }

        if (!$user?->isMainBranchAdmin()) {
            abort(403, 'Only Main Branch Admin can record other-branch reports.');
        }

        return $this->storeByMode($request, 'other');
    }

    private function renderForm(string $mode)
    {
        $user = auth()->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? $user->branch_id ?? 0);
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();

        $packages = Package::with(['packageInclusions', 'packageFreebies'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $branchQuery = Branch::where('is_active', true)->orderBy('branch_code');
        if ($mode === 'other') {
            $branchQuery->whereRaw('UPPER(branch_code) <> ?', ['BR001']);
        } else {
            $branchQuery->where('id', $operationalBranchId);
        }

        $branches = $branchQuery->get();
        $defaultBranchId = old('branch_id') ?: ($branches->first()?->id ?? $operationalBranchId);

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
            'client_first_name',
            'client_last_name',
            'client_middle_name',
            'client_suffix',
            'client_relationship',
            'client_contact_number',
            'client_address',
            'deceased_first_name',
            'deceased_last_name',
            'deceased_middle_name',
            'deceased_suffix',
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
        $this->mergeLegacyIntakeNameParts($request, 'client');
        $this->mergeLegacyIntakeNameParts($request, 'deceased');

        // Funeral service (wake) must be on or after the date of death (business timeline).
        $wakeDateRules = ['required', 'date', 'after_or_equal:died'];
        $intermentDateRules = ['required', 'date', 'after_or_equal:funeral_service_at'];

        $validated = $request->validate([
            // `service_requested_at` is an audit timestamp (encoding time) and not
            // part of the real-world funeral event timeline. Only enforce that
            // it's a valid date and not in the future.
            'service_requested_at' => 'required|date|before_or_equal:today',
            'is_backdated_entry' => 'nullable|boolean',
            'backdated_entry_reason' => 'required_if:is_backdated_entry,1|nullable|string|max:500',
            'client_first_name'  => FieldRules::namePart(),
            'client_last_name'   => FieldRules::namePart(),
            'client_middle_name' => FieldRules::namePart(false),
            'client_suffix'      => ['nullable', 'string', Rule::in(self::SUFFIX_OPTIONS)],
            'client_relationship' => ['required', 'string', 'max:100', Rule::in(self::CLIENT_RELATIONSHIP_OPTIONS)],
            'client_contact_number' => ['required', 'string', 'regex:/^(09\d{9}|639\d{9})$/'],
            'client_email' => 'nullable|email|max:255',
            'client_valid_id_type' => 'nullable|string|max:100',
            'client_valid_id_number' => 'nullable|string|max:100',
            'client_address' => ['required', 'string', 'max:255', $this->addressHasPlaceName()],

            'deceased_first_name'  => FieldRules::namePart(),
            'deceased_last_name'   => FieldRules::namePart(),
            'deceased_middle_name' => FieldRules::namePart(false),
            'deceased_suffix'      => ['nullable', 'string', Rule::in(self::SUFFIX_OPTIONS)],
            'deceased_address' => ['required', 'string', 'max:255', $this->addressHasPlaceName()],
            'born' => 'required|date_format:Y-m-d|before_or_equal:today',
            'died' => 'required|date_format:Y-m-d|after_or_equal:born|before_or_equal:today',
            'civil_status' => 'nullable|string|max:30',
            'senior_citizen_status' => 'required|boolean',
            'senior_citizen_id_number' => 'nullable|string|max:100',
            'pwd_status' => 'nullable|boolean',
            'pwd_id_number' => 'nullable|string|max:100',
            'senior_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'wake_location' => 'required|string|max:255',
            'funeral_service_at' => $wakeDateRules,
            'interment_at' => $intermentDateRules,
            'wake_days' => 'nullable|integer|min:1|max:30',
            'place_of_cemetery' => 'required|string|max:255',
            'case_status' => 'required|in:DRAFT,ACTIVE,COMPLETED',
            'transport_option' => 'nullable|string|max:30',
            'transport_notes' => 'nullable|string|max:500',
            'coffin_length_cm' => 'nullable|numeric|min:30|max:300',
            'coffin_size' => 'nullable|in:SMALL,MEDIUM,LARGE,XL,CUSTOM',
            'embalming_required' => 'nullable|boolean',
            'embalming_status' => 'nullable|string|max:20',
            'embalming_at' => 'nullable|date',
            'embalming_notes' => 'nullable|string|max:500',
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
            'custom_package_name' => 'exclude_unless:package_id,custom|required|string|max:150',
            'custom_package_price' => 'exclude_unless:package_id,custom|required|numeric|min:0',
            'custom_package_inclusions' => 'exclude_unless:package_id,custom|nullable|string|max:1000',
            'custom_package_freebies' => 'exclude_unless:package_id,custom|nullable|string|max:1000',
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
            'confirm_review' => 'nullable|boolean',
        ], [
            'service_requested_at.required' => 'Request date is required.',
            'service_requested_at.before_or_equal' => 'Request date cannot be in the future.',
            'client_first_name.regex'  => FieldRules::nameRegexMessage('Client first name'),
            'client_last_name.regex'   => FieldRules::nameRegexMessage('Client last name'),
            'client_middle_name.regex' => FieldRules::nameRegexMessage('Client middle name'),
            'deceased_first_name.regex'  => FieldRules::nameRegexMessage('Deceased first name'),
            'deceased_last_name.regex'   => FieldRules::nameRegexMessage('Deceased last name'),
            'deceased_middle_name.regex' => FieldRules::nameRegexMessage('Deceased middle name'),
            'client_relationship.in' => 'Please select a valid relationship to the deceased.',
            'client_suffix.in' => 'Please select a valid suffix.',
            'deceased_suffix.in' => 'Please select a valid suffix.',
            'client_contact_number.regex' => 'Please enter a valid Philippine mobile number.',
            'client_address.required' => 'Complete address is required.',
            'deceased_address.required' => 'Complete address is required.',
            'reporter_contact.regex' => 'Reporter contact number must contain numbers only.',
            'born.required' => 'Date of birth is required.',
            'born.date_format' => 'Please enter a valid date of birth.',
            'born.before_or_equal' => 'Date of birth cannot be in the future.',
            'died.required' => 'Date of death is required.',
            'died.date_format' => 'Please enter a valid date of death.',
            'died.after_or_equal' => 'Date of death cannot be earlier than date of birth.',
            'died.before_or_equal' => 'Date of death cannot be in the future.',
            // No longer validating `service_requested_at` relative to death here.
            'funeral_service_at.after_or_equal' => 'Funeral service date must be on or after the date of death.',
            'interment_at.after_or_equal' => 'Interment date must be on or after the funeral service date.',
            'backdated_entry_reason.required_if' => 'Please provide a reason for a backdated request entry.',
            'paid_at.after_or_equal' => 'Paid date/time must be on or after date of death.',
            'reported_at.before_or_equal' => 'Reported date/time cannot be in the future.',
            'confirm_review' => 'You must confirm that the information is correct before saving.',
        ]);
        $validated['service_type'] = 'Burial';
        $validated = $this->normalizeIntakeNameParts($validated);
        if ($response = $this->rejectDuplicateIntakeNameParts($validated, 'client')) {
            return $response;
        }
        if ($response = $this->rejectDuplicateIntakeNameParts($validated, 'deceased')) {
            return $response;
        }

        if (
            !($validated['is_backdated_entry'] ?? false)
            && Carbon::parse($validated['service_requested_at'])->lt(today())
        ) {
            return back()->withErrors([
                'is_backdated_entry' => 'Backdated request entries must be clearly marked.',
                'backdated_entry_reason' => 'Please provide a reason for a backdated request entry.',
            ])->withInput();
        }

        if (!$this->isIntermentAfterDeathDate($validated['died'] ?? null, $validated['interment_at'] ?? null)) {
            return back()->withErrors([
                'interment_at' => 'Interment date must be on or after the date of death.',
            ])->withInput();
        }

        // Always compute wake days from wake start (funeral_service_at) to interment (inclusive).
        $computedWakeDays = $this->resolveWakeDays(
            null,
            $validated['funeral_service_at'] ?? null,
            $validated['interment_at'] ?? null
        );
        if ($computedWakeDays === null) {
            return back()->withErrors([
                'wake_days' => 'Wake days could not be calculated. Please check wake start and interment dates.',
            ])->withInput();
        }
        $validated['wake_days'] = $computedWakeDays;

        $user = auth()->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? $user->branch_id ?? 0);
        $isMainAdmin = $user->isMainBranchAdmin();
        $isBranchAdmin = $user->isBranchAdmin();

        if ($mode === 'other') {
            if ($isBranchAdmin || !$isMainAdmin) {
                return redirect()->back()
                    ->withErrors(['branch_id' => 'Unauthorized for cross-branch intake.']);
            }
        }

        $entrySource = 'MAIN';
        if ($mode === 'main') {
            $mainBranch = Branch::where('is_active', true)
                ->whereKey($operationalBranchId)
                ->first();

            if (!$mainBranch) {
                return back()->withErrors(['branch_id' => 'Assigned branch is not available.'])->withInput();
            }

            $branchId = (int) $mainBranch->id;
        } else {
            $branchId = (int) $validated['branch_id'];
            $branch = Branch::where('id', $branchId)
                ->where('is_active', true)
                ->first();

            if (!$branch || strtoupper((string) $branch->branch_code) === 'BR001') {
                return back()->withErrors([
                    'branch_id' => 'Please select a non-main active branch.',
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
            $package = Package::with(['packageInclusions', 'packageFreebies'])
                ->where('id', $selectedPackageId)
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
            $entrySource = 'MAIN';
        }

        $clientFullName = Client::buildFullName(
            $validated['client_first_name'],
            $validated['client_middle_name'] ?? null,
            $validated['client_last_name'],
            $validated['client_suffix'] ?? null,
        );
        $deceasedFullName = Deceased::buildFullName(
            $validated['deceased_first_name'],
            $validated['deceased_middle_name'] ?? null,
            $validated['deceased_last_name'],
            $validated['deceased_suffix'] ?? null,
        );

        $normalizedClientName     = strtolower(trim($clientFullName));
        $normalizedClientContact  = trim((string) ($validated['client_contact_number'] ?? ''));
        $normalizedClientAddress  = strtolower(trim((string) $validated['client_address']));
        $normalizedDeceasedName   = strtolower(trim($deceasedFullName));
        $normalizedDeceasedAddress = strtolower(trim((string) $validated['deceased_address']));
        $clientDuplicateKey = $request->filled('client_name') ? 'client_name' : 'client_first_name';
        $deceasedDuplicateKey = $request->filled('deceased_name') ? 'deceased_name' : 'deceased_first_name';

        if ($normalizedClientName === $normalizedDeceasedName) {
            return back()->withErrors([
                $deceasedDuplicateKey => 'Client and deceased names cannot be exactly the same. Please verify the entered information.',
            ])->withInput();
        }

        $duplicateClient = Client::query()
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedClientName])
            ->whereRaw('COALESCE(contact_number, "") = ?', [$normalizedClientContact])
            ->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedClientAddress])
            ->first();

        if ($duplicateClient) {
            return back()->withErrors([
                $clientDuplicateKey => 'Duplicate client detected (same name, contact number, and address).',
            ])->withInput();
        }

        $duplicateDeceasedQuery = Deceased::query()
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedDeceasedName])
            ->whereDate('died', $validated['died'])
            ->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedDeceasedAddress]);

        if ($duplicateDeceasedQuery->exists()) {
            return back()->withErrors([
                $deceasedDuplicateKey => 'Duplicate deceased record detected (same name, date of death, and address).',
            ])->withInput();
        }

        $duplicateActiveCaseQuery = FuneralCase::query()
            ->where('branch_id', $branchId)
            ->whereIn('case_status', ['DRAFT', 'ACTIVE'])
            ->whereHas('deceased', function ($query) use ($validated, $normalizedDeceasedName, $normalizedDeceasedAddress) {
                $query->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedDeceasedName])
                    ->whereDate('died', $validated['died']);

                if (!empty($validated['born'])) {
                    $query->whereDate('born', $validated['born']);
                } else {
                    $query->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedDeceasedAddress]);
                }
            });

        if ($duplicateActiveCaseQuery->exists()) {
            return back()->withErrors([
                $deceasedDuplicateKey => 'An active case already exists for this deceased record.',
            ])->withInput();
        }

        if ($request->has('confirm_review') && !$request->boolean('confirm_review')) {
            return back()->withErrors([
                'confirm_review' => 'You must confirm that the information is correct before saving.',
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

        if ($age < 60 && (bool) ($validated['senior_citizen_status'] ?? false)) {
            return back()->withErrors([
                'senior_citizen_status' => 'Senior Citizen can only be set to Yes when computed age is at least 60.',
            ])->withInput();
        }

        if ($age >= 60) {
            $validated['senior_citizen_status'] = true;
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

        // Retry up to 3 times on a duplicate case_number collision (error 1062).
        // The transaction is idempotent: Client, Deceased, and FuneralCase rows are
        // all rolled back on failure, so a fresh attempt starts clean each time.
        $attempt    = 0;
        $maxRetries = 3;
        do {
            $attempt++;
            try {
            DB::transaction(function () use (
                $validated,
                $branchId,
                $selectedPackageId,
                $isCustomPackage,
                $servicePackageName,
                $packagePrice,
                $coffinType,
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
                $seniorProofPath,
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
                $initialPaymentType,
            ) {
                $client = Client::create([
                    'branch_id'   => $branchId,
                    'first_name'  => $validated['client_first_name'],
                    'last_name'   => $validated['client_last_name'],
                    'middle_name' => $validated['client_middle_name'] ?? null,
                    'suffix'      => $validated['client_suffix'] ?? null,
                    'relationship_to_deceased' => $validated['client_relationship'],
                    'contact_number' => $validated['client_contact_number'] ?? null,
                    'email' => $validated['client_email'] ?? null,
                    'valid_id_type' => $validated['client_valid_id_type'] ?? null,
                    'valid_id_number' => $validated['client_valid_id_number'] ?? null,
                    'address' => $validated['client_address'],
                ]);

                $intermentAt = !empty($validated['interment_at'])
                    ? Carbon::parse($validated['interment_at'])
                    : null;

                $deceased = Deceased::create([
                    'branch_id'   => $branchId,
                    'client_id'   => $client->id,
                    'address'     => $validated['deceased_address'],
                    'first_name'  => $validated['deceased_first_name'],
                    'last_name'   => $validated['deceased_last_name'],
                    'middle_name' => $validated['deceased_middle_name'] ?? null,
                    'suffix'      => $validated['deceased_suffix'] ?? null,
                    'born' => $validated['born'] ?? null,
                    'died' => $validated['died'] ?? null,
                    'date_of_death' => $validated['died'] ?? null,
                    'civil_status' => $validated['civil_status'] ?? null,
                    'age' => $age,
                    'interment' => $intermentAt?->toDateString(),
                    'interment_at' => $intermentAt,
                    'wake_days' => $wakeDays,
                    'place_of_cemetery' => $validated['place_of_cemetery'],
                    'senior_citizen_status' => (bool) $validated['senior_citizen_status'],
                    'senior_citizen_id_number' => $validated['senior_citizen_id_number'] ?? null,
                    'pwd_status' => (bool) ($validated['pwd_status'] ?? false),
                    'pwd_id_number' => $validated['pwd_id_number'] ?? null,
                    'photo_path' => $photoPath,
                    'senior_proof_path' => $seniorProofPath,
                ]);

                $funeralCase = FuneralCase::create([
                    'branch_id'   => $branchId,
                    'client_id'   => $client->id,
                    'deceased_id' => $deceased->id,
                    'package_id'  => $selectedPackageId,
                    'case_number' => FuneralCase::nextCaseNumber($branchId),
                    'case_code'   => $this->nextCaseCode($branchId),
                    'custom_package_name'        => $isCustomPackage ? $servicePackageName : null,
                    'custom_package_price'       => $isCustomPackage ? $packagePrice : 0,
                    'custom_package_inclusions'  => $isCustomPackage ? ($validated['custom_package_inclusions'] ?? null) : null,
                    'custom_package_freebies'    => $isCustomPackage ? ($validated['custom_package_freebies'] ?? null) : null,
                    'service_type' => $validated['service_type'],
                    'service_requested_at' => Carbon::parse($validated['service_requested_at'])->toDateString(),
                    'service_package' => $servicePackageName,
                    'coffin_type' => $coffinType,
                    'wake_location' => $validated['wake_location'],
                    'funeral_service_at' => Carbon::parse($validated['funeral_service_at'])->toDateString(),
                    'transport_option' => $validated['transport_option'] ?? null,
                    'transport_notes' => $validated['transport_notes'] ?? null,
                    'coffin_length_cm' => $validated['coffin_length_cm'] ?? null,
                    'coffin_size' => $validated['coffin_size'] ?? null,
                    'embalming_required' => (bool) ($validated['embalming_required'] ?? false),
                    'embalming_status' => $validated['embalming_status'] ?? null,
                    'embalming_at' => !empty($validated['embalming_at']) ? Carbon::parse($validated['embalming_at']) : null,
                    'embalming_notes' => $validated['embalming_notes'] ?? null,
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

                // Populate the normalized service_details row for this case.
                ServiceDetail::create([
                    'funeral_case_id' => $funeralCase->id,
                    'start_of_wake'   => Carbon::parse($validated['funeral_service_at'])->toDateString(),
                    'internment_date' => $intermentAt?->toDateString(),
                    'wake_days'       => $wakeDays,
                    'wake_location'   => $validated['wake_location'],
                    'cemetery_place'  => $validated['place_of_cemetery'],
                    'case_status'     => match ($caseStatus) {
                        'ACTIVE'    => 'ongoing',
                        'COMPLETED' => 'completed',
                        default     => 'pending',
                    },
                ]);

                AuditLogger::log(
                    action: 'case.created',
                    actionType: 'create',
                    entityType: 'funeral_case',
                    entityId: $funeralCase->id,
                    metadata: [
                        'case_code' => $funeralCase->case_code,
                        'case_status' => $funeralCase->case_status,
                        'payment_status' => $funeralCase->payment_status,
                        'entry_source' => $funeralCase->entry_source,
                        'package_id' => $funeralCase->package_id,
                        'service_package' => $funeralCase->service_package,
                        'service_requested_at' => $funeralCase->service_requested_at?->toDateString(),
                        'funeral_service_at' => $funeralCase->funeral_service_at?->toDateString(),
                        'interment_at' => $funeralCase->interment_at?->toDateTimeString(),
                        'is_backdated_entry' => (bool) ($validated['is_backdated_entry'] ?? false),
                        'backdated_entry_reason' => $validated['backdated_entry_reason'] ?? null,
                        'total_amount' => $funeralCase->total_amount,
                        'total_paid' => $funeralCase->total_paid,
                        'balance_amount' => $funeralCase->balance_amount,
                    ],
                    branchId: (int) $funeralCase->branch_id,
                    targetBranchId: (int) $funeralCase->branch_id,
                    status: 'success',
                    remarks: 'Case intake recorded',
                    actionLabel: 'Case created'
                );

                if ($markAsPaid) {
                    $paidAt = Carbon::parse($validated['paid_at']);
                    $payment = Payment::create([
                        'funeral_case_id'  => $funeralCase->id,
                        'branch_id'        => $branchId,
                        'payment_record_no' => Payment::nextPaymentRecordNumber($paidAt),
                        'method'           => 'CASH',
                        'payment_mode'     => 'cash',
                        'payment_method'   => 'cash',
                        'amount'           => round((float) $validated['amount_paid'], 2),
                        'balance_after_payment'        => $initialBalance,
                        'payment_status_after_payment' => $initialPaymentStatus,
                        'paid_date'   => $paidAt->toDateString(),
                        'paid_at'     => $paidAt,
                        'encoded_by'  => $user->id,
                        'recorded_by' => $user->id,
                    ]);

                    $payment->update([
                        'receipt_number' => $payment->payment_record_no,
                    ]);

                    AuditLogger::log(
                        action: 'payment.created',
                        actionType: 'create',
                        entityType: 'payment',
                        entityId: $payment->id,
                        metadata: [
                            'case_id' => $funeralCase->id,
                            'amount' => $payment->amount,
                            'payment_record_no' => $payment->payment_record_no,
                            'payment_status_after' => $payment->payment_status_after_payment,
                            'entry_source' => $funeralCase->entry_source,
                            'changes' => [
                                ['field' => 'total_paid', 'before' => 0, 'after' => $funeralCase->total_paid],
                                ['field' => 'balance_amount', 'before' => $funeralCase->total_amount, 'after' => $funeralCase->balance_amount],
                                ['field' => 'payment_status', 'before' => 'UNPAID', 'after' => $funeralCase->payment_status],
                            ],
                        ],
                        branchId: (int) $funeralCase->branch_id,
                        targetBranchId: (int) $funeralCase->branch_id,
                        status: 'success',
                        remarks: 'Payment recorded during intake',
                        actionLabel: 'Payment recorded'
                    );
                }

            });
            break; // transaction succeeded — exit retry loop
        } catch (\Illuminate\Database\QueryException $e) {
            if ($attempt >= $maxRetries || ($e->errorInfo[1] ?? 0) !== 1062) {
                Log::error('intake.case_creation_failed', [
                    'branch_id' => $branchId,
                    'attempt'   => $attempt,
                    'error'     => $e->getMessage(),
                ]);
                return back()->withErrors(['case' => 'Failed to save intake record. Please try again.'])->withInput();
            }
            Log::warning('intake.case_number_collision', [
                'branch_id' => $branchId,
                'attempt'   => $attempt,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['deceased_name' => $e->getMessage()])->withInput();
        }
        } while ($attempt < $maxRetries);

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

            return $intermentDate->greaterThanOrEqualTo($diedDate);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveWakeDays(?int $wakeDays, ?string $wakeStart, ?string $intermentAt): ?int
    {
        if (!$wakeStart || !$intermentAt) {
            return null;
        }

        try {
            $intermentDate = Carbon::parse($intermentAt)->startOfDay();
            $startDate = Carbon::parse($wakeStart)->startOfDay();
            if ($intermentDate->lessThan($startDate)) {
                return null; // invalid sequence
            }

            return max(1, min(30, $startDate->diffInDays($intermentDate) + 1)); // inclusive counting
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveAutomaticIntakeDiscount(
        array $validated,
        CaseDiscountResolver $discountResolver,
        float $packagePrice
    ): array {
        if ((bool) ($validated['pwd_status'] ?? false)) {
            return $discountResolver->resolveSelected(
                new Package(['name' => 'Automatic PWD Discount']),
                'PWD',
                $packagePrice,
                now()
            );
        }

        if ((bool) ($validated['senior_citizen_status'] ?? false)) {
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

    private function normalizeIntakeNameParts(array $validated): array
    {
        foreach (['client', 'deceased'] as $prefix) {
            foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $field) {
                $key = "{$prefix}_{$field}";
                $validated[$key] = Client::cleanNamePart($validated[$key] ?? null);
            }
        }

        return $validated;
    }

    private function addressHasPlaceName(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if (! preg_match('/\pL/u', (string) $value)) {
                $fail('Complete address must include a valid place name.');
            }
        };
    }

    private function mergeLegacyIntakeNameParts(Request $request, string $prefix): void
    {
        $legacyKey = "{$prefix}_name";
        if (! $request->filled($legacyKey) || $request->filled("{$prefix}_first_name") || $request->filled("{$prefix}_last_name")) {
            return;
        }

        $parts = Client::parseFullName((string) $request->input($legacyKey));
        $request->merge([
            "{$prefix}_first_name" => $parts['first_name'],
            "{$prefix}_middle_name" => $parts['middle_name'],
            "{$prefix}_last_name" => $parts['last_name'],
            "{$prefix}_suffix" => $parts['suffix'],
        ]);
    }

    private function rejectDuplicateIntakeNameParts(array $validated, string $prefix): ?\Illuminate\Http\RedirectResponse
    {
        $first = mb_strtolower((string) ($validated["{$prefix}_first_name"] ?? ''));
        $middle = mb_strtolower((string) ($validated["{$prefix}_middle_name"] ?? ''));
        $last = mb_strtolower((string) ($validated["{$prefix}_last_name"] ?? ''));

        if ($first !== '' && $last !== '' && $first === $last) {
            return back()->withErrors([
                "{$prefix}_last_name" => 'First name and last name cannot be the same.',
            ])->withInput();
        }

        if ($middle !== '' && ($middle === $first || $middle === $last)) {
            return back()->withErrors([
                "{$prefix}_middle_name" => 'Middle name should not be the same as first name or last name.',
            ])->withInput();
        }

        $parts = array_filter([
            "{$prefix}_first_name" => $validated["{$prefix}_first_name"] ?? null,
            "{$prefix}_middle_name" => $validated["{$prefix}_middle_name"] ?? null,
            "{$prefix}_last_name" => $validated["{$prefix}_last_name"] ?? null,
            "{$prefix}_suffix" => $validated["{$prefix}_suffix"] ?? null,
        ], static fn (?string $value): bool => $value !== null);

        $seen = [];
        foreach ($parts as $field => $value) {
            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                return back()->withErrors([
                    $field => ucfirst($prefix) . ' name parts must not repeat exactly.',
                ])->withInput();
            }
            $seen[$key] = true;
        }

        return null;
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
