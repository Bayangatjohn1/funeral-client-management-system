<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Support\Discount\CaseDiscountResolver;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class FuneralCaseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FuneralCase::class, 'funeral_case');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $scopeBranchIds = $user->branchScopeIds();
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();
        $scopeBranches = Branch::whereIn('id', $scopeBranchIds)->orderBy('branch_code')->get();
        $mainBranch = $scopeBranches->first(function ($branch) {
            return strtoupper((string) $branch->branch_code) === 'BR001';
        });
        $mainBranchId = (int) ($mainBranch?->id ?? 0);

        $request->validate([
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'tab' => ['nullable', 'in:active,completed'],
            'case_status' => ['nullable', 'in:DRAFT,ACTIVE'],
            'payment_status' => ['nullable', 'in:PAID,PARTIAL,UNPAID'],
            'date_range' => ['nullable', 'in:any,today,7d,30d,this_month,custom'],
            'request_date_from' => ['nullable', 'date'],
            'request_date_to' => ['nullable', 'date', 'after_or_equal:request_date_from'],
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
                'case_code',
                'service_requested_at',
                'created_at',
                'updated_at',
                'funeral_service_at',
                'service_package',
                'total_amount',
                'total_paid',
                'balance_amount',
                'payment_status',
                'case_status',
            ])
            ->with([
                'client:id,full_name,contact_number',
                'deceased:id,full_name,interment_at,interment',
                'branch:id,branch_code',
            ])
            ->where('branch_id', $mainBranchId)
            ->where(function ($scopeQuery) {
                $scopeQuery->where('entry_source', 'MAIN')
                    ->orWhereNull('entry_source');
            });

        if ($mainBranchId <= 0) {
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

        if ($currentTab === 'active' && $request->filled('case_status')) {
            $query->where('case_status', $request->case_status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        $dateRange = $request->string('date_range', 'any')->toString();
        $usesCustomDate = $dateRange === 'custom'
            || (!$request->filled('date_range') && ($request->filled('request_date_from') || $request->filled('request_date_to')));

        if ($usesCustomDate) {
            if ($request->filled('request_date_from')) {
                $query->whereDate('service_requested_at', '>=', $request->string('request_date_from')->toString());
            }
            if ($request->filled('request_date_to')) {
                $query->whereDate('service_requested_at', '<=', $request->string('request_date_to')->toString());
            }
        } elseif ($dateRange !== 'any') {
            $today = now()->startOfDay();
            if ($dateRange === 'today') {
                $query->whereDate('service_requested_at', $today->toDateString());
            } elseif ($dateRange === '7d') {
                $query->where('service_requested_at', '>=', now()->subDays(7)->startOfDay());
            } elseif ($dateRange === '30d') {
                $query->where('service_requested_at', '>=', now()->subDays(30)->startOfDay());
            } elseif ($dateRange === 'this_month') {
                $query->whereBetween('service_requested_at', [now()->startOfMonth(), now()->endOfMonth()]);
            }
        }

        $this->applyCaseRecordQuickFilter($query, $currentTab, $quickFilter);
        $this->applyCaseRecordSort($query, $sort);

        $cases = $query->paginate(20)->withQueryString();

        $openWizard = $request->boolean('open_wizard') && $currentTab === 'active';
        $packages = collect();
        $branches = $mainBranch ? collect([$mainBranch]) : collect();
        $defaultBranchId = $mainBranchId > 0 ? $mainBranchId : ((int) $user->branch_id);
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
            'quickFilter'
        ));
    }

    public function completedIndex(Request $request)
    {
        if (strtolower((string) $request->query('record_scope')) === 'other') {
            return redirect()->route('funeral-cases.other-reports');
        }

        return redirect()->route('funeral-cases.index', array_filter([
            'tab' => 'completed',
            'q' => $request->query('q'),
            'payment_status' => $request->query('payment_status'),
            'date_range' => $request->query('date_range'),
            'request_date_from' => $request->query('request_date_from'),
            'request_date_to' => $request->query('request_date_to'),
            'sort' => $request->query('sort'),
            'quick_filter' => $request->query('quick_filter'),
        ], fn ($value) => !is_null($value) && $value !== ''));
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
            'service_date_asc' => 'Service Date: Earliest',
            'service_date_desc' => 'Service Date: Latest',
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
        $scopeBranchIds = $user->branchScopeIds();
        $canEncodeAnyBranch = $user->canEncodeAnyBranch();

        if (!$canEncodeAnyBranch) {
            return redirect()
                ->back()
                ->with('warning', 'You need permission from the admin to view other-branch reports.');
        }

        $scopeBranches = Branch::whereIn('id', $scopeBranchIds)->orderBy('branch_code')->get();
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
                'reporter_name',
                'reported_at',
                'verification_status',
            ])
            ->with([
                'client:id,full_name',
                'deceased:id,full_name,interment_at,interment',
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
        $user = auth()->user();

        if (!in_array((int) $funeral_case->branch_id, $user->branchScopeIds(), true)) {
            abort(403);
        }

        if (($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH' && !$user->canEncodeAnyBranch()) {
            abort(403);
        }

        $funeral_case->load(['client', 'deceased', 'branch', 'reportedBranch', 'encodedBy', 'payments.recordedBy']);

        return view('staff.funeral_cases.show', compact('funeral_case'));
    }

    public function create()
    {
        $scopeBranchIds = auth()->user()->branchScopeIds();
        $defaultBranchId = (int) auth()->user()->branch_id;

        $clients = Client::whereIn('branch_id', $scopeBranchIds)
            ->orderBy('full_name')
            ->get();

        $deceaseds = Deceased::whereIn('branch_id', $scopeBranchIds)
            ->orderBy('full_name')
            ->get();

        $packages = Package::where('is_active', true)
            ->orderBy('name')
            ->get();
        $nextCode = $this->nextCaseCode($defaultBranchId);
        $nextCodeMap = Branch::whereIn('id', $scopeBranchIds)
            ->orderBy('branch_code')
            ->get()
            ->mapWithKeys(function ($branch) {
                return [$branch->id => $this->nextCaseCode((int) $branch->id)];
            });

        return view('staff.funeral_cases.create', compact('clients', 'deceaseds', 'packages', 'nextCode', 'nextCodeMap'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'deceased_id' => 'required|exists:deceased,id',
            'package_id' => 'required|integer|exists:packages,id',
            'case_status' => 'required|in:DRAFT,ACTIVE,COMPLETED',
        ]);

        $scopeBranchIds = auth()->user()->branchScopeIds();
        $defaultBranchId = (int) auth()->user()->branch_id;

        $client = Client::find($validated['client_id']);
        $deceased = Deceased::find($validated['deceased_id']);

        if (!$client || !in_array((int) $client->branch_id, $scopeBranchIds, true)) {
            abort(403);
        }
        if (!$deceased || !in_array((int) $deceased->branch_id, $scopeBranchIds, true)) {
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

        $entrySource = optional(Branch::find($client->branch_id))->branch_code === 'BR001' ? 'MAIN' : 'OTHER_BRANCH';
        if ($entrySource === 'OTHER_BRANCH') {
            return back()->withErrors([
                'client_id' => 'Direct case creation is for main-branch intake only. Use Other Branch Intake for completed branch reports.',
            ])->withInput();
        }

        $subtotal = (float) $package->price;
        $discountPayload = app(CaseDiscountResolver::class)
            ->resolve($package, $this->resolveDeceasedAge($deceased), $subtotal, now());
        $discount = (float) $discountPayload['discount_amount'];
        $total = round(max($subtotal - $discount, 0), 2);
        $payment = $this->computePaymentFields($total, 0);
        $serviceRequestedAt = now()->toDateString();
        $wakeLocation = $this->resolveLegacyWakeLocation($client);
        $funeralServiceAt = $this->resolveLegacyFuneralServiceDate($deceased, $serviceRequestedAt);

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

        $case = FuneralCase::create([
            'branch_id' => $client->branch_id,
            'client_id' => $client->id,
            'deceased_id' => $deceased->id,
            'package_id' => $package->id,
            'case_code' => $this->nextCaseCode((int) $client->branch_id),
            'service_requested_at' => $serviceRequestedAt,
            'service_package' => $package->name,
            'coffin_type' => $package->coffin_type,
            'wake_location' => $wakeLocation,
            'funeral_service_at' => $funeralServiceAt,
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
            'encoded_by' => auth()->id(),
            'reported_branch_id' => $client->branch_id,
            'reported_at' => now(),
            'entry_source' => $entrySource,
            'verification_status' => 'VERIFIED',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'verification_note' => 'Auto-verified main-branch case creation.',
        ]);

        AuditLogger::log(
            'case.created',
            'create',
            'funeral_case',
            $case->id,
            [
                'case_status' => $case->case_status,
                'payment_status' => $case->payment_status,
                'entry_source' => $case->entry_source,
                'package_id' => $case->package_id,
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
        $scopeBranchIds = auth()->user()->branchScopeIds();
        if (!in_array((int) $funeral_case->branch_id, $scopeBranchIds, true)) {
            abort(403);
        }
        if (($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH') {
            return redirect()
                ->route('funeral-cases.show', $funeral_case)
                ->withErrors(['case' => 'Other-branch reported cases are locked. Use admin verification workflow for review.']);
        }

        $clients = Client::whereIn('branch_id', $scopeBranchIds)
            ->orderBy('full_name')
            ->get();

        $deceaseds = Deceased::whereIn('branch_id', $scopeBranchIds)
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

        $scopeBranchIds = auth()->user()->branchScopeIds();
        if (!in_array((int) $funeral_case->branch_id, $scopeBranchIds, true)) {
            abort(403);
        }
        if (($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH') {
            return back()->withErrors([
                'case' => 'Other-branch reported cases are locked and cannot be edited from case management.',
            ]);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'deceased_id' => 'required|exists:deceased,id',
            'package_id' => 'required|integer|exists:packages,id',
            'case_status' => 'required|in:DRAFT,ACTIVE,COMPLETED',
        ]);

        $client = Client::find($validated['client_id']);
        $deceased = Deceased::find($validated['deceased_id']);

        if (!$client || !in_array((int) $client->branch_id, $scopeBranchIds, true)) {
            abort(403);
        }
        if (!$deceased || !in_array((int) $deceased->branch_id, $scopeBranchIds, true)) {
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

        $entrySource = optional(Branch::find($client->branch_id))->branch_code === 'BR001' ? 'MAIN' : 'OTHER_BRANCH';
        if ($entrySource === 'OTHER_BRANCH') {
            return back()->withErrors([
                'client_id' => 'Direct case management edits are for main-branch records only.',
            ])->withInput();
        }

        $subtotal = (float) $package->price;
        $discountPayload = app(CaseDiscountResolver::class)
            ->resolve($package, $this->resolveDeceasedAge($deceased), $subtotal, now());
        $discount = (float) $discountPayload['discount_amount'];
        $total = round(max($subtotal - $discount, 0), 2);
        $serviceRequestedAt = $funeral_case->service_requested_at?->toDateString() ?: now()->toDateString();
        $wakeLocation = $funeral_case->wake_location ?: $this->resolveLegacyWakeLocation($client);
        $funeralServiceAt = $funeral_case->funeral_service_at?->toDateString()
            ?: $this->resolveLegacyFuneralServiceDate($deceased, $serviceRequestedAt);

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
            'funeral_service_at' => $funeralServiceAt,
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
        if (!in_array((int) $funeral_case->branch_id, auth()->user()->branchScopeIds(), true)) {
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
}
