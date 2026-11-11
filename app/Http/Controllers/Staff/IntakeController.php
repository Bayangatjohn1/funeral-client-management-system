<?php

namespace App\Http\Controllers\Staff;
use Illuminate\Validation\ValidationException;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\AddOnCatalog;
use App\Models\CasketCatalog;
use App\Models\Client;
use App\Models\CaseAttachment;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\FreebieCatalog;
use App\Models\HearseCatalog;
use App\Models\IntakeDraft;
use App\Models\HistoricalCaseSubmission;
use App\Models\PaymentProofUpload;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\ServiceDetail;
use App\Support\AuditLogger;
use App\Support\CeremonyLocation;
use App\Support\CustomPackageSelection;
use App\Support\IntakePricingService;
use App\Support\Payments\PaymentDetails;
use App\Support\Payments\PaymentIdentifier;
use App\Support\Validation\FieldRules;
use App\Support\WakeDuration;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IntakeController extends Controller
{
    private function promotionContext(Package $package, Carbon $at): array
    {
        $promotion = $package->relationLoaded('promotion') ? $package->getRelation('promotion') : $package->promotion;
        $legacy = [
            'promo_label' => $package->promo_label,
            'promo_value_type' => $package->promo_value_type,
            'promo_value' => $package->promo_value !== null ? (string) $package->promo_value : null,
            'promo_starts_at' => $package->promo_starts_at?->toIso8601String(),
            'promo_ends_at' => $package->promo_ends_at?->toIso8601String(),
            'promo_is_active' => (bool) $package->promo_is_active,
            'promo_manually_stopped_at' => $package->promo_manually_stopped_at?->toIso8601String(),
        ];
        $promotionData = $promotion ? [
            'id' => (int) $promotion->id,
            'name' => $promotion->name,
            'discount_type' => $promotion->discount_type,
            'discount_value' => $promotion->discount_value !== null ? (string) $promotion->discount_value : null,
            'starts_at' => $promotion->starts_at?->toDateString(),
            'ends_at' => $promotion->ends_at?->toDateString(),
            'is_enabled' => (bool) $promotion->is_enabled,
            'manually_stopped_at' => $promotion->manually_stopped_at?->toIso8601String(),
        ] : null;
        $status = $package->promotionStatusAt($at);

        return [
            'package_id' => (int) $package->id,
            'as_of' => $at->copy()->timezone('Asia/Manila')->toIso8601String(),
            'status' => $status,
            'promotion' => $promotionData,
            'legacy' => $legacy,
        ];
    }

    private function addOnContext($catalogs, array $selectedIds = []): array
    {
        $ids = collect($selectedIds)->map(fn ($id) => (int) $id)->unique()->values();

        return ['items' => collect($catalogs)
            ->filter(fn (AddOnCatalog $addOn) => $ids->isEmpty() || $ids->contains((int) $addOn->id))
            ->mapWithKeys(fn (AddOnCatalog $addOn) => [(string) $addOn->id => [
                'name' => (string) $addOn->name,
                'price' => number_format((float) $addOn->price, 2, '.', ''),
                'unit' => (string) $addOn->unit,
                'is_active' => (bool) $addOn->is_active,
                'is_available' => (bool) $addOn->is_available,
            ]])->all()];
    }

    private function addOnContextMatches(array $left, array $right): bool
    {
        $normalize = static function (array $context): array {
            $items = $context['items'] ?? [];
            ksort($items, SORT_NATURAL);

            return ['items' => $items];
        };

        return hash_equals(
            hash('sha256', json_encode($normalize($left), JSON_THROW_ON_ERROR)),
            hash('sha256', json_encode($normalize($right), JSON_THROW_ON_ERROR)),
        );
    }

    private function makeAddOnReview(array $previous, array $current, array $quantities = []): array
    {
        $ids = array_unique(array_merge(array_keys($previous['items'] ?? []), array_keys($current['items'] ?? [])));
        $items = collect($ids)->map(function ($id) use ($previous, $current, $quantities) {
            $quantity = max(1, (int) ($quantities[$id] ?? 1));
            $previousPrice = (float) ($previous['items'][$id]['price'] ?? 0);
            $currentPrice = (float) ($current['items'][$id]['price'] ?? 0);

            return [
                'name' => $current['items'][$id]['name'] ?? $previous['items'][$id]['name'] ?? 'Selected add-on',
                'quantity' => $quantity,
                'previous_price' => $previousPrice,
                'current_price' => $currentPrice,
                'previous_line_total' => round($previousPrice * $quantity, 2),
                'current_line_total' => round($currentPrice * $quantity, 2),
                'previous_unit' => (string) ($previous['items'][$id]['unit'] ?? ''),
                'current_unit' => (string) ($current['items'][$id]['unit'] ?? ''),
            ];
        })->values()->all();

        $token = Crypt::encryptString(json_encode($current, JSON_THROW_ON_ERROR));

        return ['items' => $items, 'review_token' => $token, 'context_token' => $token];
    }

    private function promotionContextMatches(array $left, array $right): bool
    {
        $normalize = static fn (array $context): array => [
            'package_id' => $context['package_id'] ?? null,
            'status' => $context['status'] ?? null,
            'promotion' => $context['promotion'] ?? null,
            'legacy' => $context['legacy'] ?? null,
        ];

        return hash_equals(
            hash('sha256', json_encode($normalize($left), JSON_THROW_ON_ERROR)),
            hash('sha256', json_encode($normalize($right), JSON_THROW_ON_ERROR)),
        );
    }

    private function packageWithPromotionContext(Package $package, array $context): Package
    {
        $snapshot = clone $package;
        foreach (($context['legacy'] ?? []) as $key => $value) {
            $snapshot->setAttribute($key, $value);
        }

        $data = $context['promotion'] ?? null;
        if (is_array($data)) {
            $promotion = new Promotion();
            $promotion->forceFill($data);
            $promotion->exists = true;
            $snapshot->setRelation('promotion', $promotion);
        } else {
            $snapshot->setRelation('promotion', null);
        }

        return $snapshot;
    }

    private function initialPaymentSubmissionHash(array $validated, int $branchId, array $details): string
    {
        $data = [
            'source' => 'intake', 'branch_id' => $branchId,
            'amount_cents' => (int) round((float) $validated['amount_paid'] * 100),
            'paid_at' => Carbon::parse($validated['paid_at'])->toDateTimeString(), 'details' => $details,
        ];
        if (isset($validated['receipt_or_no'])) {
            $data['receipt_or_no'] = $validated['receipt_or_no'];
        }
        if (isset($validated['payment_proof_id'])) {
            $data['payment_proof_id'] = $validated['payment_proof_id'];
        }

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

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

    public function saved(Request $request)
    {
        $saved = $request->session()->get('intake_saved_state');
        if ($saved) {
            foreach ($saved as $key => $value) {
                $request->session()->put($key, $value);
            }
        }
        $request->session()->keep(['intake_case_saved_toast']);
        if (!$request->session()->has('intake_payment.id')) {
            return redirect()->route('funeral-cases.index', ['record_scope' => 'main']);
        }
        $request->session()->keep(['intake_payment', 'summary', 'intake_saved_mode']);
        return view('staff.intake.saved');
    }

    public function scheduleAdvisory(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'funeral_service_at' => ['required_without:interment_at', 'nullable', 'date_format:Y-m-d'],
            'interment_at' => ['required_without:funeral_service_at', 'nullable', 'date_format:Y-m-d'],
        ]);
        $user = $request->user();
        abort_unless($user->canEncodeAnyBranch() || (int) $data['branch_id'] === (int) ($user->operationalBranchId() ?? $user->branch_id), 403);
        $dates = array_values(array_unique(array_filter([$data['funeral_service_at'] ?? null, $data['interment_at'] ?? null])));
        $cases = FuneralCase::where('branch_id', $data['branch_id'])
            ->with('hearseCatalog:id,name')
            ->whereIn('case_status', ['DRAFT', 'ACTIVE', 'COMPLETED'])
            ->where(function ($query) use ($dates) {
                $query->whereIn(DB::raw('DATE(funeral_service_at)'), $dates)
                    ->orWhereIn(DB::raw('DATE(interment_at)'), $dates);
            })->orderBy('case_code')->get();
        $events = [];
        foreach ($cases as $case) {
            foreach (['funeral_service_at' => ['funeral', 'Funeral ceremony', 'funeral_service_time'], 'interment_at' => ['interment', 'Interment', 'interment_time']] as $field => [$eventType, $label, $timeField]) {
                if (!$case->$field) continue;
                $date = Carbon::parse($case->$field);
                if (!in_array($date->toDateString(), $dates, true)) continue;
                $time = $case->$timeField ? Carbon::parse($case->$timeField) : null;
                $events[] = ['case_code' => $case->case_code, 'event_type' => $eventType, 'event' => $label,
                    'date' => $date->format('M d, Y'),
                    'date_key' => $date->toDateString(),
                    'time' => $time?->format('h:i A') ?? 'Time not recorded',
                    'start_time' => $time?->format('H:i'),
                    'assigned_hearse_id' => $case->hearse_catalog_id,
                    'assigned_hearse' => $case->hearseCatalog?->name,
                    'status' => $case->case_status];
            }
        }
        return response()->json(['case_count' => $cases->count(), 'branch_name' => Branch::find($data['branch_id'])?->branch_name, 'events' => $events]);
    }

    public function existingClients(Request $request)
    {
        $query = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q'];
        $branchId = (int) ($request->user()->operationalBranchId() ?? $request->user()->branch_id);
        if ($request->user()->canEncodeAnyBranch() && $request->filled('branch_id')) {
            $branchId = (int) $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']])['branch_id'];
        }
        abort_unless($branchId > 0, 403);

        $clients = Client::where('branch_id', $branchId);
        $matchedCase = null;
        if (preg_match('/\ACL-?(\d+)\z/i', $query, $directoryId)) {
            $clients->whereKey(ltrim($directoryId[1], '0'));
        } elseif (preg_match('/\A(?=.*[a-z])(?=.*\d)[a-z0-9_-]+\z/i', $query)) {
            $matchedCase = FuneralCase::where('branch_id', $branchId)
                ->where(fn ($cases) => $cases->where('entry_source', 'MAIN')->orWhereNull('entry_source'))
                ->whereRaw('LOWER(case_code) = LOWER(?)', [$query])
                ->first(['client_id', 'case_code']);
            if (!$matchedCase?->client_id) {
                return response()->json([]);
            }
            $clients->whereKey($matchedCase->client_id);
        } else {
            $term = '%'.strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $digits = preg_replace('/\D/', '', $query);
            $clients->where(function ($search) use ($term, $digits) {
                $search->whereRaw("LOWER(full_name) LIKE LOWER(?) ESCAPE '!'", [$term]);
                if (strlen($digits) >= 2) {
                    $search->orWhere('contact_number', 'like', '%'.$digits.'%');
                }
            });
        }

        return response()->json($clients->orderBy('full_name')->orderBy('id')->limit(10)->get()
            ->map(fn (Client $client) => $this->clientSelection($client) + ['matched_case_code' => $matchedCase?->case_code]));
    }

    private function clientSelection(Client $client): array
    {
        return $client->only(['id', 'full_name', 'contact_number', 'address'])
            + ['directory_id' => $client->directoryCode()] + $client->splitName();
    }

    public function createMain()
    {
        return $this->renderForm('main');
    }

    public function createHistorical()
    {
        $user = auth()->user();
        abort_unless($user && ($user->isStaff() || $user->isBranchAdmin() || $user->isSystemAdmin()), 403);
        return $this->renderForm('main', null, null, true);
    }

    public function storeHistorical(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->isStaff() || $user->isBranchAdmin() || $user->isSystemAdmin()), 403);
        $request->merge(['historical_mode' => true]);
        return $this->storeByMode($request, 'main');
    }

    public function createOther()
    {
        $user = auth()->user();
        if ($user?->isBranchAdmin()) {
            abort(403);
        }

        if (!$user?->isMainBranchAdmin()) {
            abort(403, 'Only a System Admin can record other-branch reports.');
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
            abort(403, 'Only a System Admin can record other-branch reports.');
        }

        return $this->storeByMode($request, 'other');
    }

    public function renderFormForDraft(string $mode, IntakeDraft $draft, ?string $returnTo = null)
    {
        return $this->renderForm($mode, $draft, $returnTo);
    }

    private function renderForm(string $mode, ?IntakeDraft $draft = null, ?string $returnTo = null, bool $historicalMode = false)
    {
        $user = auth()->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? $user->branch_id ?? 0);
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();

        $packages = app(\App\Support\ServiceCatalogPolicy::class)
            ->intakePackages(Package::query()->with(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageInclusions.addOnCatalog', 'packageFreebies.catalog', 'promotion']), allowCasketReplacement: true)
            ->orderBy('name')
            ->get();
        $promotionContextAt = now('Asia/Manila');
        $promotionContextTokens = $packages->mapWithKeys(fn (Package $package) => [
            $package->id => Crypt::encryptString(json_encode($this->promotionContext($package, $promotionContextAt), JSON_THROW_ON_ERROR)),
        ]);
        $activeAddOns = AddOnCatalog::where('is_active', true)->where('is_available', true)
            ->when(!$canEncodeAnyBranch, fn ($q) => $q->where(fn ($q) => $q->where('category', '<>', AddOnCatalog::CHAPEL_VIEWING)->orWhere('branch_id', $operationalBranchId)))
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        $addOnContextToken = Crypt::encryptString(json_encode($this->addOnContext($activeAddOns), JSON_THROW_ON_ERROR));
        $activeCaskets = CasketCatalog::where('is_active', true)
            ->orderBy('name')
            ->get();
        $activeFreebies = FreebieCatalog::where('is_active', true)->where('is_available', true)->orderBy('name')->get();
        $activeHearses = HearseCatalog::where('is_active', true)->where('is_available', true)->orderBy('name')->get();

        $branchQuery = Branch::where('is_active', true)->orderBy('branch_code');
        if ($mode === 'other') {
            $branchQuery->whereRaw('UPPER(branch_code) <> ?', ['BR001']);
        } elseif (!($historicalMode && $canEncodeAnyBranch)) {
            $branchQuery->where('id', $operationalBranchId);
        }

        $branches = $branchQuery->get();
        $draftFields = $draft?->payload['fields'] ?? [];
        $defaultBranchId = old('branch_id')
            ?: ($draftFields['branch_id'] ?? null)
            ?: ($branches->first()?->id ?? $operationalBranchId);
        $existingClientId = filter_var(old('existing_client_id', $draftFields['existing_client_id'] ?? null), FILTER_VALIDATE_INT);
        $clientLookupBranchId = $historicalMode && $canEncodeAnyBranch ? (int) $defaultBranchId : $operationalBranchId;
        $existingClient = $mode === 'main' && $existingClientId
            ? Client::where('branch_id', $clientLookupBranchId)->find($existingClientId) : null;

        return view('staff.intake.create', [
            'packages' => $packages,
            'promotionContextTokens' => $promotionContextTokens,
            'activeAddOns' => $activeAddOns,
            'addOnContextToken' => $addOnContextToken,
            'activeCaskets' => $activeCaskets,
            'activeFreebies' => $activeFreebies,
            'activeHearses' => $activeHearses,
            'branches' => $branches,
            'nextCode' => $this->nextCaseCode((int) $defaultBranchId),
            'nextCodeMap' => $branches->mapWithKeys(function ($branch) {
                return [$branch->id => $this->nextCaseCode((int) $branch->id)];
            }),
            'canEncodeAnyBranch' => $canEncodeAnyBranch,
            'entryMode' => $mode,
            'historicalMode' => $historicalMode,
            'intakeModal' => request()->boolean('intake_modal')
                && (request()->routeIs('intake.main.create')
                    || ($historicalMode && request()->routeIs('historical-intake.create'))
                    || ($mode === 'main' && $draft && $user->isStaff() && request()->routeIs('intake.drafts.edit'))),
            'defaultBranchId' => (int) $defaultBranchId,
            'formAction' => $mode === 'other'
                ? route('intake.other.store')
                : ($historicalMode ? route('historical-intake.store') : route('intake.main.store')),
            'clientRelationshipOptions' => self::CLIENT_RELATIONSHIP_OPTIONS,
            'existingClientSelection' => $existingClient ? $this->clientSelection($existingClient) : null,
            'seniorDiscountPercent' => (float) config('funeral.senior_discount_percent', 20),
            'intakeDraft' => $draft,
            'intakeDraftPayload' => $draft?->payload,
            'returnTo' => $returnTo,
        ]);
    }

    private function storeByMode(Request $request, string $mode)
    {
        $user = $request->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? $user->branch_id ?? 0);
        $selection = $request->validate(['existing_client_id' => [$mode === 'main' && $user->isStaff() ? 'nullable' : 'prohibited', 'integer', 'min:1']]);
        $existingClient = empty($selection['existing_client_id']) ? null
            : Client::where('branch_id', $operationalBranchId)->find($selection['existing_client_id']);
        if (! empty($selection['existing_client_id']) && ! $existingClient) {
            return back()->withErrors(['existing_client_id' => 'This client is unavailable in your branch. Choose a client again.'])->withInput();
        }
        $newClientOnly = Rule::excludeIf($existingClient !== null);

        $postedServiceRequestedAt = $request->input('service_requested_at');
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
        $hasPaymentDetails = $request->boolean('mark_as_paid') || $request->filled('payment_type') || $request->filled('amount_paid');
        if ($mode === 'main' && ! $request->filled('payment_method') && ! $hasPaymentDetails) {
            $request->merge(['payment_method' => 'none']);
        }
        if (in_array($request->input('payment_method'), ['cash', 'cashless'], true)) {
            $request->merge(['mark_as_paid' => 1]);
        }
        if ($request->input('payment_method') === 'none' && $hasPaymentDetails) {
            return back()->withErrors([
                'mark_as_paid' => 'Select Cash or Cashless to record a payment, or clear payment details when no payment was received.',
            ])->withInput();
        }
        $markAsPaid = $request->boolean('mark_as_paid');
        if ($request->filled('additional_service_amount') && ! $request->filled('additional_services')) {
            $request->merge(['additional_services' => 'Additional services']);
        }
        $historicalMode = $request->routeIs('historical-intake.store') && $request->boolean('historical_mode') && $mode === 'main';
        if ($historicalMode && is_array($request->input('historical_payments'))) {
            $request->merge(['historical_payments' => collect($request->input('historical_payments'))->filter(function ($row) {
                return is_array($row) && collect($row)->except(['payment_proof_form_id', 'payment_proof_id'])->contains(fn ($value) => is_string($value) ? trim($value) !== '' : filled($value));
            })->values()->all()]);
        }
        $serverRequestDate = $historicalMode
            ? (string) ($request->input('wake_start_date') ?: now()->toDateString())
            : now()->toDateString();
        $request->merge(['service_requested_at' => $serverRequestDate]);
        $this->mergeLegacyIntakeNameParts($request, 'client');
        $this->mergeLegacyIntakeNameParts($request, 'deceased');
        $this->mergeLegacyScheduleFields($request, $serverRequestDate);

        $wakeDateRules = ['required', 'date_format:Y-m-d'];
        if ($historicalMode) {
            $wakeDateRules[] = 'before_or_equal:today';
        }
        $scheduleTimeRules = ['required', 'date_format:H:i'];

        $validated = $request->validate(array_merge([
            // Server-owned date recorded value; user input is overwritten above.
            'service_requested_at' => 'required|date|before_or_equal:today',
            'is_backdated_entry' => 'nullable|boolean',
            'historical_mode' => 'nullable|boolean',
            'historical_entry_reason' => ['exclude_unless:historical_mode,1', 'required', 'string', 'min:10', 'max:1000'],
            'historical_parent_submission_id' => 'nullable|integer|exists:historical_case_submissions,id',
            'historical_primary_parent_payment_index' => ['exclude_unless:historical_mode,1', 'nullable', 'integer', 'min:0', 'max:19'],
            'historical_payments' => ['exclude_unless:historical_mode,1', 'nullable', 'array', 'max:20'],
            'historical_payments.*.amount' => ['exclude_unless:historical_mode,1', 'required', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'gt:0'],
            'historical_payments.*.paid_at' => ['exclude_unless:historical_mode,1', 'required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'historical_payments.*.payment_method' => ['exclude_unless:historical_mode,1', 'required', 'in:cash,cashless'],
            'historical_payments.*.receipt_or_no' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'min:3', 'max:50', 'regex:~^[A-Za-z0-9 /-]+$~'],
            'historical_payments.*.cashless_type' => ['exclude_unless:historical_mode,1', 'nullable', Rule::in(PaymentDetails::CASHLESS_TYPES)],
            'historical_payments.*.bank_name' => ['exclude_unless:historical_mode,1', 'nullable', Rule::in(PaymentDetails::BANKS)],
            'historical_payments.*.other_bank_name' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'min:2', 'max:80', 'regex:/^[A-Za-z0-9 .&()\-]+$/'],
            'historical_payments.*.account_name' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'max:100'],
            'historical_payments.*.mobile_number' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'regex:/^(09\d{9}|\+?639\d{9})$/'],
            'historical_payments.*.reference_number' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'min:4', 'max:60', 'regex:/^[A-Za-z0-9 _\/\-]+$/'],
            'historical_payments.*.approval_code' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'min:4', 'max:40', 'regex:/^[A-Za-z0-9_\/\-]+$/'],
            'historical_payments.*.card_type' => ['exclude_unless:historical_mode,1', 'nullable', Rule::in(PaymentDetails::CARD_TYPES)],
            'historical_payments.*.terminal_provider' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'max:80'],
            'historical_payments.*.payment_channel' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'max:80'],
            'historical_payments.*.payment_notes' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'max:255'],
            'historical_payments.*.proof_missing_reason' => ['exclude_unless:historical_mode,1', 'nullable', 'string', 'max:500'],
            'historical_payments.*.payment_proof_id' => ['exclude_unless:historical_mode,1', 'nullable', 'uuid'],
            'historical_payments.*.payment_proof_form_id' => ['exclude_unless:historical_mode,1', 'nullable', 'uuid'],
            'historical_payments.*.parent_payment_index' => ['exclude_unless:historical_mode,1', 'nullable', 'integer', 'min:0', 'max:19'],
            'backdated_entry_reason' => 'required_if:is_backdated_entry,1|nullable|string|max:500',
            'client_first_name'  => [$newClientOnly, ...FieldRules::namePart()],
            'client_last_name'   => [$newClientOnly, ...FieldRules::namePart()],
            'client_middle_name' => [$newClientOnly, ...FieldRules::namePart(false)],
            'client_suffix'      => [$newClientOnly, 'nullable', 'string', Rule::in(self::SUFFIX_OPTIONS)],
            'client_relationship' => ['required', 'string', 'max:100', Rule::in(self::CLIENT_RELATIONSHIP_OPTIONS)],
            'client_contact_number' => [$newClientOnly, 'required', 'string', 'regex:/^(09\d{9}|639\d{9})$/'],
            'client_email' => [$newClientOnly, 'nullable', 'email', 'max:255'],
            'client_valid_id_type' => [$newClientOnly, 'nullable', 'string', 'max:100'],
            'client_valid_id_number' => [$newClientOnly, 'nullable', 'string', 'max:100'],
            'client_address' => [$newClientOnly, 'required', 'string', 'max:255', $this->addressHasPlaceName()],

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
            'viewing_type' => ['sometimes', 'required', Rule::in(['home', 'chapel'])],
            'chapel_address' => ['exclude_unless:viewing_type,chapel', 'required', 'string', 'max:255', $this->addressHasPlaceName()],
            'viewing_type' => ['sometimes', 'required', Rule::in(['home', 'chapel'])],
            'wake_location' => ['required', 'string', 'max:255', $this->addressHasPlaceName()],
            'wake_start_date' => $wakeDateRules,
            'wake_start_time' => $scheduleTimeRules,
            'wake_end_date' => $wakeDateRules,
            'wake_end_time' => $scheduleTimeRules,
            'funeral_service_at' => $wakeDateRules,
            'funeral_service_time' => $scheduleTimeRules,
            ...CeremonyLocation::rules(),
            'interment_at' => $wakeDateRules,
            'interment_time' => $scheduleTimeRules,
            'wake_days' => 'nullable|integer|min:0|max:365',
            'place_of_cemetery' => ['required', 'string', 'max:255', $this->addressHasPlaceName()],
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
                function ($attribute, $value, $fail) use ($mode) {
                    if ((string) $value === 'custom') {
                        if ($mode !== 'main') {
                            $fail('Custom packages are only available for main-branch intake.');
                        }
                        return;
                    }
                    if (!is_numeric($value)) {
                        $fail('Please select a valid package.');
                        return;
                    }
                    $exists = app(\App\Support\ServiceCatalogPolicy::class)
                        ->intakePackages(Package::query()->whereKey((int) $value), allowCasketReplacement: true)
                        ->exists();
                    if (!$exists) {
                        $fail('This package is no longer available for new intake. Choose another package.');
                    }
                },
            ],
            'promotion_context_token' => ['nullable', 'string', 'max:10000'],
            'package_configuration_context' => ['nullable', 'string', 'size:64'],
            'custom_defaults_context' => ['nullable', 'string', 'max:3000'],
            'promotion_review_token' => ['nullable', 'string', 'max:10000'],
            'promotion_acknowledged' => ['nullable', 'boolean'],
            'add_on_context_token' => ['required_with:selected_add_ons,included_add_on_requested,chapel_add_on_id', 'string', 'max:10000'],
            'add_on_review_token' => ['nullable', 'string', 'max:10000'],
            'add_on_acknowledged' => ['nullable', 'boolean'],
            'custom_package_name' => 'exclude_unless:package_id,custom|required|string|max:150',
            'custom_package_price' => 'exclude_unless:package_id,custom|required|numeric|min:0.01',
            'custom_service_types' => 'exclude_unless:package_id,custom|nullable|array|max:5',
            'custom_service_types.*' => ['string', 'distinct', Rule::in([
                Package::SERVICE_BODY_RETRIEVAL, Package::SERVICE_EMBALMING, Package::SERVICE_CASKET,
                Package::SERVICE_HOME_VIEWING, Package::SERVICE_HEARSE,
            ])],
            'custom_service_coverage' => 'exclude_unless:package_id,custom|nullable|array',
            'custom_service_coverage.*' => 'nullable|numeric|min:0|max:100000',
            'custom_included' => 'exclude_unless:package_id,custom|nullable|array',
            'custom_included.*' => 'nullable|numeric|min:0|max:100000|decimal:0,2',
            'custom_requested' => 'exclude_unless:package_id,custom|nullable|array',
            'custom_requested.*' => 'nullable|numeric|min:0|max:100000|decimal:0,2',
            'custom_casket_catalog_id' => 'exclude_unless:package_id,custom|nullable|integer',
            'hearse_catalog_id' => 'nullable|integer',
            'custom_freebie_catalog_ids' => 'exclude_unless:package_id,custom|nullable|array|max:20',
            'custom_freebie_catalog_ids.*' => 'integer|distinct',
            'custom_other_inclusions' => 'exclude_unless:package_id,custom|nullable|string|max:1000',
            'additional_services' => 'nullable|string|max:1000',
            'additional_service_amount' => 'nullable|numeric|min:0',
            'additional_service_items' => 'nullable|array|max:20',
            'additional_service_items.*.description' => 'nullable|string|max:150',
            'additional_service_items.*.amount' => 'nullable|numeric|min:0',
            'selected_add_ons' => 'nullable|array',
            'selected_add_ons.*' => 'integer|distinct',
            'chapel_add_on_id' => 'exclude_unless:viewing_type,chapel|nullable|integer',
            'chapel_coverage_flow' => 'nullable|boolean',
            'chapel_coverage' => ['exclude_unless:viewing_type,chapel', Rule::requiredIf(fn () => $request->input('viewing_type') === 'chapel' && $request->boolean('chapel_coverage_flow')), 'nullable', Rule::in(['included', 'separate'])],
            'add_on_quantities' => 'nullable|array',
            'add_on_quantities.*' => 'required|integer|min:1',
            'included_add_on_requested' => 'nullable|array',
            'included_add_on_requested.*' => 'nullable|integer|min:1|max:9999',
            'package_allowance_context' => 'nullable|string|size:64',
            'replacement_casket_catalog_id' => 'nullable|integer|exists:casket_catalogs,id',
            'apply_retrieval_excess' => 'nullable|boolean',
            'retrieval_excess_kilometers' => 'nullable|numeric|min:0|max:10000',
            'apply_hearse_excess' => 'nullable|boolean',
            'hearse_excess_kilometers' => 'nullable|numeric|min:0|max:10000',
            'actual_retrieval_kilometers' => 'nullable|numeric|min:0|max:10000',
            'actual_hearse_kilometers' => 'nullable|numeric|min:0|max:10000',
            'reporter_name' => FieldRules::personName(false),
            'reporter_contact' => 'nullable|string|max:50|regex:/^[0-9]+$/',
            'reported_at' => 'nullable|date|before_or_equal:now',
            'tax_rate' => 'nullable|numeric|min:0|max:100',

            'mark_as_paid' => 'nullable|boolean',
            'payment_type' => 'required_if:mark_as_paid,1|in:FULL,PARTIAL',
            'payment_proof_id' => ['exclude_unless:mark_as_paid,1', $historicalMode ? 'nullable' : 'required', 'uuid'],
            'payment_proof_form_id' => ['exclude_unless:mark_as_paid,1', $historicalMode ? 'nullable' : 'required', 'uuid'],
            'payment_proof_missing_reason' => ['nullable', 'string', 'max:500'],
            'receipt_or_no' => ['exclude_unless:payment_method,cash', 'required_if:mark_as_paid,1', 'nullable', 'string', 'min:3', 'max:50', 'regex:~^[A-Za-z0-9 /-]+$~'],
            'paid_at' => ['exclude_unless:mark_as_paid,1', 'required_if:mark_as_paid,1', 'date_format:Y-m-d\TH:i,Y-m-d H:i:s', 'before_or_equal:now'],
            'amount_paid' => ['required_if:mark_as_paid,1', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'gt:0'],
            'confirm_review' => 'nullable|boolean',
            'submission_id' => ['nullable', 'uuid'],
            'intake_draft_id' => 'nullable|integer|exists:intake_drafts,id',
        ], PaymentDetails::rules(), [
            'payment_method' => ['required', 'in:cash,cashless,none'],
        ]), array_merge(PaymentDetails::messages(), [
            'payment_method.required' => 'Select Cash or Cashless to record a payment.',
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
            'chapel_coverage.required' => 'Choose a package with a chapel or select a branch chapel separately.',
            'reporter_contact.regex' => 'Reporter contact number must contain numbers only.',
            'born.required' => 'Date of birth is required.',
            'born.date_format' => 'Please enter a valid date of birth.',
            'born.before_or_equal' => 'Date of birth cannot be in the future.',
            'died.required' => 'Date of death is required.',
            'died.date_format' => 'Please enter a valid date of death.',
            'died.after_or_equal' => 'Date of death cannot be earlier than date of birth.',
            'died.before_or_equal' => 'Date of death cannot be in the future.',
            'wake_start_date.required' => 'Select a Wake Start date.',
            'wake_start_time.required' => 'Select a Wake Start time.',
            'wake_start_time.date_format' => 'Select a valid Wake Start time.',
            'wake_end_date.required' => 'Select a Wake End date.',
            'wake_end_time.required' => 'Select a Wake End time.',
            'wake_end_time.date_format' => 'Select a valid Wake End time.',
            'funeral_service_at.required' => 'Select a Funeral Ceremony date.',
            'funeral_service_time.required' => 'Select a Funeral Ceremony time.',
            'funeral_service_time.date_format' => 'Select a valid Funeral Ceremony time.',
            'interment_at.required' => 'Select a Interment date.',
            'interment_time.required' => 'Select a Interment time.',
            'interment_time.date_format' => 'Select a valid Interment time.',
            'backdated_entry_reason.required_if' => 'Please provide a reason for a backdated request entry.',
            'historical_entry_reason.required' => 'Explain why this completed case is being recorded as a historical entry.',
            'historical_entry_reason.min' => 'Provide a little more detail about why this case is being recorded late.',
            'paid_at.required_if' => 'Please select the actual payment received date and time.',
            'paid_at.date_format' => 'Please enter a valid payment received date and time.',
            'paid_at.before_or_equal' => 'Payment received date/time cannot be in the future.',
            'reported_at.before_or_equal' => 'Reported date/time cannot be in the future.',
            'confirm_review' => 'You must confirm that the information is correct before saving.',
            'additional_service_amount.numeric' => 'Additional charges must be a valid amount.',
            'additional_service_amount.min' => 'Additional charges cannot be negative.',
            'custom_package_name.required' => 'Enter a name for the custom package.',
            'custom_package_price.required' => 'Enter the agreed package price.',
            'custom_package_price.min' => 'The custom package price must be greater than zero.',
            'custom_package_inclusions.required' => 'List at least one service or item included in the custom package.',
            'additional_service_items.*.description.max' => 'Additional service description is too long.',
            'additional_service_items.*.amount.numeric' => 'Additional service amount must be valid.',
            'additional_service_items.*.amount.min' => 'Additional service amount cannot be negative.',
            'selected_add_ons.*.integer' => 'Selected add-on is invalid.',
            'selected_add_ons.*.distinct' => 'Duplicate add-ons are not allowed.',
            'add_on_quantities.*.required' => 'Enter a quantity for each selected add-on.',
            'add_on_quantities.*.integer' => 'Add-on quantity must be a whole number.',
            'add_on_quantities.*.min' => 'Add-on quantity must be at least 1.',
            'replacement_casket_catalog_id.exists' => 'Selected casket is unavailable.',
            'hearse_catalog_id.integer' => 'Selected hearse is invalid.',
            'retrieval_excess_kilometers.numeric' => 'Retrieval excess kilometers must be a valid number.',
            'hearse_excess_kilometers.numeric' => 'Hearse excess kilometers must be a valid number.',
            'actual_retrieval_kilometers.numeric' => 'Retrieval kilometers must be a valid number.',
            'actual_hearse_kilometers.numeric' => 'Hearse kilometers must be a valid number.',
        ]));
        $paymentDetails = PaymentDetails::normalize($request);
        if ($historicalMode && ($validated['senior_citizen_status'] ?? false) && !$request->hasFile('senior_proof')) {
            return back()->withErrors(['senior_proof' => 'Attach the senior citizen ID/certificate proof when recording a historical case with the senior discount.'])->withInput();
        }
        $additionalHistoricalPayments = [];
        if ($historicalMode) {
            foreach (($validated['historical_payments'] ?? []) as $index => $row) {
                $details = PaymentDetails::normalize($row);
                $detailErrors = PaymentDetails::validateNormalized($details);
                if (($row['payment_method'] ?? null) === 'cash' && blank($row['receipt_or_no'] ?? null)) {
                    $detailErrors['receipt_or_no'] = 'Enter the Receipt / OR number for this cash payment.';
                }
                foreach ($detailErrors as $field => $message) {
                    return back()->withErrors(["historical_payments.$index.$field" => $message])->withInput();
                }
                if (($row['payment_method'] ?? null) === 'cashless' && blank($details['reference_number']) && blank($details['approval_code'])) {
                    return back()->withErrors(["historical_payments.$index.reference_number" => 'Enter a transaction reference or approval code.'])->withInput();
                }
                $additionalHistoricalPayments[] = ['index' => $index, 'row' => $row, 'details' => $details];
            }
        }
        if ($historicalMode && $request->boolean('mark_as_paid') && !$request->filled('payment_proof_id') && blank($validated['payment_proof_missing_reason'] ?? null) && !$request->filled('historical_parent_submission_id')) {
            return back()->withErrors(['payment_proof_missing_reason' => 'Explain why the historical payment proof is unavailable.'])->withInput();
        }
        if ($request->boolean('mark_as_paid')) {
            $paymentDetailErrors = PaymentDetails::validateNormalized($paymentDetails);
            if ($paymentDetailErrors !== []) {
                return back()->withErrors($paymentDetailErrors)->withInput();
            }
        }
        $sourceDraft = null;
        if (! empty($validated['intake_draft_id'])) {
            $sourceDraft = IntakeDraft::whereKey($validated['intake_draft_id'])
                ->where('created_by', $user->id)
                ->where('status', IntakeDraft::STATUS_IN_PROGRESS)
                ->firstOrFail();

            if (! in_array((int) $sourceDraft->branch_id, $user->branchScopeIds(), true)) {
                abort(403);
            }
        }
        $validated['service_type'] = 'Burial';
        $validated = $this->normalizeIntakeNameParts($validated);
        if (! $historicalMode && ! $existingClient && ($response = $this->rejectDuplicateIntakeNameParts($validated, 'client'))) {
            return $response;
        }
        if (! $historicalMode && ($response = $this->rejectDuplicateIntakeNameParts($validated, 'deceased'))) {
            return $response;
        }

        foreach (($validated['additional_service_items'] ?? []) as $index => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $amount = round((float) ($item['amount'] ?? 0), 2);
            if ($amount > 0 && $description === '') {
                return back()->withErrors([
                    "additional_service_items.$index.description" => 'Description is required for this additional charge.',
                ])->withInput();
            }
            if ($description !== '' && $amount <= 0) {
                return back()->withErrors([
                    "additional_service_items.$index.amount" => 'Enter an amount greater than zero for this line.',
                ])->withInput();
            }
        }

        if (
            ! $historicalMode &&
            !($validated['is_backdated_entry'] ?? false)
            && Carbon::parse($validated['service_requested_at'])->lt(today())
        ) {
            return back()->withErrors([
                'is_backdated_entry' => 'Backdated request entries must be clearly marked.',
                'backdated_entry_reason' => 'Please provide a reason for a backdated request entry.',
            ])->withInput();
        }

        $schedule = $this->resolveScheduleDatetimes($validated);
        $scheduleErrors = [];
        if (!$historicalMode && $schedule['request_date']->gt($schedule['wake_start']->copy()->startOfDay())) {
            $scheduleErrors['wake_start_date'] = 'Wake start date cannot be before the request/recorded date.';
        }

        if ($schedule['funeral_service']->copy()->startOfDay()->lt(Carbon::parse($validated['died'])->startOfDay())) {
            $scheduleErrors['funeral_service_at'] = 'Funeral Ceremony date must be on or after the date of death.';
        }

        if ($schedule['funeral_service']->lt($schedule['wake_start'])) {
            $field = $schedule['funeral_service']->isSameDay($schedule['wake_start']) ? 'funeral_service_time' : 'funeral_service_at';
            $scheduleErrors[$field] = $field === 'funeral_service_time'
                ? 'Funeral Ceremony time cannot be before Wake Start time.'
                : 'Funeral Ceremony date cannot be before Wake Start date.';
        }

        if ($schedule['wake_end']->lessThanOrEqualTo($schedule['wake_start'])) {
            if ($schedule['wake_end']->isSameDay($schedule['wake_start'])) {
                $scheduleErrors['wake_end_time'] = 'Wake End time must be later than Wake Start time.';
            } else {
                $scheduleErrors['wake_end_date'] = 'Wake End date cannot be before Wake Start date.';
            }
        }

        if ($schedule['funeral_service']->lt($schedule['wake_end'])) {
            $field = $schedule['funeral_service']->isSameDay($schedule['wake_end']) ? 'funeral_service_time' : 'funeral_service_at';
            $scheduleErrors[$field] = $field === 'funeral_service_time'
                ? 'Funeral Ceremony time cannot be before Wake End time.'
                : 'Funeral Ceremony date cannot be before Wake End date.';
        }

        if ($schedule['interment']->copy()->startOfDay()->lt($schedule['funeral_service']->copy()->startOfDay())) {
            $scheduleErrors['interment_at'] = 'Interment date cannot be before the Funeral Ceremony date.';
        }

        if (
            $schedule['interment']->isSameDay($schedule['funeral_service'])
            && $schedule['interment']->lessThanOrEqualTo($schedule['funeral_service'])
        ) {
            $scheduleErrors['interment_time'] = 'Interment time must be later than the Funeral Ceremony time.';
        }

        if (!$this->isIntermentAfterDeathDate($validated['died'] ?? null, $schedule['interment']->toDateTimeString())) {
            $scheduleErrors['interment_at'] = 'Interment date must be on or after the date of death.';
        }

        if ($historicalMode && $schedule['interment']->gt(now())) {
            $scheduleErrors['interment_at'] = 'Historical entries are only for services that have already taken place.';
        }

        if ($schedule['wake_start']->copy()->startOfDay()->lt(Carbon::parse($validated['died'])->startOfDay())) {
            $scheduleErrors['wake_start_date'] = 'Wake start date must be on or after the date of death.';
        }
        if ($scheduleErrors) {
            return back()->withErrors($scheduleErrors)->withInput();
        }

        $computedWakeDays = $this->resolveWakeDays(
            null,
            $validated['wake_start_date'] ?? null,
            $validated['wake_end_date'] ?? null
        );
        if ($computedWakeDays === null) {
            return back()->withErrors([
                'wake_days' => 'Wake Days could not be calculated. Please check the Wake Start and Wake End dates.',
            ])->withInput();
        }
        $validated['wake_days'] = $computedWakeDays;

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
            $targetBranchId = $historicalMode ? (int) $validated['branch_id'] : $operationalBranchId;
            if ($historicalMode && !$user->canEncodeAnyBranch()) {
                abort_unless($targetBranchId === $operationalBranchId, 403);
            }
            $mainBranch = Branch::where('is_active', true)->whereKey($targetBranchId)->first();

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
            $package = app(\App\Support\ServiceCatalogPolicy::class)
                ->intakePackages(Package::query()->with(['packageInclusions.casketCatalog', 'packageInclusions.hearseCatalog', 'packageInclusions.addOnCatalog', 'packageFreebies.catalog', 'promotion'])
                    ->whereKey($selectedPackageId), allowCasketReplacement: true)
                ->first();

            if (!$package) {
                return back()->withErrors(['package_id' => 'This package is no longer available for new intake. Choose another package.'])->withInput();
            }
            if (filled($validated['package_configuration_context'] ?? null)
                && ! hash_equals($package->intakeConfigurationFingerprint(), $validated['package_configuration_context'])) {
                return back()->withErrors(['package_id' => 'The selected package changed. Review the current inclusions and charges before saving.'])->withInput();
            }
        }
        if ($isCustomPackage && filled($validated['custom_defaults_context'] ?? null)) {
            $defaults = \App\Models\CustomPackageDefault::find(1);
            $currentDefaults = ['coverage' => $defaults?->coverage() ?? [], 'rates' => $defaults?->rates() ?? []];
            try {
                $shownDefaults = json_decode(Crypt::decryptString($validated['custom_defaults_context']), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Illuminate\Contracts\Encryption\DecryptException | \JsonException) {
                $shownDefaults = null;
            }
            if (json_encode($shownDefaults, JSON_THROW_ON_ERROR) !== json_encode($currentDefaults, JSON_THROW_ON_ERROR)) {
                $input = $request->all();
                foreach ($validated['custom_included'] ?? [] as $type => $quantity) {
                    $shownCoverage = $shownDefaults['coverage'][$type] ?? null;
                    if ($shownCoverage !== null && $quantity !== null && (float) $quantity === (float) $shownCoverage) {
                        unset($input['custom_included'][$type]);
                    }
                }
                return back()->withErrors(['package_id' => 'Basic Funeral Inclusions changed. Review the current coverage and charges before saving.'])->withInput($input);
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

        $clientFullName = $existingClient ? (string) $existingClient->full_name : Client::buildFullName(
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
        $normalizedClientAddress  = strtolower(trim((string) ($validated['client_address'] ?? '')));
        $normalizedDeceasedName   = strtolower(trim($deceasedFullName));
        $normalizedDeceasedAddress = strtolower(trim((string) $validated['deceased_address']));
        $clientDuplicateKey = $request->filled('client_name') ? 'client_name' : 'client_first_name';
        $deceasedDuplicateKey = $request->filled('deceased_name') ? 'deceased_name' : 'deceased_first_name';

        if ($request->boolean('mark_as_paid') && !empty($validated['submission_id'])) {
            $savedPayment = Payment::withTrashed()->where('branch_id', $branchId)
                ->where('submission_id', $validated['submission_id'])->first();
            if ($savedPayment) {
                $savedCase = FuneralCase::with(['client', 'deceased'])->where('branch_id', $branchId)
                    ->find($savedPayment->funeral_case_id);
                $same = $savedCase && $savedCase->entry_source === $entrySource
                    && (! $existingClient || ((int) $savedCase->client_id === (int) $existingClient->id && $savedCase->clientRelationship() === $validated['client_relationship']))
                    && strtolower(trim((string) $savedCase->client?->full_name)) === $normalizedClientName
                    && strtolower(trim((string) $savedCase->deceased?->full_name)) === $normalizedDeceasedName
                    && hash_equals((string) $savedPayment->submission_hash,
                        $this->initialPaymentSubmissionHash($validated, $branchId, $paymentDetails));
                if (!$same) {
                    return back()->withErrors(['submission_id' => 'This submission was already saved with different details. Review Case Records before starting another intake.'])->withInput();
                }
                $this->authorize('view', $savedCase);
                $response = redirect()->route('funeral-cases.show', $savedCase)
                    ->with('success', 'Recovered existing case '.$savedCase->case_code.'. This retry did not apply changes or create another case or payment. Review the saved details and payment history.');
                return $this->intakeSaveResponse($request, $response);
            }
        }

        // A retry with the same submission must be recovered above before its
        // already-saved receipt/reference is treated as a duplicate. New
        // submissions still receive the normal branch-scoped uniqueness check.
        if ($markAsPaid) {
            $identifierChecks = [];
            $receiptOrNo = trim((string) ($validated['receipt_or_no'] ?? ''));
            if ($receiptOrNo !== '') $identifierChecks[] = ['receipt_or_no', $receiptOrNo];
            if (($paymentDetails['payment_method'] ?? null) === 'cashless') {
                foreach (['reference_number', 'approval_code'] as $identifierType) {
                    $value = trim((string) ($paymentDetails[$identifierType] ?? ''));
                    if ($value !== '') $identifierChecks[] = [$identifierType, $value];
                }
            }
            foreach ($identifierChecks as [$identifierType, $value]) {
                $duplicate = PaymentIdentifier::duplicate(
                    $branchId,
                    $identifierType,
                    $value,
                    $paymentDetails['cashless_type'] ?? null,
                    PaymentIdentifier::providerFor($paymentDetails),
                );
                if ($duplicate) {
                    return back()->withErrors([
                        $identifierType => PaymentIdentifier::duplicateMessage($identifierType, $duplicate),
                    ])->withInput();
                }
            }
        }

        if (! $historicalMode && $normalizedClientName === $normalizedDeceasedName) {
            return back()->withErrors([
                $deceasedDuplicateKey => 'Client and deceased names cannot be exactly the same. Please verify the entered information.',
            ])->withInput();
        }

        $duplicateClient = ! $historicalMode && ! $existingClient && Client::query()
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedClientName])
            ->whereRaw('COALESCE(contact_number, "") = ?', [$normalizedClientContact])
            ->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedClientAddress])
            ->exists();

        if ($duplicateClient) {
            return back()->withErrors([
                $clientDuplicateKey => $mode === 'main'
                    ? 'Duplicate client detected. Use Find Existing Client to link the new case to the saved profile.'
                    : 'Duplicate client detected (same name, contact number, and address).',
            ])->withInput();
        }

        $duplicateDeceasedQuery = Deceased::query()
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedDeceasedName])
            ->whereDate('died', $validated['died'])
            ->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedDeceasedAddress]);

        if (!$historicalMode && $duplicateDeceasedQuery->exists()) {
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

        if (!$historicalMode && $duplicateActiveCaseQuery->exists()) {
            return back()->withErrors([
                $deceasedDuplicateKey => 'An active case already exists for this deceased record.',
            ])->withInput();
        }

        if ($request->has('confirm_review') && !$request->boolean('confirm_review')) {
            return back()->withErrors([
                'confirm_review' => 'You must confirm that the information is correct before saving.',
            ])->withInput();
        }

        $scheduleConflictCount = $historicalMode ? 0 : FuneralCase::where('branch_id', $branchId)
            ->whereIn('case_status', ['DRAFT', 'ACTIVE', 'COMPLETED'])
            ->where(function ($query) use ($validated) {
                $funeralDate = Carbon::parse($validated['funeral_service_at'])->toDateString();
                $intermentDate = Carbon::parse($validated['interment_at'])->toDateString();
                $dates = array_unique([$funeralDate, $intermentDate]);
                $query->whereIn(DB::raw('DATE(funeral_service_at)'), $dates)
                    ->orWhereIn(DB::raw('DATE(interment_at)'), $dates);
            })
            ->count();

        $servicePackageName = $isCustomPackage
            ? ($validated['custom_package_name'] ?? 'Client Preference')
            : $package->name;

        $includesHearse = $isCustomPackage
            ? in_array(Package::SERVICE_HEARSE, $validated['custom_service_types'] ?? [], true)
            : $package->packageInclusions->contains(fn ($row) => Package::normalizeServiceType($row->service_type) === Package::SERVICE_HEARSE)
                || collect($package->inclusionNames())->contains(fn ($name) => Package::normalizeServiceType((string) $name) === Package::SERVICE_HEARSE);
        $selectedHearse = null;
        if ($includesHearse) {
            $selectedHearse = HearseCatalog::query()
                ->whereKey($isCustomPackage ? (int) ($validated['hearse_catalog_id'] ?? 0) : (int) (($validated['hearse_catalog_id'] ?? null) ?: $package->packageInclusions->firstWhere('service_type', Package::SERVICE_HEARSE)?->hearse_catalog_id))
                ->where('is_active', true)->where('is_available', true)
                ->first();
            if (! $selectedHearse) {
                return back()->withErrors(['hearse_catalog_id' => 'Choose an active hearse from the catalog.'])->withInput();
            }
            $validated['hearse_catalog_id'] = $selectedHearse->id;
            $validated['selected_hearse'] = [
                'id' => $selectedHearse->id,
                'name' => $selectedHearse->name,
                'description' => $selectedHearse->description,
                'reference_value' => $selectedHearse->reference_value !== null ? (float) $selectedHearse->reference_value : null,
            ];
        } else {
            // A forged or stale selection cannot become an active assignment.
            $validated['hearse_catalog_id'] = null;
            $validated['selected_hearse'] = null;
        }
        if ($isCustomPackage) {
            $validated['custom_package_configuration'] = app(CustomPackageSelection::class)->resolve($validated);
            $validated['custom_package_inclusions'] = $validated['custom_package_configuration']['inclusions_text'];
            $validated['custom_package_freebies'] = $validated['custom_package_configuration']['freebies_text'];
        }
        $pricingService = app(IntakePricingService::class);
        $chapelAllowances = $package?->packageInclusions->filter(fn ($row) => $row->addOnCatalog?->category === AddOnCatalog::CHAPEL_VIEWING
            && (int) $row->addOnCatalog?->branch_id === (int) $validated['branch_id'])->keyBy('add_on_catalog_id') ?? collect();
        if (AddOnCatalog::where('category', AddOnCatalog::CHAPEL_VIEWING)->whereIn('id', $validated['selected_add_ons'] ?? [])->exists()
            || AddOnCatalog::where('category', AddOnCatalog::CHAPEL_VIEWING)->whereIn('id', array_keys($validated['included_add_on_requested'] ?? []))->exists()) {
            return back()->withErrors(['chapel_add_on_id' => 'Use the Chapel Viewing charge to apply chapel fees.'])->withInput();
        }
        if (($validated['viewing_type'] ?? null) === 'chapel') {
            if ($chapelAllowances->isEmpty() && $package?->packageInclusions->contains(fn ($row) => $row->addOnCatalog?->category === AddOnCatalog::CHAPEL_VIEWING && !$row->addOnCatalog?->branch_id)) {
                return back()->withErrors(['chapel_add_on_id' => 'This package has a chapel inclusion without an assigned branch. Ask the System Admin to complete its setup, choose another package or viewing type, or save as Draft.'])->withInput();
            }
            $chapelDays = WakeDuration::days($validated['wake_start_date'], $validated['wake_end_date']);
            $postedChapelId = $validated['chapel_add_on_id'] ?? null;
            $allowance = $chapelAllowances->first();
            $coverage = $validated['chapel_coverage'] ?? null;
            if ($coverage === 'included' && !$allowance) {
                return back()->withErrors(['package_id' => 'Choose a package with chapel coverage at this branch.'])->withInput();
            }
            if ($coverage === 'separate' && $allowance) {
                return back()->withErrors(['package_id' => 'Choose a package without chapel coverage at this branch for the separate rate.'])->withInput();
            }
            $chapelId = $allowance?->add_on_catalog_id ?? $postedChapelId;
            if (! $chapelId) {
                return back()->withErrors(['chapel_add_on_id' => 'Chapel Viewing is not included in this package. Choose an available chapel before continuing, or save as Draft.'])->withInput();
            }
            $chapel = AddOnCatalog::where('category', AddOnCatalog::CHAPEL_VIEWING)->where('unit', 'day')
                ->where('is_active', true)->where('is_available', true)->whereNotNull('price')->where('price', '>=', 0)
                ->where('branch_id', $validated['branch_id'])->whereNotNull('chapel_address')->where('chapel_address', '<>', '')
                ->whereKey($chapelId)->first();
            if (! $chapel || ($postedChapelId && (int) $postedChapelId !== (int) $chapel->id)) {
                return back()->withErrors(['chapel_add_on_id' => $allowance
                    ? 'The chapel included in this package is unavailable. Save as Draft until it is resolved.'
                    : 'No usable chapel rate was selected. Save as Draft until the rate is resolved.'])->withInput();
            }
            if (! $chapelDays || blank($validated['add_on_context_token'] ?? null)) {
                return back()->withErrors(['chapel_add_on_id' => 'Review the wake schedule and chapel charge before saving.'])->withInput();
            }
            $validated['chapel_catalog_id'] = $chapel->id;
            $validated['chapel_priced_rate'] = $chapel->price;
            $validated['chapel_priced_days'] = $allowance?->included_quantity;
            $validated['wake_location'] = $chapel->name;
            $validated['chapel_address'] = $chapel->chapel_address;
            $chapelContext = app(\App\Support\ChapelSchedule::class)->context($chapel,
                $validated['wake_start_date'].' '.$validated['wake_start_time'], $validated['wake_end_date'].' '.$validated['wake_end_time']);
            if ($chapelContext['events'] && !$historicalMode) {
                return back()->withErrors(['chapel_add_on_id' => \App\Support\ChapelSchedule::conflictMessage($chapelContext['events'][0])])->withInput();
            }
            if ($allowance) {
                $validated['included_add_on_requested'][$chapel->id] = $chapelDays;
            } else {
                $validated['selected_add_ons'][] = $chapel->id;
                $validated['add_on_quantities'][$chapel->id] = $chapelDays;
            }
        }
        if ($package && array_filter($validated['included_add_on_requested'] ?? [])
            && ! hash_equals($package->addOnAllowanceFingerprint(), $validated['package_allowance_context'] ?? '')) {
            return back()->withErrors(['included_add_on_requested' => 'The package allowances changed. Review the included quantities before saving.'])->withInput();
        }
        $selectionErrors = $pricingService->validateSelections($package, $validated, $isCustomPackage);
        if ($selectionErrors !== []) {
            return back()->withErrors($selectionErrors)->withInput();
        }

        $pricingReferenceAt = now('Asia/Manila');
        $pricing = $pricingService->price($package, $validated, $isCustomPackage, $pricingReferenceAt);

        $includedExtras = collect($pricing['snapshot']['service_charges'])->where('type', 'included_add_on_extra')->pluck('excess', 'add_on_catalog_id');
        $selectedAddOnIds = collect($validated['selected_add_ons'] ?? [])->map(fn ($id) => (int) $id)->merge($includedExtras->keys())->unique()->values();
        if ($selectedAddOnIds->isNotEmpty() && filled($validated['add_on_context_token'] ?? null)) {
            try {
                $initialAddOnContext = json_decode(Crypt::decryptString($validated['add_on_context_token']), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                return back()->withErrors(['selected_add_ons' => 'The add-on price review expired. Reopen intake and review the selected add-ons again.'])->withInput();
            }

            $selectedKeys = $selectedAddOnIds->map(fn ($id) => (string) $id)->all();
            $initialSelectedAddOns = ['items' => collect($initialAddOnContext['items'] ?? [])->only($selectedKeys)->all()];
            $currentSelectedAddOns = $this->addOnContext(
                AddOnCatalog::whereIn('id', $selectedAddOnIds)->get(),
                $selectedAddOnIds->all(),
            );
            $addOnPricingChanged = ! $this->addOnContextMatches($initialSelectedAddOns, $currentSelectedAddOns);

            $acknowledgedAddOnContext = null;
            if (filled($validated['add_on_review_token'] ?? null)) {
                try {
                    $acknowledgedAddOnContext = json_decode(Crypt::decryptString($validated['add_on_review_token']), true, 512, JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                    $acknowledgedAddOnContext = null;
                }
            }

            $reviewTokenMatchesCurrent = is_array($acknowledgedAddOnContext)
                && $this->addOnContextMatches($acknowledgedAddOnContext, $currentSelectedAddOns);
            $reviewWasSubmitted = filled($validated['add_on_review_token'] ?? null);
            $reviewAccepted = $reviewWasSubmitted && $reviewTokenMatchesCurrent && $request->boolean('add_on_acknowledged');

            if (($addOnPricingChanged || $reviewWasSubmitted) && ! $reviewAccepted) {
                $review = $this->makeAddOnReview(
                    $initialSelectedAddOns,
                    $currentSelectedAddOns,
                    $includedExtras->all() + (array) ($validated['add_on_quantities'] ?? []),
                );

                if ($reviewWasSubmitted && $reviewTokenMatchesCurrent && ! $request->boolean('add_on_acknowledged')) {
                    return back()->withErrors(['add_on_acknowledged' => 'Review and accept the current add-on prices before saving.'])
                        ->withInput()
                        ->with('add_on_review', $review);
                }

                return back()->withInput()->with('add_on_review', $review);
            }
        }
        $coffinType = $isCustomPackage
            ? ($pricing['included_casket']['name'] ?? 'CUSTOM')
            : ($pricing['selected_casket']['name'] ?? $pricing['included_casket']['name'] ?? $package->coffin_type);
        $packagePrice = $pricing['base_price'];
        $additionalServiceAmount = $pricing['additional_total'];
        if ($additionalServiceAmount > 0 && trim((string) ($validated['additional_services'] ?? '')) === '') {
            $hasItemizedDescription = collect($validated['additional_service_items'] ?? [])
                ->contains(fn ($item) => trim((string) ($item['description'] ?? '')) !== '');
            if (! $hasItemizedDescription) {
                return back()->withErrors([
                    'additional_services' => 'Description of extras is required when additional charges are entered.',
                ])->withInput();
            }
        }

        $selectedAddOns = $pricing['selected_add_ons'];
        $addOnsTotal = $pricing['add_ons_total'];
        $subtotal = $pricing['subtotal'];

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

        $pricingReferenceAt = now('Asia/Manila');
        $pricing = $pricingService->price($package, $validated, $isCustomPackage, $pricingReferenceAt);
        $coffinType = $isCustomPackage
            ? ($pricing['included_casket']['name'] ?? 'CUSTOM')
            : ($pricing['selected_casket']['name'] ?? $pricing['included_casket']['name'] ?? $package->coffin_type);
        $packagePrice = $pricing['base_price'];
        $additionalServiceAmount = $pricing['additional_total'];
        $selectedAddOns = $pricing['selected_add_ons'];
        $addOnsTotal = $pricing['add_ons_total'];
        $subtotal = $pricing['subtotal'];

        if (! $isCustomPackage && filled($validated['promotion_context_token'] ?? null)) {
            try {
                $initialPromotionContext = json_decode(Crypt::decryptString($validated['promotion_context_token']), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                return back()->withErrors(['package_id' => 'The package promotion review expired. Reopen intake and review the package again.'])->withInput();
            }

            if ((int) ($initialPromotionContext['package_id'] ?? 0) !== (int) $package->id) {
                return back()->withErrors(['package_id' => 'The selected package changed. Review its promotion before continuing.'])->withInput();
            }

            $currentPromotionContext = $this->promotionContext($package, $pricingReferenceAt);
            if (! $this->promotionContextMatches($initialPromotionContext, $currentPromotionContext)) {
                $acknowledgedContext = null;
                if ($request->boolean('promotion_acknowledged') && filled($validated['promotion_review_token'] ?? null)) {
                    try {
                        $acknowledgedContext = json_decode(Crypt::decryptString($validated['promotion_review_token']), true, 512, JSON_THROW_ON_ERROR);
                    } catch (\Throwable) {
                        $acknowledgedContext = null;
                    }
                }

                if (! is_array($acknowledgedContext) || ! $this->promotionContextMatches($acknowledgedContext, $currentPromotionContext)) {
                    $snapshotAt = Carbon::parse($initialPromotionContext['as_of'] ?? $pricingReferenceAt)->timezone('Asia/Manila');
                    $previousPackage = $this->packageWithPromotionContext($package, $initialPromotionContext);
                    $previousPricing = $pricingService->price($previousPackage, $validated, false, $snapshotAt);

                    return back()->withInput()->with('promotion_review', [
                        'package_id' => (int) $package->id,
                        'package_name' => $package->name,
                        'previous_status' => $initialPromotionContext['status'] ?? 'Unknown',
                        'current_status' => $currentPromotionContext['status'] ?? 'Unknown',
                        'previous_package_amount' => (float) $previousPricing['total'],
                        'current_package_amount' => (float) $pricing['total'],
                        'review_token' => Crypt::encryptString(json_encode($currentPromotionContext, JSON_THROW_ON_ERROR)),
                    ]);
                }
            }
        }

        $wakeDays = $this->resolveWakeDays(
            $validated['wake_days'] ?? null,
            $validated['wake_start_date'] ?? null,
            $validated['interment_at'] ?? null
        );

        $discountPayload = $pricing['discount'];
        if (!empty($discountPayload['error_field'])) {
            return back()->withErrors([
                $discountPayload['error_field'] => $discountPayload['error_message'],
            ])->withInput();
        }
        $discountAmount = $pricing['discount_amount'];
        $taxRate = $pricing['tax_rate'];
        $taxAmount = $pricing['tax_amount'];
        $total = $pricing['total'];

        $allowOverpayment = (bool) config('funeral.allow_overpayment', false);
        $photoPath = null;
        $seniorProofPath = null;
        $photoFileName = $request->file('deceased_photo')?->getClientOriginalName();
        $seniorProofFileName = $request->file('senior_proof')?->getClientOriginalName();
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
                    'amount_paid' => 'Amount cannot exceed the remaining balance of ₱' . number_format($total, 2) . '.',
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

        if ($historicalMode) {
            $parentSubmission = null;
            if (!empty($validated['historical_parent_submission_id'])) {
                $parentSubmission = HistoricalCaseSubmission::whereKey($validated['historical_parent_submission_id'])
                    ->where('encoded_by', $user->id)->where('branch_id', $branchId)
                    ->whereIn('status', [HistoricalCaseSubmission::NEEDS_CHANGES, HistoricalCaseSubmission::REJECTED])->first();
                if (!$parentSubmission) {
                    return back()->withErrors(['historical_parent_submission_id' => 'This historical submission cannot be resubmitted from your account.'])->withInput();
                }
            }
            $historicalPaymentRows = [];
            $copyHistoricalProof = function (?string $uploadId, ?string $formId, string $errorKey, ?string $missingReason, ?int $parentIndex = null) use ($request, $user, $parentSubmission) {
                if (blank($uploadId)) {
                    $priorPayments = $parentSubmission?->submission_data['historical_payments'] ?? [];
                    $priorProof = $parentIndex !== null ? ($priorPayments[$parentIndex]['proof'] ?? null) : null;
                    if (is_array($priorProof) && !empty($priorProof['path']) && \Illuminate\Support\Facades\Storage::disk('local')->exists($priorProof['path'])) {
                        return $priorProof;
                    }
                    if (blank($missingReason)) {
                        throw ValidationException::withMessages([$errorKey => 'Explain why this historical payment proof is unavailable.']);
                    }
                    return null;
                }
                $upload = PaymentProofUpload::whereKey($uploadId)->first();
                if (!$upload || (int) $upload->user_id !== (int) $user->id
                    || !hash_equals($upload->session_hash, \App\Support\Payments\PaymentProof::sessionHash($request))
                    || $upload->form_id !== $formId || $upload->context !== 'intake'
                    || $upload->cancelled_at || $upload->claimed_at || !$upload->path
                    || !\Illuminate\Support\Facades\Storage::disk($upload->disk)->exists($upload->path)) {
                    $priorPayments = $parentSubmission?->submission_data['historical_payments'] ?? [];
                    $priorProof = $parentIndex !== null ? ($priorPayments[$parentIndex]['proof'] ?? null) : null;
                    if (is_array($priorProof) && !empty($priorProof['path']) && \Illuminate\Support\Facades\Storage::disk('local')->exists($priorProof['path'])) {
                        return $priorProof;
                    }
                    throw ValidationException::withMessages([$errorKey => 'This payment proof is no longer available. Upload it again or explain why it is unavailable.']);
                }
                $target = 'historical-payment-proofs/'.now()->format('Y/m').'/'.\Illuminate\Support\Str::uuid().'.'.pathinfo($upload->original_name, PATHINFO_EXTENSION);
                \Illuminate\Support\Facades\Storage::disk('local')->put($target, \Illuminate\Support\Facades\Storage::disk($upload->disk)->get($upload->path));
                return ['path' => $target, 'mime' => $upload->mime, 'name' => $upload->original_name];
            };
            if ($markAsPaid) {
                $proof = $copyHistoricalProof($validated['payment_proof_id'] ?? null, $validated['payment_proof_form_id'] ?? null, 'payment_proof_id', $validated['payment_proof_missing_reason'] ?? null, isset($validated['historical_primary_parent_payment_index']) ? (int) $validated['historical_primary_parent_payment_index'] : null);
                $historicalPaymentRows[] = [
                    'amount' => round((float) $validated['amount_paid'], 2), 'paid_at' => Carbon::parse($validated['paid_at'])->toDateTimeString(),
                    'receipt_or_no' => $validated['receipt_or_no'] ?? null, 'details' => $paymentDetails,
                    'proof' => $proof, 'proof_missing_reason' => $validated['payment_proof_missing_reason'] ?? null,
                ];
            }
            $seenHistoricalIdentifiers = [];
            foreach ($historicalPaymentRows as $row) {
                foreach ([['receipt_or_no', $row['receipt_or_no']], ['reference_number', $row['details']['reference_number'] ?? null], ['approval_code', $row['details']['approval_code'] ?? null]] as [$type, $value]) {
                    if (!$value) continue;
                    $fingerprint = $type.':'.mb_strtolower(trim((string) $value));
                    if (isset($seenHistoricalIdentifiers[$fingerprint])) {
                        return back()->withErrors(['historical_payments' => 'The same receipt or reference appears more than once in this submission.'])->withInput();
                    }
                    $duplicate = PaymentIdentifier::duplicate($branchId, $type, trim((string) $value), $row['details']['cashless_type'] ?? null, PaymentIdentifier::providerFor($row['details']));
                    if ($duplicate) {
                        return back()->withErrors(['receipt_or_no' => PaymentIdentifier::duplicateMessage($type, $duplicate)])->withInput();
                    }
                    $seenHistoricalIdentifiers[$fingerprint] = true;
                }
            }
            foreach ($additionalHistoricalPayments as $entry) {
                $row = $entry['row'];
                $details = $entry['details'];
                $proof = $copyHistoricalProof($row['payment_proof_id'] ?? null, $row['payment_proof_form_id'] ?? null, "historical_payments.{$entry['index']}.payment_proof_id", $row['proof_missing_reason'] ?? null, isset($row['parent_payment_index']) ? (int) $row['parent_payment_index'] : null);
                $historyRow = [
                    'amount' => round((float) $row['amount'], 2), 'paid_at' => Carbon::parse($row['paid_at'])->toDateTimeString(),
                    'receipt_or_no' => $row['receipt_or_no'] ?? null, 'details' => $details,
                    'proof' => $proof, 'proof_missing_reason' => $row['proof_missing_reason'] ?? null,
                ];
                foreach ([['receipt_or_no', $historyRow['receipt_or_no']], ['reference_number', $details['reference_number'] ?? null], ['approval_code', $details['approval_code'] ?? null]] as [$type, $value]) {
                    if (!$value) continue;
                    $fingerprint = $type.':'.mb_strtolower(trim((string) $value));
                    if (isset($seenHistoricalIdentifiers[$fingerprint])) {
                        return back()->withErrors(["historical_payments.{$entry['index']}.{$type}" => 'This receipt or reference number is duplicated in the historical payments.'])->withInput();
                    }
                    $duplicate = PaymentIdentifier::duplicate($branchId, $type, trim((string) $value), $details['cashless_type'] ?? null, PaymentIdentifier::providerFor($details));
                    if ($duplicate) {
                        return back()->withErrors(["historical_payments.{$entry['index']}.{$type}" => PaymentIdentifier::duplicateMessage($type, $duplicate)])->withInput();
                    }
                    $seenHistoricalIdentifiers[$fingerprint] = true;
                }
                $historicalPaymentRows[] = $historyRow;
            }
            $historicalPaidTotal = collect($historicalPaymentRows)->sum('amount');
            if (!(bool) config('funeral.allow_overpayment', false) && $historicalPaidTotal > $total) {
                return back()->withErrors(['historical_payments' => 'The historical payments cannot total more than the case amount.'])->withInput();
            }
            $addonRows = collect($selectedAddOns)->map(fn ($addon) => [
                'package_add_on_id' => null,
                'add_on_catalog_id' => $addon->id,
                'add_on_name_snapshot' => $addon->name,
                'add_on_description_snapshot' => $addon->description,
                'add_on_price_snapshot' => $addon->price,
                'quantity' => (int) $addon->quantity,
                'line_total' => round((float) $addon->line_total, 2),
            ])->values()->all();
            $historicalValidated = $validated;
            unset($historicalValidated['deceased_photo'], $historicalValidated['senior_proof']);
            $pricingForStorage = $pricing;
            unset($pricingForStorage['selected_add_ons']);
            $possibleMatches = FuneralCase::query()->with(['client:id,full_name', 'deceased:id,full_name'])
                ->where('branch_id', $branchId)
                ->whereHas('deceased', function ($query) use ($validated, $normalizedDeceasedName) {
                    $query->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedDeceasedName])
                        ->whereDate('died', $validated['died']);
                })->limit(5)->get(['id', 'case_code', 'client_id', 'deceased_id', 'service_requested_at']);
            $submission = HistoricalCaseSubmission::create([
                'submission_code' => 'HIS-'.now()->format('ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6)),
                'branch_id' => $branchId, 'encoded_by' => $user->id,
                'parent_submission_id' => $parentSubmission?->id,
                'status' => HistoricalCaseSubmission::PENDING,
                'submission_data' => [
                    'validated' => $historicalValidated,
                    'existing_client_id' => $existingClient?->id,
                    'possible_matches' => $possibleMatches->map(fn ($case) => [
                        'case_code' => $case->case_code,
                        'client_name' => $case->client?->full_name,
                        'deceased_name' => $case->deceased?->full_name,
                        'service_date' => $case->service_requested_at?->toDateString(),
                    ])->all(),
                    'pricing' => $pricingForStorage,
                    'schedule' => [
                        'wake_start_date' => $schedule['wake_start']->toDateString(), 'wake_start_time' => $validated['wake_start_time'],
                        'wake_end_date' => $schedule['wake_end']->toDateString(), 'wake_end_time' => $validated['wake_end_time'],
                        'funeral_date' => $schedule['funeral_service']->toDateString(), 'funeral_time' => $validated['funeral_service_time'],
                        'interment' => $schedule['interment']->toDateTimeString(), 'interment_time' => $validated['interment_time'],
                    ],
                    'age' => $age, 'wake_days' => $wakeDays, 'mark_as_paid' => $historicalPaymentRows !== [],
                    'historical_payments' => $historicalPaymentRows,
                    'payment_details' => $paymentDetails, 'initial_payment_type' => $initialPaymentType,
                    'payment_proof_missing_reason' => $validated['payment_proof_missing_reason'] ?? null,
                    'selected_package_id' => $selectedPackageId, 'hearse_id' => $selectedHearse?->id,
                    'hearse_name' => $selectedHearse?->name, 'is_custom' => $isCustomPackage,
                    'service_package_name' => $servicePackageName, 'package_price' => $packagePrice,
                    'package_inclusions' => $isCustomPackage ? ($validated['custom_package_inclusions'] ?? null) : implode("\n", $package?->inclusionNames() ?? []),
                    'package_freebies' => $isCustomPackage ? ($validated['custom_package_freebies'] ?? null) : implode("\n", $package?->freebieNames() ?? []),
                    'package_promo_snapshot' => $package && $package->is_promo_effective ? json_encode($pricing['snapshot']['promo'] ?? null) : null,
                    'coffin_type' => $coffinType, 'additional_service_amount' => $additionalServiceAmount,
                    'add_ons_total' => $addOnsTotal, 'selected_add_ons' => $addonRows,
                    'photo_path' => $photoPath, 'photo_file_name' => $photoFileName,
                    'senior_proof_path' => $seniorProofPath, 'senior_proof_file_name' => $seniorProofFileName,
                    'submitted_at' => now()->toIso8601String(),
                    'existing_client_id' => $existingClient?->id,
                ],
            ]);
            $submission->reviews()->create(['reviewed_by' => $user->id, 'action' => 'SUBMITTED', 'note' => null, 'created_at' => now()]);
            if ($parentSubmission) {
                $parentSubmission->reviews()->create(['reviewed_by' => $user->id, 'action' => 'RESUBMITTED', 'note' => 'Resubmitted as '.$submission->submission_code, 'created_at' => now()]);
                $parentSubmission->update(['resubmitted_at' => now()]);
            }
            AuditLogger::log(
                action: 'historical_case.submitted', actionType: 'create', entityType: 'historical_case_submission',
                entityId: $submission->id, metadata: ['submission_code' => $submission->submission_code, 'branch_id' => $branchId],
                branchId: $branchId, targetBranchId: $branchId, status: 'success',
                remarks: 'Historical case submitted for approval', actionLabel: 'Historical case submitted'
            );
            $successMessage = 'Historical case submitted for approval. It will not appear in official records until approved.';
            if ($request->boolean('intake_modal')) {
                return response()->view('staff.intake.modal-saved', [
                    'destination' => route('historical-cases.index'),
                    'stayOnPage' => true,
                    'message' => $successMessage,
                ]);
            }
            return redirect()->route('historical-cases.index')->with('success', $successMessage);
        }

        // Retry up to 3 times on a duplicate case_number collision (error 1062).
        // The transaction is idempotent: Client, Deceased, and FuneralCase rows are
        // all rolled back on failure, so a fresh attempt starts clean each time.
        $attempt    = 0;
        $maxRetries = 3;
        $createdCaseId = null;
        $createdPaymentId = null;
        do {
            $attempt++;
            try {
            DB::transaction(function () use (
                &$createdPaymentId,
                $validated,
                $existingClient,
                $branchId,
                $selectedPackageId,
                $selectedHearse,
                $isCustomPackage,
                $package,
                $servicePackageName,
                $packagePrice,
                $selectedAddOns,
                $addOnsTotal,
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
                $photoFileName,
                $seniorProofFileName,
                $user,
                $initialPaid,
                $initialBalance,
                $initialPaymentStatus,
                $entrySource,
                $mode,
                $discountPayload,
                $pricing,
                $caseStatus,
                $verificationStatus,
                $verifiedBy,
                $verifiedAt,
                $verificationNote,
                $initialPaymentType,
                $paymentDetails,
                $schedule,
                $serverRequestDate,
                $sourceDraft,
                &$createdCaseId,
            ) {
                Branch::whereKey($branchId)->lockForUpdate()->firstOrFail();
                if (!empty($validated['chapel_catalog_id'])) {
                    if ($package) {
                        Package::whereKey($package->id)->lockForUpdate()->firstOrFail();
                    }
                    $lockedChapel = AddOnCatalog::lockForUpdate()->findOrFail($validated['chapel_catalog_id']);
                    if (!$lockedChapel->chapel_ready || (int) $lockedChapel->branch_id !== (int) $branchId) {
                        throw ValidationException::withMessages(['chapel_add_on_id' => 'The chapel configuration changed. Review it before saving.']);
                    }
                    $currentAllowance = $package?->packageInclusions()
                        ->whereHas('addOnCatalog', fn ($query) => $query->where('category', AddOnCatalog::CHAPEL_VIEWING)
                            ->where('branch_id', $branchId))
                        ->lockForUpdate()->first();
                    if ($lockedChapel->price !== $validated['chapel_priced_rate']
                        || ($currentAllowance && (int) $currentAllowance->add_on_catalog_id !== (int) $lockedChapel->id)
                        || $currentAllowance?->included_quantity !== $validated['chapel_priced_days']
                        || $lockedChapel->name !== $validated['wake_location']
                        || $lockedChapel->chapel_address !== $validated['chapel_address']) {
                        throw ValidationException::withMessages(['chapel_add_on_id' => 'Chapel details or charges changed. Review the updated configuration before saving.']);
                    }
                    $latest = app(\App\Support\ChapelSchedule::class)->context($lockedChapel,
                        $validated['wake_start_date'].' '.$validated['wake_start_time'], $validated['wake_end_date'].' '.$validated['wake_end_time'],
                        lockForUpdate: true);
                    if ($latest['events']) {
                        throw ValidationException::withMessages(['chapel_add_on_id' => \App\Support\ChapelSchedule::conflictMessage($latest['events'][0])]);
                    }
                }
                $client = $existingClient ? Client::where('branch_id', $branchId)->lockForUpdate()->find($existingClient->id) : Client::create([
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
                if (! $client) {
                    throw ValidationException::withMessages(['existing_client_id' => 'This client is no longer available. Choose a client again.']);
                }

                $intermentAt = $schedule['interment'];

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
                    'client_relationship' => $validated['client_relationship'],
                    'deceased_id' => $deceased->id,
                    'package_id'  => $selectedPackageId,
                    'hearse_catalog_id' => $selectedHearse?->id,
                    'hearse_name_snapshot' => $selectedHearse?->name,
                    'package_name_snapshot' => $servicePackageName,
                    'package_price_snapshot' => $packagePrice,
                    'package_description_snapshot' => null,
                    'package_inclusions_snapshot' => $isCustomPackage
                        ? ($validated['custom_package_inclusions'] ?? null)
                        : implode("\n", $package?->inclusionNames() ?? []),
                    'package_freebies_snapshot' => $isCustomPackage
                        ? ($validated['custom_package_freebies'] ?? null)
                        : implode("\n", $package?->freebieNames() ?? []),
                    'package_promo_snapshot' => $package && $package->is_promo_effective
                        ? json_encode($pricing['snapshot']['promo'] ?? null)
                        : null,
                    'package_discount_snapshot' => $discountAmount,
                    'pricing_snapshot' => $pricing['snapshot'],
                    'case_number' => FuneralCase::nextCaseNumber($branchId),
                    'case_code'   => $this->nextCaseCode($branchId),
                    'custom_package_name'        => $isCustomPackage ? $servicePackageName : null,
                    'custom_package_price'       => $isCustomPackage ? $packagePrice : 0,
                    'custom_package_inclusions'  => $isCustomPackage ? ($validated['custom_package_inclusions'] ?? null) : null,
                    'custom_package_freebies'    => $isCustomPackage ? ($validated['custom_package_freebies'] ?? null) : null,
                    'service_type' => $validated['service_type'],
                    'service_requested_at' => $serverRequestDate,
                    'service_package' => $servicePackageName,
                    'coffin_type' => $coffinType,
                    'wake_location' => $validated['wake_location'],
                    'viewing_type' => $validated['viewing_type'] ?? null,
                    'chapel_address' => $validated['chapel_address'] ?? null,
                    'chapel_catalog_id' => $validated['chapel_catalog_id'] ?? null,
                    'viewing_type' => $validated['viewing_type'] ?? null,
                    'wake_start_date' => $schedule['wake_start']->toDateString(),
                    'wake_start_time' => $this->formatTimeForStorage($validated['wake_start_time'] ?? null),
                    'wake_end_date' => $schedule['wake_end']->toDateString(),
                    'wake_end_time' => $this->formatTimeForStorage($validated['wake_end_time'] ?? null),
                    'funeral_service_at' => Carbon::parse($validated['funeral_service_at'])->toDateString(),
                    'funeral_service_time' => $this->formatTimeForStorage($validated['funeral_service_time'] ?? null),
                    ...CeremonyLocation::attributes($validated),
                    'interment_at' => $intermentAt,
                    'interment_time' => $this->formatTimeForStorage($validated['interment_time'] ?? null),
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
                    'add_ons_total_amount' => $addOnsTotal,
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
                $createdCaseId = $funeralCase->id;
                foreach ([
                    [CaseAttachment::TYPE_DECEASED_PHOTO, $photoPath, $photoFileName],
                    [CaseAttachment::TYPE_SENIOR_PROOF, $seniorProofPath, $seniorProofFileName],
                ] as [$attachmentType, $filePath, $fileName]) {
                    if ($filePath) {
                        $funeralCase->attachments()->create([
                            'attachment_type' => $attachmentType,
                            'file_name' => $fileName ?: basename($filePath),
                            'file_path' => $filePath,
                            'uploaded_by' => $user->id,
                        ]);
                    }
                }
                if ($sourceDraft) {
                    $sourceDraft->update([
                        'status' => IntakeDraft::STATUS_SUBMITTED,
                        'submitted_case_id' => $funeralCase->id,
                    ]);
                }

                // Populate the normalized service_details row for this case.
                ServiceDetail::create([
                    'funeral_case_id' => $funeralCase->id,
                    'start_of_wake'   => $schedule['wake_start']->toDateString(),
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

                foreach ($selectedAddOns as $selectedAddOn) {
                    $funeralCase->caseAddOns()->create([
                        'package_add_on_id' => null,
                        'add_on_catalog_id' => $selectedAddOn->id,
                        'add_on_name_snapshot' => $selectedAddOn->name,
                        'add_on_description_snapshot' => $selectedAddOn->description,
                        'add_on_price_snapshot' => $selectedAddOn->price,
                        'quantity' => (int) $selectedAddOn->quantity,
                        'line_total' => round((float) $selectedAddOn->line_total, 2),
                    ]);
                }

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
                    $receiptOrNo = $validated['receipt_or_no'] ?? null;
                    $identifierChecks = [];
                    if ($receiptOrNo !== null && trim((string) $receiptOrNo) !== '') {
                        $identifierChecks[] = ['receipt_or_no', trim((string) $receiptOrNo)];
                    }
                    if (($paymentDetails['payment_method'] ?? null) === 'cashless') {
                        foreach (['reference_number', 'approval_code'] as $identifierType) {
                            $value = trim((string) ($paymentDetails[$identifierType] ?? ''));
                            if ($value !== '') $identifierChecks[] = [$identifierType, $value];
                        }
                    }
                    foreach ($identifierChecks as [$identifierType, $value]) {
                        $duplicate = PaymentIdentifier::duplicate(
                            $branchId,
                            $identifierType,
                            $value,
                            $paymentDetails['cashless_type'] ?? null,
                            PaymentIdentifier::providerFor($paymentDetails),
                        );
                        if ($duplicate) {
                            throw ValidationException::withMessages([
                                $identifierType => PaymentIdentifier::duplicateMessage($identifierType, $duplicate),
                            ]);
                        }
                    }
                    $payment = Payment::create([
                        'submission_id' => $validated['submission_id'] ?? null,
                        'submission_hash' => isset($validated['submission_id'])
                            ? $this->initialPaymentSubmissionHash($validated, $branchId, $paymentDetails) : null,
                        'funeral_case_id'  => $funeralCase->id,
                        'branch_id'        => $branchId,
                        'payment_record_no' => Payment::nextPaymentRecordNumber($paidAt),
                        'receipt_or_no' => $receiptOrNo,
                        'accounting_reference_no' => $receiptOrNo,
                        'method'           => $paymentDetails['legacy_method'],
                        'payment_mode'     => $paymentDetails['legacy_payment_mode'],
                        'payment_method'   => $paymentDetails['payment_method'],
                        'cashless_type'    => $paymentDetails['cashless_type'],
                        'bank_name'        => $paymentDetails['bank_name'],
                        'other_bank_name'  => $paymentDetails['other_bank_name'],
                        'wallet_provider'  => $paymentDetails['wallet_provider'],
                        'account_name'     => $paymentDetails['account_name'],
                        'mobile_number'    => $paymentDetails['mobile_number'],
                        'reference_number' => $paymentDetails['reference_number'],
                        'approval_code'    => $paymentDetails['approval_code'],
                        'card_type'        => $paymentDetails['card_type'],
                        'terminal_provider' => $paymentDetails['terminal_provider'],
                        'payment_channel'  => $paymentDetails['payment_channel'],
                        'payment_notes'    => $paymentDetails['payment_notes'],
                        'bank_or_channel'  => $paymentDetails['legacy_bank_or_channel'],
                        'other_bank_or_channel' => $paymentDetails['legacy_other_bank_or_channel'],
                        'transaction_reference_no' => $paymentDetails['legacy_transaction_reference_no'],
                        'sender_name'      => $paymentDetails['legacy_sender_name'],
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
                    $createdPaymentId = $payment->id;
                    \App\Support\Payments\PaymentProof::claim(request(), $payment, 'intake');

                    AuditLogger::log(
                        action: 'payment.created',
                        actionType: 'create',
                        entityType: 'payment',
                        entityId: $payment->id,
                        metadata: [
                            'case_id' => $funeralCase->id,
                            'amount' => $payment->amount,
                            'payment_record_no' => $payment->payment_record_no,
                            'payment_method' => $payment->payment_method,
                            'cashless_type' => $payment->cashless_type,
                            'bank_name' => $payment->bank_name,
                            'wallet_provider' => $payment->wallet_provider,
                            'reference_number' => $payment->reference_number,
                            'receipt_or_no' => $payment->receipt_or_no,
                            'approval_code' => $payment->approval_code,
                            'payment_channel' => $payment->payment_channel,
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
            Log::warning('intake.unique_collision_retry', [
                'branch_id' => $branchId,
                'attempt'   => $attempt,
                'error'     => $e->getMessage(),
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['deceased_name' => $e->getMessage()])->withInput();
        }
        } while ($attempt < $maxRetries);

        $legacyBackdatedAuditRedirect = false;
        if (is_string($postedServiceRequestedAt) && $postedServiceRequestedAt !== '' && ! empty($validated['died'])) {
            try {
                $legacyBackdatedAuditRedirect = Carbon::parse($postedServiceRequestedAt)
                    ->startOfDay()
                    ->lt(Carbon::parse($validated['died'])->startOfDay());
            } catch (\Throwable) {
                $legacyBackdatedAuditRedirect = false;
            }
        }

        $redirectRoute = $legacyBackdatedAuditRedirect
            ? route('intake.main.create')
            : ($mode === 'other'
            ? route('funeral-cases.other-reports')
            : route('funeral-cases.index', ['record_scope' => 'main']));

        $redirectResponse = redirect()
            ->to($createdPaymentId ? route('intake.saved') : $redirectRoute)
            ->with('intake_saved_mode', $mode)
            ->with('intake_case_saved_toast', true)
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
            $redirectResponse->with('warning', "Schedule advisory: {$scheduleConflictCount} other case(s) are scheduled on the same date in this branch. Record saved; review staff, venue, and vehicle availability.");
        }

        if ($createdPaymentId) {
            $savedPayment = Payment::with(['funeralCase.client', 'funeralCase.deceased'])->findOrFail($createdPaymentId);
            $redirectResponse->with('intake_payment', [
                'id' => $savedPayment->id,
                'record_no' => $savedPayment->display_payment_record_no,
                'case_code' => $savedPayment->funeralCase->case_code,
                'client' => $savedPayment->funeralCase->client?->full_name,
                'deceased' => $savedPayment->funeralCase->deceased?->full_name,
                'amount' => (float) $savedPayment->amount,
                'balance' => (float) $savedPayment->balance_after_payment,
                'method' => PaymentDetails::label($savedPayment),
                'received_at' => $savedPayment->paid_at->format('M d, Y h:i A'),
            ]);
        }

        if ($createdPaymentId) {
            $request->session()->put('intake_saved_state', [
                'intake_payment' => $request->session()->get('intake_payment'),
                'summary' => $request->session()->get('summary'),
                'intake_saved_mode' => $mode,
            ]);
        }
        return $this->intakeSaveResponse($request, $redirectResponse);
    }

    private function intakeSaveResponse(Request $request, RedirectResponse $response)
    {
        if ($request->routeIs('intake.main.store') && $request->boolean('intake_modal')) {
            return response()->view('staff.intake.modal-saved', ['destination' => $response->getTargetUrl()]);
        }

        return $response;
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

    private function resolveWakeDays(?int $wakeDays, ?string $wakeStartDate, ?string $wakeEndDate): ?int
    {
        return WakeDuration::days($wakeStartDate, $wakeEndDate);
    }

    private function resolveScheduleDatetimes(array $validated): array
    {
        return [
            'request_date' => Carbon::parse($validated['service_requested_at'])->startOfDay(),
            'wake_start' => $this->combineScheduleDateTime(
                $validated['wake_start_date'] ?? null,
                $validated['wake_start_time'] ?? null
            ),
            'wake_end' => $this->combineScheduleDateTime(
                $validated['wake_end_date'] ?? null,
                $validated['wake_end_time'] ?? null
            ),
            'funeral_service' => $this->combineScheduleDateTime(
                $validated['funeral_service_at'] ?? null,
                $validated['funeral_service_time'] ?? null
            ),
            'interment' => $this->combineScheduleDateTime(
                $validated['interment_at'] ?? null,
                $validated['interment_time'] ?? null
            ),
        ];
    }

    private function combineScheduleDateTime(?string $date, ?string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', Carbon::parse($date)->toDateString() . ' ' . substr((string) $time, 0, 5));
    }

    private function formatTimeForStorage(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        return Carbon::createFromFormat('H:i', substr($time, 0, 5))->format('H:i:s');
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
            $label = match ($attribute) {
                'wake_location' => 'Wake location',
                'chapel_address' => 'Chapel address',
                'place_of_cemetery' => 'Place of interment / cemetery',
                default => 'Complete address',
            };
            if (! preg_match('/\pL/u', (string) $value)) {
                $fail($label.' must include a place name.');
            } elseif (! preg_match("/^[\\pL\\pM\\pN .,\x{2019}'#()\\/\\&-]+$/u", (string) $value)) {
                $fail($label.' may contain letters, numbers, spaces, and address punctuation only (., apostrophe, #, (), /, &, -).');
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

    private function mergeLegacyScheduleFields(Request $request, string $serverRequestDate): void
    {
        $extractDate = static function (?string $value): ?string {
            if (! $value) {
                return null;
            }

            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        };

        $extractTime = static function (?string $value, string $fallback): string {
            if (! $value) {
                return $fallback;
            }

            try {
                return Carbon::parse($value)->format('H:i');
            } catch (\Throwable) {
                return $fallback;
            }
        };

        $funeralDate = $extractDate((string) $request->input('funeral_service_at')) ?? $serverRequestDate;
        $intermentDate = $extractDate((string) $request->input('interment_at')) ?? $funeralDate;

        $request->merge(array_filter([
            'wake_start_date' => $request->filled('wake_start_date')
                ? $request->input('wake_start_date')
                : ($extractDate((string) $request->input('wake_start_at')) ?? $serverRequestDate),
            'wake_start_time' => $request->filled('wake_start_time')
                ? $request->input('wake_start_time')
                : $extractTime((string) $request->input('wake_start_at'), '00:00'),
            'funeral_service_at' => $request->filled('funeral_service_at')
                ? $funeralDate
                : null,
            'funeral_service_time' => $request->filled('funeral_service_time')
                ? $request->input('funeral_service_time')
                : $extractTime((string) $request->input('funeral_service_at'), '00:00'),
            'interment_at' => $request->filled('interment_at')
                ? $intermentDate
                : null,
            'interment_time' => $request->filled('interment_time')
                ? $request->input('interment_time')
                : $extractTime((string) $request->input('interment_at'), '00:00'),
        ], static fn ($value): bool => $value !== null));
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
