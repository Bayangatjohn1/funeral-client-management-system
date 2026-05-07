<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\ServiceDetail;
use App\Support\Discount\CaseDiscountResolver;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuneralCaseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FuneralCase::class, 'funeral_case');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? 0);
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();
        $operationalBranch = $operationalBranchId > 0
            ? Branch::whereKey($operationalBranchId)->first()
            : null;

        $request->validate([
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'tab' => ['nullable', 'in:active,completed'],
            'case_status' => ['nullable', 'in:DRAFT,ACTIVE,COMPLETED'],
            'payment_status' => ['nullable', 'in:PAID,PARTIAL,UNPAID'],
            'date_preset' => ['nullable', 'in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date_range' => ['nullable', 'in:any,today,7d,30d,this_month,custom'],
            'request_date_from' => ['nullable', 'date'],
            'request_date_to' => ['nullable', 'date', 'after_or_equal:request_date_from'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
            'sort' => ['nullable', 'in:newest,oldest,service_date_asc,service_date_desc,total_desc,total_asc,paid_desc,paid_asc,balance_desc,balance_asc'],
            'quick_filter' => ['nullable', 'in:all,needs_attention,with_balance,recent,paid,recently_completed'],
            'record_scope' => ['nullable', 'in:main,other'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $recordScope = strtolower((string) $request->query('record_scope', 'main'));
        if (!in_array($recordScope, ['main', 'other'], true)) {
            $recordScope = 'main';
        }
        if ($recordScope === 'other') {
            return redirect()
                ->route('funeral-cases.other-reports')
                ->with('success', 'Other-branch reports are completed-only and are shown under Branch Reports.');
        }

        $currentTab = strtolower((string) $request->query('tab', 'active'));
        if (!in_array($currentTab, ['active', 'completed'], true)) {
            $currentTab = 'active';
        }

        $quickFilterOptions = $currentTab === 'active'
            ? [
                'all' => 'All',
                'needs_attention' => 'Needs Attention',
                'with_balance' => 'With Balance',
                'recent' => 'Recent',
            ]
            : [
                'all' => 'All',
                'paid' => 'Paid',
                'with_balance' => 'With Balance',
                'recently_completed' => 'Recently Completed',
            ];

        $quickFilter = strtolower((string) $request->query('quick_filter', 'all'));
        if (!array_key_exists($quickFilter, $quickFilterOptions)) {
            $quickFilter = 'all';
        }

        $sortOptions = $this->caseRecordSortOptions($currentTab);
        $sort = strtolower((string) $request->query('sort', 'newest'));
        if (!array_key_exists($sort, $sortOptions)) {
            $sort = 'newest';
        }

        $query = FuneralCase::query()
            ->select([
                'id',
                'branch_id',
                'client_id',
                'deceased_id',
                'package_id',
                'case_code',
                'service_requested_at',
                'created_at',
                'updated_at',
                'funeral_service_at',
                'service_package',
                'service_type',
                'interment_at',
                'total_amount',
                'total_paid',
                'balance_amount',
                'payment_status',
                'case_status',
            ])
            ->with([
                'client:id,full_name,contact_number',
                'deceased:id,full_name',
                'serviceDetail:id,funeral_case_id,internment_date',
                'branch:id,branch_code,branch_name',
            ])
            ->where('branch_id', $operationalBranchId)
            ->where(function ($scopeQuery) {
                $scopeQuery->where('entry_source', 'MAIN')
                    ->orWhereNull('entry_source');
            });

        if ($operationalBranchId <= 0) {
            $query->whereRaw('1 = 0');
        }

        if ($currentTab === 'active') {
            $query->whereIn('case_status', ['DRAFT', 'ACTIVE']);
        } else {
            $query->where('case_status', 'COMPLETED');
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('case_code', 'like', "%{$q}%")
                    ->orWhereHas('client', fn ($q2) => $q2->where('full_name', 'like', "%{$q}%"))
                    ->orWhereHas('deceased', fn ($q3) => $q3->where('full_name', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('case_status')) {
            $query->where('case_status', $request->case_status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->string('service_type')->toString());
        }
        if ($request->filled('package_id')) {
            $query->where('package_id', (int) $request->query('package_id'));
        }

        $datePreset = $request->string('date_preset')->toString();
        if ($datePreset === '' && $request->filled('date_range')) {
            $datePreset = match ($request->string('date_range')->toString()) {
                'today' => 'TODAY',
                'this_month' => 'THIS_MONTH',
                'custom' => 'CUSTOM',
                default => '',
            };
        }
        if ($datePreset === '' && ($request->filled('date_from') || $request->filled('date_to') || $request->filled('request_date_from') || $request->filled('request_date_to'))) {
            $datePreset = 'CUSTOM';
        }

        [$dateFrom, $dateTo] = $this->resolveCaseDateRange(
            $datePreset,
            $request->query('date_from', $request->query('request_date_from')),
            $request->query('date_to', $request->query('request_date_to'))
        );
        [$startAt, $endAt] = $this->parseDateBounds($dateFrom, $dateTo);
        $query
            ->when($startAt, fn ($dateQuery) => $dateQuery->where('created_at', '>=', $startAt))
            ->when($endAt, fn ($dateQuery) => $dateQuery->where('created_at', '<=', $endAt));

        $intermentFrom = $request->query('interment_from');
        $intermentTo = $request->query('interment_to');
        $query->when($intermentFrom || $intermentTo, function ($intermentQuery) use ($intermentFrom, $intermentTo) {
            $intermentQuery->where(function ($outer) use ($intermentFrom, $intermentTo) {
                $outer->whereHas('serviceDetail', function ($caseDate) use ($intermentFrom, $intermentTo) {
                    if ($intermentFrom) {
                        $caseDate->whereDate('internment_date', '>=', $intermentFrom);
                    }
                    if ($intermentTo) {
                        $caseDate->whereDate('internment_date', '<=', $intermentTo);
                    }
                });
            });
        });

        $this->applyCaseRecordQuickFilter($query, $currentTab, $quickFilter);
        $this->applyCaseRecordSort($query, $sort);

        $cases = $query->paginate(20)->withQueryString();

        $openWizard = $request->boolean('open_wizard') && $currentTab === 'active';
        $packages = Package::query()
            ->select(['id', 'name', 'coffin_type', 'price'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $branches = $operationalBranch ? collect([$operationalBranch]) : collect();
        $serviceTypes = FuneralCase::query()
            ->where('branch_id', $operationalBranchId)
            ->whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->distinct()
            ->orderBy('service_type')
            ->pluck('service_type');
        $defaultBranchId = $operationalBranchId > 0 ? $operationalBranchId : ((int) $user->branch_id);
        $nextCode = null;
        $nextCodeMap = collect();

        // Intake resources are only needed when the wizard is actually open.
        if ($openWizard) {
            $packages = Package::query()
                ->select(['id', 'name', 'coffin_type', 'price'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $nextCode = $this->nextCaseCode($defaultBranchId);
            $nextCodeMap = $branches->mapWithKeys(function ($branch) {
                return [$branch->id => $this->nextCaseCode((int) $branch->id)];
            });
        }

        return view('staff.funeral_cases.index', compact(
            'cases',
            'packages',
            'branches',
            'nextCode',
            'nextCodeMap',
            'canEncodeAnyBranch',
            'recordScope',
            'currentTab',
            'sortOptions',
            'sort',
            'quickFilterOptions',
            'quickFilter',
            'operationalBranch',
            'datePreset',
            'dateFrom',
            'dateTo',
            'intermentFrom',
            'intermentTo',
            'serviceTypes'
        ));
    }

    private function resolveCaseDateRange(?string $preset, ?string $dateFrom, ?string $dateTo): array
    {
        return match ($preset) {
            'TODAY' => [Carbon::today()->toDateString(), Carbon::today()->toDateString()],
            'THIS_MONTH' => [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->toDateString()],
            'THIS_YEAR' => [Carbon::today()->startOfYear()->toDateString(), Carbon::today()->toDateString()],
            'CUSTOM' => [$dateFrom, $dateTo],
            default => [null, null],
        };
    }

    public function completedIndex(Request $request)
    {
        if (strtolower((string) $request->query('record_scope')) === 'other') {
            return redirect()->route('funeral-cases.other-reports');
        }

        $request->query->replace(array_filter([
            'tab' => 'completed',
            'q' => $request->query('q'),
            'case_status' => $request->query('case_status'),
            'payment_status' => $request->query('payment_status'),
            'date_preset' => $request->query('date_preset'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'date_range' => $request->query('date_range'),
            'request_date_from' => $request->query('request_date_from'),
            'request_date_to' => $request->query('request_date_to'),
            'service_type' => $request->query('service_type'),
            'package_id' => $request->query('package_id'),
            'interment_from' => $request->query('interment_from'),
            'interment_to' => $request->query('interment_to'),
            'sort' => $request->query('sort'),
            'quick_filter' => $request->query('quick_filter'),
        ], fn ($value) => !is_null($value) && $value !== ''));

        return $this->index($request);
    }

    private function caseRecordSortOptions(string $tab): array
    {
        if ($tab === 'completed') {
            return [
                'newest' => 'Newest First',
                'oldest' => 'Oldest First',
                'total_desc' => 'Total Amount: High to Low',
                'total_asc' => 'Total Amount: Low to High',
                'paid_desc' => 'Total Paid: High to Low',
                'paid_asc' => 'Total Paid: Low to High',
                'balance_desc' => 'Balance: High to Low',
                'balance_asc' => 'Balance: Low to High',
            ];
        }

        return [
            'newest' => 'Newest First',
            'oldest' => 'Oldest First',
            'service_date_asc' => 'Funeral Service Date: Earliest',
            'service_date_desc' => 'Funeral Service Date: Latest',
            'total_desc' => 'Total Amount: High to Low',
            'total_asc' => 'Total Amount: Low to High',
            'balance_desc' => 'Balance: High to Low',
            'balance_asc' => 'Balance: Low to High',
        ];
    }

    private function applyCaseRecordQuickFilter($query, string $tab, string $quickFilter): void
    {
        if ($quickFilter === 'all') {
            return;
        }

        if ($tab === 'active') {
            if ($quickFilter === 'needs_attention') {
                $query->where(function ($sub) {
                    $sub->where('case_status', 'DRAFT')
                        ->orWhereIn('payment_status', ['UNPAID', 'PARTIAL']);
                });
                return;
            }

            if ($quickFilter === 'with_balance') {
                $query->where('balance_amount', '>', 0);
                return;
            }

            if ($quickFilter === 'recent') {
                $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                return;
            }

            return;
        }

        if ($quickFilter === 'paid') {
            $query->where('payment_status', 'PAID');
            return;
        }

        if ($quickFilter === 'with_balance') {
            $query->where('balance_amount', '>', 0);
            return;
        }

        if ($quickFilter === 'recently_completed') {
            $query->whereDate('updated_at', '>=', now()->subDays(30)->toDateString());
        }
    }

    private function applyCaseRecordSort($query, string $sort): void
    {
        if ($sort === 'oldest') {
            $query->oldest('created_at');
            return;
        }
        if ($sort === 'service_date_asc') {
            $query->orderBy('funeral_service_at');
            return;
        }
        if ($sort === 'service_date_desc') {
            $query->orderByDesc('funeral_service_at');
            return;
        }
        if ($sort === 'total_desc') {
            $query->orderByDesc('total_amount');
            return;
        }
        if ($sort === 'total_asc') {
            $query->orderBy('total_amount');
            return;
        }
        if ($sort === 'paid_desc') {
            $query->orderByDesc('total_paid');
            return;
        }
        if ($sort === 'paid_asc') {
            $query->orderBy('total_paid');
            return;
        }
        if ($sort === 'balance_desc') {
            $query->orderByDesc('balance_amount');
            return;
        }
        if ($sort === 'balance_asc') {
            $query->orderBy('balance_amount');
            return;
        }

        $query->latest('created_at');
    }

    public function otherReportsIndex(Request $request)
    {
        $user = auth()->user();
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();

        if (!$canEncodeAnyBranch) {
            abort(403, 'Only Main Branch Admin can view other-branch reports.');
        }

        $scopeBranches = Branch::whereIn('id', $user->branchScopeIds())
            ->orderBy('branch_code')
            ->get();

        $otherScopeBranchIds = $scopeBranches
            ->filter(function ($branch) {
                return strtoupper((string) $branch->branch_code) !== 'BR001';
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $request->validate([
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'verification_status' => ['nullable', 'in:PENDING,VERIFIED,DISPUTED'],
            'reported_from' => ['nullable', 'date'],
            'reported_to' => ['nullable', 'date', 'after_or_equal:reported_from'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $selectedBranchId = null;
        if ($request->filled('branch_id')) {
            $candidate = (int) $request->branch_id;
            if (in_array($candidate, $otherScopeBranchIds, true)) {
                $selectedBranchId = $candidate;
            }
        }

        $verificationStatus = $request->string('verification_status')->toString();
        $reportedFrom = $request->string('reported_from')->toString();
        $reportedTo = $request->string('reported_to')->toString();

        $query = FuneralCase::query()
            ->select([
                'id',
                'branch_id',
                'client_id',
                'deceased_id',
                'case_code',
                'service_requested_at',
                'created_at',
                'service_package',
                'total_amount',
                'total_paid',
                'balance_amount',
                'payment_status',
                'case_status',
                'interment_at',
                'reporter_name',
                'reported_at',
                'verification_status',
            ])
            ->with([
                'client:id,full_name',
                'deceased:id,full_name',
                'serviceDetail:id,funeral_case_id,internment_date',
                'branch:id,branch_code',
            ])
            ->where('entry_source', 'OTHER_BRANCH')
            ->where('case_status', 'COMPLETED')
            ->where('payment_status', 'PAID')
            ->latest();

        if ($otherScopeBranchIds !== []) {
            $query->whereIn('branch_id', $otherScopeBranchIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('case_code', 'like', "%{$q}%")
                    ->orWhereHas('client', fn ($q2) => $q2->where('full_name', 'like', "%{$q}%"))
                    ->orWhereHas('deceased', fn ($q3) => $q3->where('full_name', 'like', "%{$q}%"))
                    ->orWhere('reporter_name', 'like', "%{$q}%");
            });
        }

        if (!empty($verificationStatus)) {
            $query->where('verification_status', $verificationStatus);
        }
        if (!empty($reportedFrom)) {
            $query->whereDate('reported_at', '>=', $reportedFrom);
        }
        if (!empty($reportedTo)) {
            $query->whereDate('reported_at', '<=', $reportedTo);
        }

        $cases = $query->paginate(20)->withQueryString();
        $branches = $scopeBranches
            ->filter(function ($branch) {
                return strtoupper((string) $branch->branch_code) !== 'BR001';
            })
            ->values();

        return view('staff.funeral_cases.completed', compact(
            'cases',
            'branches',
            'selectedBranchId',
            'canEncodeAnyBranch',
            'verificationStatus',
            'reportedFrom',
            'reportedTo'
        ))->with([
            'recordScope' => 'other',
            'viewType' => 'other',
            'mainBranchId' => null,
        ]);
    }

    public function show(FuneralCase $funeral_case)
    {
        $funeral_case->load(['client', 'deceased', 'branch', 'reportedBranch', 'encodedBy', 'payments.recordedBy', 'payments.encodedBy', 'package.packageInclusions', 'package.packageFreebies', 'caseAddOns', 'serviceDetail']);

        return view('staff.funeral_cases.show', compact('funeral_case'));
    }

    public function create()
    {
        $defaultBranchId = (int) (auth()->user()->operationalBranchId() ?? 0);

        $clients = Client::where('branch_id', $defaultBranchId)
            ->orderBy('full_name')
            ->get();

        $deceaseds = Deceased::where('branch_id', $defaultBranchId)
            ->orderBy('full_name')
            ->get();

        $packages = Package::where('is_active', true)
            ->orderBy('name')
            ->get();
        $nextCode = $this->nextCaseCode($defaultBranchId);
        $nextCodeMap = Branch::whereKey($defaultBranchId)
            ->orderBy('branch_code')
            ->get()
            ->mapWithKeys(function ($branch) {
                return [$branch->id => $this->nextCaseCode((int) $branch->id)];
            });

        return view('staff.funeral_cases.create', compact('clients', 'deceaseds', 'packages', 'nextCode', 'nextCodeMap'));
    }

    public function store(Request $request)
    {
        $serverRequestDate = now()->toDateString();
        $request->merge(['service_requested_at' => $serverRequestDate]);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'deceased_id' => 'required|exists:deceased,id',
            'package_id' => 'required|integer|exists:packages,id',
            'case_status' => 'required|in:DRAFT,ACTIVE,COMPLETED',
            'date_of_death' => 'nullable|date|before_or_equal:today',
            // `service_requested_at` is an audit timestamp and not part of the
            // real-world event ordering. Only validate its format and that it's
            // not in the future.
            'service_requested_at' => 'nullable|date|before_or_equal:today',
            // Enforce funeral service to be on or after the date of death.
            'funeral_service_at' => 'nullable|date|after_or_equal:date_of_death',
            'interment_at' => 'nullable|date|after_or_equal:funeral_service_at',
            'is_backdated_entry' => 'nullable|boolean',
            'backdated_entry_reason' => 'required_if:is_backdated_entry,1|nullable|string|max:500',
        ], [
            'date_of_death.required' => 'Date of death is required.',
            'date_of_death.before_or_equal' => 'Date of death cannot be in the future.',
            'service_requested_at.required' => 'Request date is required.',
            'service_requested_at.before_or_equal' => 'Request date cannot be in the future.',
            'funeral_service_at.required' => 'Funeral service date is required.',
            'funeral_service_at.after_or_equal' => 'Funeral service date must be on or after the date of death.',
            'interment_at.required' => 'Interment date and time is required.',
            'interment_at.after_or_equal' => 'Interment date must be on or after the funeral service date.',
            'backdated_entry_reason.required_if' => 'Please provide a reason for a backdated request entry.',
        ]);

        $operationalBranchId = (int) (auth()->user()->operationalBranchId() ?? 0);

        $client = Client::find($validated['client_id']);
        $deceased = Deceased::find($validated['deceased_id']);

        if (!$client || (int) $client->branch_id !== $operationalBranchId) {
            abort(403);
        }
        if (!$deceased || (int) $deceased->branch_id !== $operationalBranchId) {
            abort(403);
        }
        if ($deceased->client_id !== $client->id) {
            return back()->withErrors(['deceased_id' => 'Selected deceased does not belong to the selected client.'])
                ->withInput();
        }

        $package = Package::where('id', $validated['package_id'])
            ->where('is_active', true)
            ->first();
        if (!$package) {
            return back()->withErrors(['package_id' => 'Selected package is unavailable.'])->withInput();
        }

        $entrySource = 'MAIN';
        $dateOfDeath = !empty($validated['date_of_death'])
            ? Carbon::parse($validated['date_of_death'])->toDateString()
            : ($deceased->died?->toDateString() ?? $deceased->date_of_death?->toDateString());
        if (!$dateOfDeath) {
            return back()->withErrors(['date_of_death' => 'Date of death is required.'])->withInput();
        }

        $deceased->forceFill([
            'died' => $dateOfDeath,
            'date_of_death' => $dateOfDeath,
        ])->save();

        $subtotal = (float) $package->price;
        $discountPayload = app(CaseDiscountResolver::class)
            ->resolve($package, $this->resolveDeceasedAge($deceased), $subtotal, now());
        $discount = (float) $discountPayload['discount_amount'];
        $total = round(max($subtotal - $discount, 0), 2);
        $payment = $this->computePaymentFields($total, 0);
        $serviceRequestedAt = !empty($validated['service_requested_at'])
            ? Carbon::parse($validated['service_requested_at'])->toDateString()
            : now()->toDateString();
        if (
            !empty($validated['service_requested_at'])
            && Carbon::parse($serviceRequestedAt)->lt(today())
            && !($validated['is_backdated_entry'] ?? false)
        ) {
            return back()->withErrors([
                'is_backdated_entry' => 'Backdated request entries must be clearly marked.',
                'backdated_entry_reason' => 'Please provide a reason for a backdated request entry.',
            ])->withInput();
        }

        $wakeLocation = $this->resolveLegacyWakeLocation($client);
        // Determine funeral service date only from explicit event fields (do not
        // default to `service_requested_at`). Prefer provided `funeral_service_at`,
        // otherwise try known deceased interment dates. If none available, require
        // the caller to provide a funeral_service_at.
        $funeralServiceAt = null;
        if (!empty($validated['funeral_service_at'])) {
            $funeralServiceAt = Carbon::parse($validated['funeral_service_at'])->toDateString();
        } elseif ($deceased->interment_at) {
            $funeralServiceAt = $deceased->interment_at->toDateString();
        } elseif ($deceased->interment) {
            $funeralServiceAt = $deceased->interment->toDateString();
        }

        if (!$funeralServiceAt) {
            return back()->withErrors([
                'funeral_service_at' => 'Funeral service date is required.',
            ])->withInput();
        }

        if (Carbon::parse($funeralServiceAt)->lt(Carbon::parse($dateOfDeath))) {
            return back()->withErrors([
                'funeral_service_at' => 'Funeral service date must be on or after the date of death.',
            ])->withInput();
        }

        $intermentAt = !empty($validated['interment_at'])
            ? Carbon::parse($validated['interment_at'])
            : ($deceased->interment_at ?: null);
        if ($intermentAt && $intermentAt->lt(Carbon::parse($funeralServiceAt)->startOfDay())) {
            return back()->withErrors([
                'interment_at' => 'Interment date must be on or after the funeral service date.',
            ])->withInput();
        }

        $hasDuplicateOpenCase = FuneralCase::query()
            ->where('branch_id', $client->branch_id)
            ->where('deceased_id', $deceased->id)
            ->whereIn('case_status', ['DRAFT', 'ACTIVE'])
            ->exists();

        if ($hasDuplicateOpenCase) {
            return back()->withErrors([
                'deceased_id' => 'This deceased already has an active case in this branch.',
            ])->withInput();
        }

        // Wrap case creation and service_details in a retryable transaction.
        // On a duplicate case_number collision (error 1062) the loop calls
        // nextCaseNumber() again with a fresh MAX before retrying. Max 3 attempts.
        $attempt    = 0;
        $maxRetries = 3;
        do {
            $attempt++;
            DB::beginTransaction();
            try {
                $case = FuneralCase::create([
                    'branch_id'   => $client->branch_id,
                    'client_id'   => $client->id,
                    'deceased_id' => $deceased->id,
                    'package_id'  => $package->id,
                    'case_number' => FuneralCase::nextCaseNumber((int) $client->branch_id),
                    'case_code'   => $this->nextCaseCode((int) $client->branch_id),
                    'service_requested_at' => $serviceRequestedAt,
                    'service_package'  => $package->name,
                    'coffin_type'      => $package->coffin_type,
                    'wake_location'    => $wakeLocation,
                    'funeral_service_at' => $funeralServiceAt,
                    'interment_at' => $intermentAt,
                    'subtotal_amount'  => $subtotal,
                    'discount_type'    => $discountPayload['discount_type'],
                    'discount_value_type' => $discountPayload['discount_value_type'],
                    'discount_value'   => $discountPayload['discount_value'],
                    'discount_amount'  => $discount,
                    'discount_note'    => $discountPayload['discount_note'],
                    'total_amount'     => $total,
                    'total_paid'       => $payment['total_paid'],
                    'balance_amount'   => $payment['balance'],
                    'payment_status'   => $payment['status'],
                    'case_status'      => $validated['case_status'],
                    'encoded_by'       => auth()->id(),
                    'reported_branch_id' => $client->branch_id,
                    'reported_at'      => now(),
                    'entry_source'     => $entrySource,
                    'verification_status' => 'VERIFIED',
                    'verified_by'      => auth()->id(),
                    'verified_at'      => now(),
                    'verification_note' => 'Auto-verified main-branch case creation.',
                ]);

                ServiceDetail::create([
                    'funeral_case_id' => $case->id,
                    'start_of_wake'   => $funeralServiceAt,
                    'internment_date' => $intermentAt?->toDateString(),
                    'wake_days'       => null,
                    'wake_location'   => $wakeLocation,
                    'cemetery_place'  => null,
                    'case_status'     => match ($validated['case_status']) {
                        'ACTIVE'    => 'ongoing',
                        'COMPLETED' => 'completed',
                        default     => 'pending',
                    },
                ]);

                DB::commit();
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                if ($attempt >= $maxRetries || ($e->errorInfo[1] ?? 0) !== 1062) {
                    Log::error('funeral_case.creation_failed', [
                        'branch_id' => $client->branch_id,
                        'attempt'   => $attempt,
                        'error'     => $e->getMessage(),
                    ]);
                    return back()->withErrors(['case' => 'Failed to create case record. Please try again.'])->withInput();
                }
                Log::warning('funeral_case.case_number_collision', [
                    'branch_id' => $client->branch_id,
                    'attempt'   => $attempt,
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('funeral_case.creation_failed', [
                    'branch_id' => $client->branch_id,
                    'error'     => $e->getMessage(),
                ]);
                return back()->withErrors(['case' => 'Failed to create case record. Please try again.'])->withInput();
            }
        } while ($attempt < $maxRetries);

        AuditLogger::log(
            'case.created',
            'create',
            'funeral_case',
            $case->id,
            [
                'case_status'    => $case->case_status,
                'payment_status' => $case->payment_status,
                'entry_source'   => $case->entry_source,
                'package_id'     => $case->package_id,
                'service_requested_at' => $case->service_requested_at?->toDateString(),
                'funeral_service_at' => $case->funeral_service_at?->toDateString(),
                'interment_at' => $case->interment_at?->toDateTimeString(),
                'is_backdated_entry' => (bool) ($validated['is_backdated_entry'] ?? false),
                'backdated_entry_reason' => $validated['backdated_entry_reason'] ?? null,
            ],
            (int) $case->branch_id,
            null,
            'success',
            null,
            'Case created'
        );

        return redirect()->route('funeral-cases.index')->with('success', 'Funeral case created successfully.');
    }

    public function edit(FuneralCase $funeral_case)
    {
        $user = auth()->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? 0);
        $editableBranchId = $user?->isMainBranchAdmin()
            ? (int) $funeral_case->branch_id
            : $operationalBranchId;

        if ((int) $funeral_case->branch_id !== $editableBranchId) {
            abort(403);
        }
        if (($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH') {
            return redirect()
                ->route('funeral-cases.show', $funeral_case)
                ->withErrors(['case' => 'Other-branch reported cases are locked. Use admin verification workflow for review.']);
        }

        $clients = Client::where('branch_id', $editableBranchId)
            ->orderBy('full_name')
            ->get();

        $deceaseds = Deceased::where('branch_id', $editableBranchId)
            ->orderBy('full_name')
            ->get();

        $packages = Package::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('staff.funeral_cases.edit', compact('funeral_case', 'clients', 'deceaseds', 'packages'));
    }

    public function update(Request $request, FuneralCase $funeral_case)
    {
        $originalStatus = $funeral_case->case_status;
        $originalPaymentStatus = $funeral_case->payment_status;
        $originalEntrySource = $funeral_case->entry_source;

        $user = auth()->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? 0);
        $editableBranchId = $user?->isMainBranchAdmin()
            ? (int) $funeral_case->branch_id
            : $operationalBranchId;

        if ((int) $funeral_case->branch_id !== $editableBranchId) {
            abort(403);
        }
        if (($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH') {
            return back()->withErrors([
                'case' => 'Other-branch reported cases are locked and cannot be edited from case management.',
            ]);
        }

        $serverRequestDate = $funeral_case->service_requested_at?->toDateString()
            ?? $funeral_case->created_at?->toDateString()
            ?? now()->toDateString();
        $request->merge(['service_requested_at' => $serverRequestDate]);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'deceased_id' => 'required|exists:deceased,id',
            'package_id' => 'required|integer|exists:packages,id',
            'case_status' => 'required|in:DRAFT,ACTIVE,COMPLETED',
            'date_of_death' => 'required|date|before_or_equal:today',
            'service_requested_at' => 'required|date|before_or_equal:today',
            'wake_start_date' => 'nullable|date',
            'wake_start_time' => 'nullable|date_format:H:i',
            'funeral_service_at' => 'required|date',
            'funeral_service_time' => 'nullable|date_format:H:i',
            'interment_at' => 'required|date',
            'interment_time' => 'nullable|date_format:H:i',
            'is_backdated_entry' => 'nullable|boolean',
            'backdated_entry_reason' => 'required_if:is_backdated_entry,1|nullable|string|max:500',
        ], [
            'date_of_death.required' => 'Date of death is required.',
            'date_of_death.before_or_equal' => 'Date of death cannot be in the future.',
            'service_requested_at.required' => 'Request date is required.',
            'service_requested_at.before_or_equal' => 'Request date cannot be in the future.',
            'wake_start_date.required' => 'Please select a wake start date and time.',
            'wake_start_time.required' => 'Please select a wake start date and time.',
            'funeral_service_at.required' => 'Please select a funeral service date and time.',
            'funeral_service_time.required' => 'Please select a funeral service date and time.',
            'interment_at.required' => 'Please select an interment date and time.',
            'interment_time.required' => 'Please select an interment date and time.',
            'backdated_entry_reason.required_if' => 'Please provide a reason for a backdated request entry.',
        ]);

        $client = Client::find($validated['client_id']);
        $deceased = Deceased::find($validated['deceased_id']);

        if (!$client || (int) $client->branch_id !== $editableBranchId) {
            abort(403);
        }
        if (!$deceased || (int) $deceased->branch_id !== $editableBranchId) {
            abort(403);
        }
        if ($deceased->client_id !== $client->id) {
            return back()->withErrors(['deceased_id' => 'Selected deceased does not belong to the selected client.'])
                ->withInput();
        }

        $package = Package::where('id', $validated['package_id'])
            ->where('is_active', true)
            ->first();
        if (!$package) {
            return back()->withErrors(['package_id' => 'Selected package is unavailable.'])->withInput();
        }

        $entrySource = 'MAIN';

        $subtotal = (float) $package->price;
        $discountPayload = app(CaseDiscountResolver::class)
            ->resolve($package, $this->resolveDeceasedAge($deceased), $subtotal, now());
        $discount = (float) $discountPayload['discount_amount'];
        $total = round(max($subtotal - $discount, 0), 2);
        $originalRequestDate = $serverRequestDate;
        $serviceRequestedAt = $serverRequestDate;
        if (
            $serviceRequestedAt !== $originalRequestDate
            && Carbon::parse($serviceRequestedAt)->lt(today())
            && !($validated['is_backdated_entry'] ?? false)
        ) {
            return back()->withErrors([
                'is_backdated_entry' => 'Backdated request entries must be clearly marked.',
                'backdated_entry_reason' => 'Please provide a reason for a backdated request entry.',
            ])->withInput();
        }

        $dateOfDeath = Carbon::parse($validated['date_of_death'])->toDateString();
        $deceased->forceFill([
            'died' => $dateOfDeath,
            'date_of_death' => $dateOfDeath,
        ])->save();

        $wakeLocation = $funeral_case->wake_location ?: $this->resolveLegacyWakeLocation($client);
        $scheduleWasEdited = $this->scheduleWasEdited($funeral_case, $validated);
        if ($scheduleWasEdited) {
            foreach ([
                'wake_start_date' => 'Please select a wake start date and time.',
                'wake_start_time' => 'Please select a wake start date and time.',
                'funeral_service_time' => 'Please select a funeral service date and time.',
                'interment_time' => 'Please select an interment date and time.',
            ] as $field => $message) {
                if (blank($validated[$field] ?? null)) {
                    return back()->withErrors([$field => $message])->withInput();
                }
            }
        }

        $funeralServiceAt = Carbon::parse($validated['funeral_service_at'])->toDateString();
        $intermentAt = $scheduleWasEdited
            ? $this->combineScheduleDateTime($validated['interment_at'], $validated['interment_time'])
            : $this->combineScheduleDateTime(
                $validated['interment_at'],
                $funeral_case->interment_at?->format('H:i') ?? '00:00'
            );

        if ($scheduleWasEdited) {
            $scheduleError = $this->validateScheduleOrder($validated);
            if ($scheduleError !== null) {
                return back()->withErrors($scheduleError)->withInput();
            }
        }
        $wakeDays = $scheduleWasEdited
            ? $this->resolveWakeDays($validated['wake_start_date'] ?? null, $validated['funeral_service_at'] ?? null)
            : $funeral_case->deceased?->wake_days;
        $serviceDetailWakeStart = $scheduleWasEdited
            ? Carbon::parse($validated['wake_start_date'])->toDateString()
            : ($funeral_case->wake_start_date?->toDateString() ?? $funeralServiceAt);

        $payment = $this->computePaymentFields($total, (float) $funeral_case->total_paid);
        $hasDuplicateOpenCase = FuneralCase::query()
            ->where('branch_id', $client->branch_id)
            ->where('deceased_id', $deceased->id)
            ->whereKeyNot($funeral_case->id)
            ->whereIn('case_status', ['DRAFT', 'ACTIVE'])
            ->exists();

        if ($hasDuplicateOpenCase && in_array($validated['case_status'], ['DRAFT', 'ACTIVE'], true)) {
            return back()->withErrors([
                'deceased_id' => 'Another active case already exists for this deceased in this branch.',
            ])->withInput();
        }

        $trackFields = [
            'case_status',
            'payment_status',
            'total_amount',
            'balance_amount',
            'package_id',
            'branch_id',
            'service_requested_at',
            'wake_start_date',
            'wake_start_time',
            'funeral_service_at',
            'funeral_service_time',
            'interment_at',
            'interment_time',
        ];
        $beforeValues = [];
        foreach ($trackFields as $field) {
            $beforeValues[$field] = $funeral_case->{$field};
        }

        $funeral_case->update([
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'branch_id' => $client->branch_id,
            'service_package' => $package->name,
            'coffin_type' => $package->coffin_type,
            'service_requested_at' => $serviceRequestedAt,
            'wake_location' => $wakeLocation,
            'wake_start_date' => $scheduleWasEdited ? Carbon::parse($validated['wake_start_date'])->toDateString() : $funeral_case->wake_start_date,
            'wake_start_time' => $scheduleWasEdited ? $this->formatTimeForStorage($validated['wake_start_time'] ?? null) : $funeral_case->wake_start_time,
            'funeral_service_at' => $funeralServiceAt,
            'funeral_service_time' => $scheduleWasEdited ? $this->formatTimeForStorage($validated['funeral_service_time'] ?? null) : $funeral_case->funeral_service_time,
            'interment_at' => $intermentAt,
            'interment_time' => $scheduleWasEdited ? $this->formatTimeForStorage($validated['interment_time'] ?? null) : $funeral_case->interment_time,
            'subtotal_amount' => $subtotal,
            'discount_type' => $discountPayload['discount_type'],
            'discount_value_type' => $discountPayload['discount_value_type'],
            'discount_value' => $discountPayload['discount_value'],
            'discount_amount' => $discount,
            'discount_note' => $discountPayload['discount_note'],
            'total_amount' => $total,
            'total_paid' => $payment['total_paid'],
            'balance_amount' => $payment['balance'],
            'payment_status' => $payment['status'],
            'case_status' => $validated['case_status'],
            'entry_source' => $entrySource,
            'verification_status' => 'VERIFIED',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'verification_note' => 'Auto-verified main-branch case update.',
        ]);

        $funeral_case->serviceDetail()->updateOrCreate(
            ['funeral_case_id' => $funeral_case->id],
            [
                'start_of_wake' => $serviceDetailWakeStart,
                'internment_date' => $intermentAt?->toDateString(),
                'wake_location' => $wakeLocation,
                'wake_days' => $wakeDays,
                'cemetery_place' => $request->input('place_of_cemetery'),
                'case_status' => match ($funeral_case->case_status) {
                    'ACTIVE' => 'ongoing',
                    'COMPLETED' => 'completed',
                    default => 'pending',
                },
            ]
        );

        if ($scheduleWasEdited && $deceased) {
            $deceased->forceFill([
                'interment' => $intermentAt?->toDateString(),
                'interment_at' => $intermentAt,
                'wake_days' => $wakeDays,
            ])->save();
        }

        $changes = [];
        foreach ($trackFields as $field) {
            $after = $funeral_case->{$field};
            if ($beforeValues[$field] != $after) {
                $changes[] = [
                    'field' => $field,
                    'before' => $beforeValues[$field],
                    'after' => $after,
                ];
            }
        }
        if ($changes === []) {
            $changes[] = [
                'field' => 'note',
                'before' => null,
                'after' => 'Updated with no tracked field changes',
            ];
        }

        AuditLogger::log(
            'case.updated',
            'update',
            'funeral_case',
            $funeral_case->id,
            [
                'case_status' => $funeral_case->case_status,
                'payment_status' => $funeral_case->payment_status,
                'entry_source' => $funeral_case->entry_source,
                'package_id' => $funeral_case->package_id,
                'is_backdated_entry' => (bool) ($validated['is_backdated_entry'] ?? false),
                'backdated_entry_reason' => $validated['backdated_entry_reason'] ?? null,
                'changes' => $changes,
            ],
            (int) $funeral_case->branch_id,
            null,
            'success',
            'Case details updated',
            'Case updated'
        );

        if ($originalStatus !== $funeral_case->case_status) {
            AuditLogger::log(
                'case.status_changed',
                'status_change',
                'funeral_case',
                $funeral_case->id,
                [
                    'from' => $originalStatus,
                    'to' => $funeral_case->case_status,
                    'payment_status' => $funeral_case->payment_status,
                    'entry_source' => $funeral_case->entry_source,
                ],
                (int) $funeral_case->branch_id,
                null,
                'success',
                'Case status updated',
                'Case status changed'
            );
        }

        return redirect()->route('funeral-cases.index')->with('success', 'Funeral case updated successfully.');
    }

    public function destroy(FuneralCase $funeral_case)
    {
        $operationalBranchId = (int) (auth()->user()->operationalBranchId() ?? 0);

        if ((int) $funeral_case->branch_id !== $operationalBranchId) {
            abort(403);
        }
        if (($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH') {
            return back()->withErrors([
                'case' => 'Other-branch reported cases are locked and cannot be deleted from case management.',
            ]);
        }

        if ($funeral_case->payments()->exists()) {
            return back()->withErrors([
                'case' => 'This case has payment records and cannot be deleted.',
            ]);
        }

        $funeral_case->delete();

        return back()->with('success', 'Funeral case deleted.');
    }

    private function computePaymentFields(float $totalAmount, float $currentPaid): array
    {
        $total = round(max($totalAmount, 0), 2);
        $paid = round(max($currentPaid, 0), 2);

        if ($total <= 0) {
            return [
                'total_paid' => 0.00,
                'balance' => 0.00,
                'status' => 'PAID',
            ];
        }

        if ($paid >= $total) {
            return [
                'total_paid' => $total,
                'balance' => 0.00,
                'status' => 'PAID',
            ];
        }

        if ($paid > 0) {
            return [
                'total_paid' => $paid,
                'balance' => round($total - $paid, 2),
                'status' => 'PARTIAL',
            ];
        }

        return [
            'total_paid' => 0.00,
            'balance' => $total,
            'status' => 'UNPAID',
        ];
    }

    private function nextCaseCode(int $branchId): string
    {
        $max = FuneralCase::query()
            ->where('branch_id', $branchId)
            ->selectRaw('MAX(CAST(SUBSTRING(case_code, 3) AS UNSIGNED)) as max_case_number')
            ->value('max_case_number');

        $next = ($max ?? 0) + 1;

        return 'FC' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function resolveDeceasedAge(Deceased $deceased): ?int
    {
        if ($deceased->age !== null) {
            return (int) $deceased->age;
        }

        if ($deceased->born && $deceased->died) {
            return $deceased->born->diffInYears($deceased->died);
        }

        return null;
    }

    private function resolveLegacyWakeLocation(Client $client): string
    {
        $address = trim((string) ($client->address ?? ''));

        return $address !== '' ? $address : 'Not specified';
    }

    private function resolveLegacyFuneralServiceDate(Deceased $deceased, string $fallbackDate): string
    {
        if ($deceased->interment_at) {
            return $deceased->interment_at->toDateString();
        }

        if ($deceased->interment) {
            return $deceased->interment->toDateString();
        }

        return $fallbackDate;
    }

    private function scheduleWasEdited(FuneralCase $case, array $validated): bool
    {
        $current = [
            'wake_start_date' => $case->wake_start_date?->toDateString(),
            'wake_start_time' => $this->normalizeTimeForComparison($case->wake_start_time),
            'funeral_service_at' => $case->funeral_service_at?->toDateString(),
            'funeral_service_time' => $this->normalizeTimeForComparison($case->funeral_service_time),
            'interment_at' => $case->interment_at?->toDateString(),
            'interment_time' => $this->normalizeTimeForComparison($case->interment_time),
        ];

        foreach ($current as $field => $oldValue) {
            $newValue = $field === 'interment_at' || str_ends_with($field, '_date') || $field === 'funeral_service_at'
                ? (! blank($validated[$field] ?? null) ? Carbon::parse($validated[$field])->toDateString() : null)
                : $this->normalizeTimeForComparison($validated[$field] ?? null);

            if ($newValue !== $oldValue) {
                return true;
            }
        }

        return false;
    }

    private function validateScheduleOrder(array $validated): ?array
    {
        $requestDate = Carbon::parse($validated['service_requested_at'])->startOfDay();
        $wakeStart = $this->combineScheduleDateTime($validated['wake_start_date'], $validated['wake_start_time']);
        $funeralService = $this->combineScheduleDateTime($validated['funeral_service_at'], $validated['funeral_service_time']);
        $interment = $this->combineScheduleDateTime($validated['interment_at'], $validated['interment_time']);

        if ($requestDate->gt($wakeStart->copy()->startOfDay())) {
            return ['wake_start_date' => 'Wake start date cannot be before the request/recorded date.'];
        }

        if ($funeralService->lt($wakeStart)) {
            return ['funeral_service_at' => 'Funeral service date/time cannot be before the wake start date/time.'];
        }

        if ($interment->lt($funeralService)) {
            return ['interment_at' => 'Interment date/time cannot be before the funeral service date/time.'];
        }

        return null;
    }

    private function resolveWakeDays(?string $wakeStartDate, ?string $funeralServiceDate): ?int
    {
        if (!$wakeStartDate || !$funeralServiceDate) {
            return null;
        }

        $wakeDate = Carbon::parse($wakeStartDate)->startOfDay();
        $serviceDate = Carbon::parse($funeralServiceDate)->startOfDay();

        if ($serviceDate->lt($wakeDate)) {
            return null;
        }

        return min(365, $wakeDate->diffInDays($serviceDate));
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

    private function normalizeTimeForComparison(mixed $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return substr((string) $time, 0, 5);
    }
}
