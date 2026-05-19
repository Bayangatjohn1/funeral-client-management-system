<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Discount\CaseDiscountResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function masterCases(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'payment_status' => ['nullable', 'in:PAID,PARTIAL,UNPAID'],
            'case_status' => ['nullable', 'in:DRAFT,ACTIVE,COMPLETED'],
            'verification_status' => ['nullable', 'in:PENDING,VERIFIED,DISPUTED'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'encoded_by' => ['nullable', 'integer', 'exists:users,id'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'date_preset' => ['nullable', 'in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $requestedBranchId = $validated['branch_id'] ?? null;
        $originalRequestedBranchId = $requestedBranchId;
        if ($request->user()?->isBranchAdmin()) {
            $requestedBranchId = $request->user()->branch_id;
        }
        $scopeBranchIds = $request->user()->branchScopeIds();
        if ($originalRequestedBranchId && $scopeBranchIds !== null && !in_array((int) $originalRequestedBranchId, $scopeBranchIds, true)) {
            abort(403, 'Branch is outside your admin scope.');
        }
        $branchId = $this->effectiveBranchId($request, $requestedBranchId);
        $q = $validated['q'] ?? null;
        $paymentStatus = $validated['payment_status'] ?? null;
        $caseStatus = $validated['case_status'] ?? null;
        $verificationStatus = $validated['verification_status'] ?? null;
        $serviceType = $validated['service_type'] ?? null;
        $packageId = $validated['package_id'] ?? null;
        $encodedBy = $validated['encoded_by'] ?? null;
        $sort = $validated['sort'] ?? 'newest';
        $dateFromInput = $validated['date_from'] ?? null;
        $dateToInput = $validated['date_to'] ?? null;
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;
        $datePreset = $validated['date_preset'] ?? (($dateFromInput || $dateToInput) ? 'CUSTOM' : '');
        if ($datePreset === '' && ($dateFromInput || $dateToInput)) {
            $datePreset = 'CUSTOM';
        }

        [$dateFrom, $dateTo] = [null, null];
        if ($datePreset === 'CUSTOM') {
            [$dateFrom, $dateTo] = [$dateFromInput, $dateToInput];
        } elseif ($datePreset !== '') {
            [$dateFrom, $dateTo] = match ($datePreset) {
                'TODAY' => [Carbon::today()->toDateString(), Carbon::today()->toDateString()],
                'THIS_MONTH' => [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->toDateString()],
                'THIS_YEAR' => [Carbon::today()->startOfYear()->toDateString(), Carbon::today()->toDateString()],
                default => [null, null],
            };
        }
        [$startAt, $endAt] = $this->parseDateBounds($dateFrom, $dateTo);

        $cases = FuneralCase::with(['branch', 'client', 'deceased', 'package', 'encodedBy'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($startAt, fn ($query) => $query->where('created_at', '>=', $startAt))
            ->when($endAt, fn ($query) => $query->where('created_at', '<=', $endAt))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($caseStatus, fn ($query) => $query->where('case_status', $caseStatus))
            ->when($verificationStatus, fn ($query) => $query->where('verification_status', $verificationStatus))
            ->when($serviceType, fn ($query) => $query->where('service_type', $serviceType))
            ->when($packageId, fn ($query) => $query->where('package_id', $packageId))
            ->when($encodedBy, fn ($query) => $query->where('encoded_by', $encodedBy))
            ->when($intermentFrom || $intermentTo, function ($query) use ($intermentFrom, $intermentTo) {
                $query->where(function ($outer) use ($intermentFrom, $intermentTo) {
                    $outer->where(function ($caseDate) use ($intermentFrom, $intermentTo) {
                        if ($intermentFrom) {
                            $caseDate->whereDate('interment_at', '>=', $intermentFrom);
                        }
                        if ($intermentTo) {
                            $caseDate->whereDate('interment_at', '<=', $intermentTo);
                        }
                    })->orWhereHas('deceased', function ($dq) use ($intermentFrom, $intermentTo) {
                        if ($intermentFrom) {
                            $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$intermentFrom]);
                        }
                        if ($intermentTo) {
                            $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$intermentTo]);
                        }
                    });
                });
            })
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('case_code', 'like', "%{$q}%")
                        ->orWhereHas('client', fn ($q2) => $q2->where('full_name', 'like', "%{$q}%"))
                        ->orWhereHas('deceased', fn ($q3) => $q3->where('full_name', 'like', "%{$q}%"));
                });
            })
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when($sort !== 'oldest', fn ($query) => $query->latest())
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::query()
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('id', $scopeBranchIds))
            ->orderBy('branch_code')
            ->get();

        $serviceTypes = FuneralCase::query()
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('branch_id', $scopeBranchIds))
            ->whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->distinct()
            ->orderBy('service_type')
            ->pluck('service_type');
        $packages = Package::query()->orderBy('name')->get(['id', 'name']);
        $encoders = User::query()
            ->whereIn('role', ['staff', 'admin'])
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('branch_id', $scopeBranchIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.reports.master_cases', compact(
            'cases',
            'branches',
            'branchId',
            'q',
            'paymentStatus',
            'caseStatus',
            'verificationStatus',
            'serviceType',
            'packageId',
            'encodedBy',
            'sort',
            'datePreset',
            'dateFrom',
            'dateTo',
            'intermentFrom',
            'intermentTo',
            'serviceTypes',
            'packages',
            'encoders'
        ));
    }

    public function editCase(Request $request, FuneralCase $funeral_case)
    {
        $user = $request->user();

        // Main Branch Admin may only edit cases that belong to their own (main) branch.
        if ($user->isMainBranchAdmin()) {
            $ownBranchId = $user->operationalBranchId();
            if ($ownBranchId === null || (int) $funeral_case->branch_id !== $ownBranchId) {
                abort(403, 'You can only edit case records that belong to your own branch.');
            }
        } else {
            $scopeBranchIds = $user->branchScopeIds();
            if (!in_array($funeral_case->branch_id, $scopeBranchIds)) {
                abort(403, 'This case is outside your admin scope.');
            }
        }

        $funeral_case->load(['client', 'deceased', 'branch', 'package', 'encodedBy']);

        $packages = Package::where('is_active', true)->orderBy('name')->get();

        $intermentPassed = $funeral_case->interment_at
            && $funeral_case->interment_at->copy()->startOfDay()->isPast();

        return view('admin.cases.edit', [
            'funeral_case'    => $funeral_case,
            'packages'        => $packages,
            'intermentPassed' => $intermentPassed,
            'returnTo'        => $request->query('return_to', route('admin.cases.index')),
        ]);
    }

    public function updateCase(Request $request, FuneralCase $funeral_case)
    {
        $user = $request->user();

        // Main Branch Admin may only update cases that belong to their own (main) branch.
        if ($user->isMainBranchAdmin()) {
            $ownBranchId = $user->operationalBranchId();
            if ($ownBranchId === null || (int) $funeral_case->branch_id !== $ownBranchId) {
                abort(403, 'You can only edit case records that belong to your own branch.');
            }
        } else {
            $scopeBranchIds = $user->branchScopeIds();
            if (!in_array($funeral_case->branch_id, $scopeBranchIds)) {
                abort(403, 'This case is outside your admin scope.');
            }
        }

        $validated = $request->validate([
            // Client fields
            'client_full_name'     => ['nullable', 'string', 'max:255'],
            'client_contact'       => ['nullable', 'string', 'max:50'],
            'client_relationship'  => ['nullable', 'string', 'max:100'],
            'client_address'       => ['nullable', 'string', 'max:500'],
            // Deceased fields
            'deceased_full_name'   => ['required', 'string', 'max:255'],
            'date_of_birth'        => ['nullable', 'date'],
            'date_of_death'        => ['nullable', 'date', 'before_or_equal:today'],
            'age'                  => ['nullable', 'integer', 'min:0', 'max:150'],
            'deceased_address'     => ['nullable', 'string', 'max:500'],
            'place_of_cemetery'    => ['nullable', 'string', 'max:255'],
            // Service & Package
            'package_id'           => ['required', 'integer', 'exists:packages,id'],
            'wake_location'        => ['nullable', 'string', 'max:255'],
            'wake_start_date'      => ['nullable', 'date'],
            'wake_start_time'      => ['nullable', 'date_format:H:i'],
            'funeral_service_at'   => ['nullable', 'date'],
            'funeral_service_time' => ['nullable', 'date_format:H:i'],
            'interment_at'         => ['nullable', 'date'],
            'interment_time'       => ['nullable', 'date_format:H:i'],
            'additional_services'  => ['nullable', 'string', 'max:1000'],
            // Case Management
            'case_status'          => ['required', 'in:ACTIVE,COMPLETED'],
            // Admin audit note
            'admin_note'           => ['nullable', 'string', 'max:1000'],
        ]);

        $client  = $funeral_case->client;
        $deceased = $funeral_case->deceased;

        // ── Interment check: block manual COMPLETED if interment hasn't passed ──
        if ($validated['case_status'] === 'COMPLETED') {
            $intermentCheck = isset($validated['interment_at']) && $validated['interment_at']
                ? Carbon::parse($validated['interment_at'])
                : $funeral_case->interment_at;
            if ($intermentCheck && $intermentCheck->copy()->startOfDay()->isFuture()) {
                return back()->withErrors([
                    'case_status' => 'Case cannot be manually set to Completed before the interment date ('
                        . $intermentCheck->format('M d, Y') . '). The status will update automatically once that date is reached.',
                ])->withInput();
            }
        }

        // ── Package & pricing ───────────────────────────────────────────────────
        $package = Package::where('id', $validated['package_id'])
            ->where('is_active', true)
            ->first();
        if (!$package) {
            return back()->withErrors(['package_id' => 'Selected package is unavailable or inactive.'])->withInput();
        }

        $subtotal = (float) $package->price;

        // Resolve age for discount check
        $deceasedAge = null;
        if (filled($validated['age'] ?? null)) {
            $deceasedAge = (int) $validated['age'];
        } elseif ($deceased) {
            $dob = filled($validated['date_of_birth'] ?? null) ? Carbon::parse($validated['date_of_birth']) : $deceased->born;
            $dod = filled($validated['date_of_death'] ?? null) ? Carbon::parse($validated['date_of_death']) : $deceased->died;
            if ($dob && $dod) {
                $deceasedAge = (int) $dob->diffInYears($dod);
            } elseif ($deceased->age !== null) {
                $deceasedAge = (int) $deceased->age;
            }
        }

        $discountPayload = app(CaseDiscountResolver::class)
            ->resolve($package, $deceasedAge, $subtotal, now());
        $discount = (float) $discountPayload['discount_amount'];
        $total    = round(max($subtotal - $discount, 0), 2);

        // ── Payment fields ──────────────────────────────────────────────────────
        $totalPaid = round(max((float) $funeral_case->total_paid, 0), 2);
        if ($total <= 0) {
            $payment = ['total_paid' => 0.0, 'balance' => 0.0, 'status' => 'PAID'];
        } elseif ($totalPaid >= $total) {
            $payment = ['total_paid' => $total, 'balance' => 0.0, 'status' => 'PAID'];
        } elseif ($totalPaid > 0) {
            $payment = ['total_paid' => $totalPaid, 'balance' => round($total - $totalPaid, 2), 'status' => 'PARTIAL'];
        } else {
            $payment = ['total_paid' => 0.0, 'balance' => $total, 'status' => 'UNPAID'];
        }

        // ── Schedule fields ─────────────────────────────────────────────────────
        $intermentAt = isset($validated['interment_at']) && $validated['interment_at']
            ? Carbon::parse($validated['interment_at'])
            : $funeral_case->interment_at;
        if ($intermentAt && isset($validated['interment_time']) && $validated['interment_time']) {
            [$h, $m] = explode(':', $validated['interment_time']);
            $intermentAt->setTime((int) $h, (int) $m, 0);
        }

        $funeralServiceAt = isset($validated['funeral_service_at']) && $validated['funeral_service_at']
            ? Carbon::parse($validated['funeral_service_at'])->toDateString()
            : $funeral_case->funeral_service_at?->toDateString();

        $wakeStartDate = isset($validated['wake_start_date']) && $validated['wake_start_date']
            ? Carbon::parse($validated['wake_start_date'])->toDateString()
            : $funeral_case->wake_start_date?->toDateString();

        $wakeDays = null;
        if ($wakeStartDate && $funeralServiceAt) {
            $diff = Carbon::parse($wakeStartDate)->diffInDays(Carbon::parse($funeralServiceAt), false);
            $wakeDays = $diff >= 0 ? (int) $diff : null;
        }

        $wakeLocation = filled($validated['wake_location'] ?? null)
            ? $validated['wake_location']
            : ($funeral_case->wake_location ?: 'Not specified');

        // ── Update client ───────────────────────────────────────────────────────
        if ($client) {
            $clientData = [
                'contact_number'           => $validated['client_contact'] ?? $client->contact_number,
                'relationship_to_deceased' => $validated['client_relationship'] ?? $client->relationship_to_deceased,
                'relationship'             => $validated['client_relationship'] ?? $client->relationship,
                'address'                  => $validated['client_address'] ?? $client->address,
            ];
            if (filled($validated['client_full_name'] ?? null)) {
                $clientData['full_name'] = $validated['client_full_name'];
            }
            $client->update($clientData);
        }

        // ── Update deceased ─────────────────────────────────────────────────────
        if ($deceased) {
            $deceasedData = [
                'full_name'        => $validated['deceased_full_name'],
                'age'              => $validated['age'] ?? $deceased->age,
                'address'          => $validated['deceased_address'] ?? $deceased->address,
                'place_of_cemetery' => $validated['place_of_cemetery'] ?? $deceased->place_of_cemetery,
            ];
            if (filled($validated['date_of_birth'] ?? null)) {
                $deceasedData['born'] = $validated['date_of_birth'];
            }
            if (filled($validated['date_of_death'] ?? null)) {
                $deceasedData['died']          = $validated['date_of_death'];
                $deceasedData['date_of_death'] = $validated['date_of_death'];
            }
            if ($intermentAt) {
                $deceasedData['interment']    = $intermentAt->toDateString();
                $deceasedData['interment_at'] = $intermentAt;
            }
            if ($wakeDays !== null) {
                $deceasedData['wake_days'] = $wakeDays;
            }
            $deceased->forceFill($deceasedData)->save();
        }

        // ── Update funeral case ─────────────────────────────────────────────────
        $funeral_case->update([
            'package_id'           => $package->id,
            'service_package'      => $package->name,
            'coffin_type'          => $package->coffin_type,
            'wake_location'        => $wakeLocation,
            'wake_start_date'      => $wakeStartDate,
            'wake_start_time'      => isset($validated['wake_start_time']) ? $validated['wake_start_time'] : $funeral_case->wake_start_time,
            'funeral_service_at'   => $funeralServiceAt,
            'funeral_service_time' => isset($validated['funeral_service_time']) ? $validated['funeral_service_time'] : $funeral_case->funeral_service_time,
            'interment_at'         => $intermentAt,
            'interment_time'       => isset($validated['interment_time']) ? $validated['interment_time'] : $funeral_case->interment_time,
            'subtotal_amount'      => $subtotal,
            'discount_type'        => $discountPayload['discount_type'],
            'discount_value_type'  => $discountPayload['discount_value_type'],
            'discount_value'       => $discountPayload['discount_value'],
            'discount_amount'      => $discount,
            'discount_note'        => $discountPayload['discount_note'],
            'total_amount'         => $total,
            'total_paid'           => $payment['total_paid'],
            'balance_amount'       => $payment['balance'],
            'payment_status'       => $payment['status'],
            'case_status'          => $validated['case_status'],
            'additional_services'  => $validated['additional_services'] ?? $funeral_case->additional_services,
            'verification_status'  => 'VERIFIED',
            'verified_by'          => $user->id,
            'verified_at'          => now(),
            'verification_note'    => 'Admin case update by ' . $user->name . '.',
        ]);

        // ── Update service detail ───────────────────────────────────────────────
        $funeral_case->serviceDetail()->updateOrCreate(
            ['funeral_case_id' => $funeral_case->id],
            [
                'start_of_wake'   => $wakeStartDate ?? $funeralServiceAt,
                'internment_date' => $intermentAt?->toDateString(),
                'wake_location'   => $wakeLocation,
                'wake_days'       => $wakeDays,
                'cemetery_place'  => $validated['place_of_cemetery'] ?? $deceased?->place_of_cemetery,
                'case_status'     => match ($funeral_case->case_status) {
                    'ACTIVE'     => 'ongoing',
                    'COMPLETED'  => 'completed',
                    default      => 'pending',
                },
            ]
        );

        AuditLogger::log(
            action: 'case.admin_updated',
            actionType: 'update',
            entityType: 'funeral_case',
            entityId: $funeral_case->id,
            metadata: [
                'case_code'   => $funeral_case->case_code,
                'updated_by'  => $user->name,
                'case_status' => $funeral_case->case_status,
                'package'     => $package->name,
                'note'        => $validated['admin_note'] ?? null,
            ],
            branchId: $funeral_case->branch_id
        );

        $returnTo = $request->input('return_to');
        $redirect = ($returnTo && str_starts_with($returnTo, url('/')))
            ? $returnTo
            : route('admin.cases.index');

        return redirect()->to($redirect)->with('success', 'Case record updated successfully.');
    }

    public function updateVerification(Request $request, FuneralCase $funeral_case)
    {
        $validated = $request->validate([
            'verification_status' => ['required', 'in:VERIFIED,DISPUTED'],
            'verification_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($funeral_case->entry_source !== 'OTHER_BRANCH') {
            return back()->withErrors([
                'verification' => 'Only other-branch records require verification workflow.',
            ]);
        }

        if ($validated['verification_status'] === 'VERIFIED') {
            if ($funeral_case->case_status !== 'COMPLETED' || $funeral_case->payment_status !== 'PAID') {
                return back()->withErrors([
                    'verification' => 'Case must be completed and fully paid before verification.',
                ]);
            }

            $funeral_case->update([
                'verification_status' => 'VERIFIED',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_note' => $validated['verification_note'] ?: 'Verified by admin.',
            ]);

            AuditLogger::log(
                action: 'case.verified',
                actionType: 'status_change',
                entityType: 'funeral_case',
                entityId: $funeral_case->id,
                metadata: [
                    'case_code' => $funeral_case->case_code,
                    'verification_status' => 'VERIFIED',
                    'note' => $funeral_case->verification_note,
                ],
                branchId: $funeral_case->branch_id
            );

            return back()->with('success', 'Case verification marked as VERIFIED.');
        }

        if (empty($validated['verification_note'])) {
            return back()->withErrors([
                'verification' => 'Please provide verification note for disputed records.',
            ]);
        }

        $funeral_case->update([
            'verification_status' => 'DISPUTED',
            'verified_by' => null,
            'verified_at' => null,
            'verification_note' => $validated['verification_note'],
        ]);

        AuditLogger::log(
            action: 'case.disputed',
            actionType: 'status_change',
            entityType: 'funeral_case',
            entityId: $funeral_case->id,
            metadata: [
                'case_code' => $funeral_case->case_code,
                'verification_status' => 'DISPUTED',
                'note' => $validated['verification_note'],
            ],
            branchId: $funeral_case->branch_id
        );

        return back()->with('success', 'Case verification marked as DISPUTED.');
    }

    public function sales(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date_preset' => ['nullable', 'in:ANY,TODAY,LAST_7_DAYS,LAST_30_DAYS,THIS_MONTH,CUSTOM'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
        ]);

        $requestedBranchId = $validated['branch_id'] ?? null;
        $scopeBranchIds = $request->user()->branchScopeIds();
        if ($requestedBranchId && $scopeBranchIds !== null && !in_array((int) $requestedBranchId, $scopeBranchIds, true)) {
            abort(403, 'Branch is outside your admin scope.');
        }
        $branchId = $this->effectiveBranchId($request, $requestedBranchId);
        $dateFromInput = $validated['date_from'] ?? null;
        $dateToInput = $validated['date_to'] ?? null;
        $dateFrom = null;
        $dateTo = null;
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;
        $datePreset = $validated['date_preset'] ?? (($dateFromInput || $dateToInput) ? 'CUSTOM' : 'THIS_MONTH');

        if ($datePreset === 'CUSTOM') {
            $dateFrom = $dateFromInput;
            $dateTo = $dateToInput;
        } elseif ($datePreset !== 'ANY') {
            [$dateFrom, $dateTo] = match ($datePreset) {
                'TODAY' => [Carbon::today()->toDateString(), Carbon::today()->toDateString()],
                'LAST_7_DAYS' => [Carbon::today()->subDays(6)->toDateString(), Carbon::today()->toDateString()],
                'LAST_30_DAYS' => [Carbon::today()->subDays(29)->toDateString(), Carbon::today()->toDateString()],
                'THIS_MONTH' => [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->toDateString()],
                default => [null, null],
            };
        }

        [$startAt, $endAt] = $this->parseDateBounds($dateFrom, $dateTo);

        $intermentFilter = function ($query) use ($intermentFrom, $intermentTo) {
            $query->whereHas('deceased', function ($dq) use ($intermentFrom, $intermentTo) {
                if ($intermentFrom) {
                    $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$intermentFrom]);
                }
                if ($intermentTo) {
                    $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$intermentTo]);
                }
            });
        };

        // Base query without branch filter — used for per-branch aggregates.
        $allBase = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->when($startAt, fn ($query) => $query->where('created_at', '>=', $startAt))
            ->when($endAt, fn ($query) => $query->where('created_at', '<=', $endAt))
            ->when($intermentFrom || $intermentTo, $intermentFilter);

        $base = (clone $allBase)->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        // Single query for all summary KPIs.
        $summary = (clone $base)
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'UNPAID' THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END), 0) as total_sales")
            ->selectRaw('COALESCE(SUM(total_paid), 0) as total_collected')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as total_outstanding')
            ->first();
        $totalCases       = (int)   ($summary->total_cases      ?? 0);
        $paidCases        = (int)   ($summary->paid_cases       ?? 0);
        $partialCases     = (int)   ($summary->partial_cases    ?? 0);
        $unpaidCases      = (int)   ($summary->unpaid_cases     ?? 0);
        $totalSales       = (float) ($summary->total_sales      ?? 0);
        $totalCollected   = (float) ($summary->total_collected  ?? 0);
        $totalOutstanding = (float) ($summary->total_outstanding ?? 0);

        // Single grouped query for all branches — replaces N×7 individual queries.
        $branches = Branch::query()
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('id', $scopeBranchIds))
            ->orderBy('branch_code')
            ->get();
        $branchAggregates = (clone $allBase)
            ->select('branch_id')
            ->selectRaw('COUNT(*) as cases')
            ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'UNPAID' THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END), 0) as sales")
            ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $branchSales = $branches->map(function ($branch) use ($branchAggregates) {
            $row = $branchAggregates->get($branch->id);
            return [
                'branch'        => $branch,
                'cases'         => (int)   ($row->cases         ?? 0),
                'paid_cases'    => (int)   ($row->paid_cases    ?? 0),
                'partial_cases' => (int)   ($row->partial_cases ?? 0),
                'unpaid_cases'  => (int)   ($row->unpaid_cases  ?? 0),
                'sales'         => (float) ($row->sales         ?? 0),
                'collected'     => (float) ($row->collected     ?? 0),
                'outstanding'   => (float) ($row->outstanding   ?? 0),
            ];
        });

        return view('admin.reports.sales', compact(
            'branches',
            'branchId',
            'datePreset',
            'dateFrom',
            'dateTo',
            'dateFromInput',
            'dateToInput',
            'intermentFrom',
            'intermentTo',
            'totalCases',
            'paidCases',
            'partialCases',
            'unpaidCases',
            'totalSales',
            'totalCollected',
            'totalOutstanding',
            'branchSales'
        ));
    }

    private function effectiveBranchId(Request $request, mixed $requestedBranchId): ?int
    {
        $user = $request->user();

        if ($user?->isBranchAdmin()) {
            return $user->branch_id ? (int) $user->branch_id : null;
        }

        return filled($requestedBranchId) ? (int) $requestedBranchId : null;
    }
}
