<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORT_SALES = 'sales';
    private const REPORT_MASTER_CASES = 'master_cases';
    private const REPORT_AUDIT_LOGS = 'audit_logs';
    private const REPORT_OWNER_BRANCH_ANALYTICS = 'owner_branch_analytics';

    public function index(Request $request)
    {
        $this->authorizeReports();

        $user = auth()->user();
        $availableReportTypes = $this->availableReportTypes();
        $requestedReportType = $request->string('report_type')->toString();
        $fallbackReportType = $user->isOwner() ? self::REPORT_OWNER_BRANCH_ANALYTICS : self::REPORT_SALES;
        $defaultReportType = array_key_exists($requestedReportType, $availableReportTypes)
            ? $requestedReportType
            : $fallbackReportType;

        return view('reports.index', [
            'defaultReportType' => $defaultReportType,
            'reportTypes' => $availableReportTypes,
            'branches' => $this->reportBranches($user),
            'packages' => Package::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name', 'role']),
            'auditOptions' => $this->auditFilterOptions(),
            'userRole' => $user->role,
            'isBranchAdmin' => $user->isBranchAdmin(),
            'assignedBranchId' => $user->isBranchAdmin() ? (int) $user->branch_id : null,
            'assignedBranchLabel' => $user->isBranchAdmin() ? $this->branchName($user->branch) : null,
        ]);
    }

    public function preview(Request $request)
    {
        $this->authorizeReports($request->input('report_type'));

        $validated = $this->validateReportRequest($request);
        $branchScope = $this->reportBranchScope($request);
        $data = $this->resolveReportData($request, $branchScope);

        return response()->json([
            'report_type' => $validated['report_type'],
            'rows' => $data['rows'],
            'summary' => $this->getSummary($data['rows'], $validated['report_type']),
            'filters' => $this->presentFilters($validated, $branchScope),
        ]);
    }

    public function ownerDrilldown(Request $request)
    {
        $this->authorizeReports('owner_branch_analytics');

        $metric = $request->input('metric');
        if (! in_array($metric, $this->validDrilldownMetrics(), true)) {
            return response()->json(['error' => 'Invalid metric.'], 422);
        }

        $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $branchScope = $this->reportBranchScope($request);
        $data = $this->resolveOwnerDrilldownData($request, $branchScope, $metric);

        return response()->json([
            'rows'   => $data['rows'],
            'mode'   => $data['mode'],
            'metric' => $metric,
        ]);
    }

    public function print(Request $request)
    {
        $this->authorizeReports($request->input('report_type'));

        $validated    = $this->validateReportRequest($request);
        $branchScope  = $this->reportBranchScope($request);
        $metric       = $request->input('metric');
        $isDrilldown  = $validated['report_type'] === self::REPORT_OWNER_BRANCH_ANALYTICS
                        && in_array($metric, $this->validDrilldownMetrics(), true);

        if ($isDrilldown) {
            $drillData       = $this->resolveOwnerDrilldownData($request, $branchScope, $metric);
            $rows            = $drillData['rows'];
            $summary         = $this->getDrilldownSummary($drillData);
            $drilldownCols   = $drillData['columns'];
        } else {
            $data            = $this->resolveReportData($request, $branchScope);
            $rows            = $data['rows'];
            $summary         = $this->getSummary($data['rows'], $validated['report_type']);
            $drilldownCols   = null;
        }

        $titleSuffix = $isDrilldown ? ' — ' . $this->metricLabel($metric) : '';

        return view('reports.print', [
            'reportType'       => $validated['report_type'],
            'reportTitle'      => $this->reportTitle($validated['report_type']) . $titleSuffix,
            'rows'             => $rows,
            'summary'          => $summary,
            'filters'          => $this->presentFilters($validated, $branchScope),
            'generatedBy'      => auth()->user(),
            'generatedAt'      => now(),
            'drilldownColumns' => $drilldownCols,
        ]);
    }

    public function exportPdf(Request $request)
    {
        return $this->print($request);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorizeReports($request->input('report_type'));

        $validated   = $this->validateReportRequest($request);
        $branchScope = $this->reportBranchScope($request);
        $metric      = $request->input('metric');
        $isDrilldown = $validated['report_type'] === self::REPORT_OWNER_BRANCH_ANALYTICS
                       && in_array($metric, $this->validDrilldownMetrics(), true);

        if ($isDrilldown) {
            $drillData = $this->resolveOwnerDrilldownData($request, $branchScope, $metric);
            $rows      = $drillData['rows'];
            $columns   = $drillData['columns'];
            $fileName  = $validated['report_type'] . '-' . $metric . '-' . now()->format('Ymd-His') . '.csv';
        } else {
            $data     = $this->resolveReportData($request, $branchScope);
            $rows     = $data['rows'];
            $columns  = $this->reportColumns($validated['report_type']);
            $fileName = $validated['report_type'] . '-' . now()->format('Ymd-His') . '.csv';
        }

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_values($columns));

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn ($key) => $row[$key] ?? '',
                    array_keys($columns)
                ));
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function validateReportRequest(Request $request): array
    {
        $reportTypes = array_keys($this->availableReportTypes());

        $validated = $request->validate([
            'report_type' => ['required', 'string', Rule::in($reportTypes)],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'payment_status' => ['nullable', Rule::in(['PAID', 'PARTIAL', 'UNPAID'])],
            'case_status' => ['nullable', Rule::in(['DRAFT', 'ACTIVE', 'COMPLETED'])],
            'verification_status' => ['nullable', Rule::in(['PENDING', 'VERIFIED', 'DISPUTED'])],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'encoded_by' => ['nullable', 'integer', 'exists:users,id'],
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:120'],
            'module' => ['nullable', 'string', 'max:120'],
        ]);

        return array_filter($validated, fn ($value) => $value !== null && $value !== '');
    }

    private function resolveReportData(Request $request, array $branchScope): array
    {
        return match ($this->normalizeReportType($request->input('report_type'))) {
            self::REPORT_SALES => $this->getSalesReportData($request, $branchScope),
            self::REPORT_MASTER_CASES => $this->getMasterCasesReportData($request, $branchScope),
            self::REPORT_AUDIT_LOGS => $this->getAuditLogsReportData($request, $branchScope),
            self::REPORT_OWNER_BRANCH_ANALYTICS => $this->getOwnerBranchAnalyticsData($request, $branchScope),
        };
    }

    private function getSalesReportData(Request $request, array $branchScope): array
    {
        $query = FuneralCase::with(['branch', 'client', 'deceased', 'package'])
            ->latest('created_at');

        $this->applyCommonCaseFilters($query, $request, $branchScope);

        $rows = $query->get()->map(fn (FuneralCase $case) => [
            'case_no' => $case->case_number ?: $case->case_code,
            'client' => $this->personName($case->client),
            'deceased' => $this->personName($case->deceased),
            'branch' => $this->branchName($case->branch),
            'package' => $this->packageName($case),
            'service_type' => $case->service_type ?: '-',
            'total_amount' => (float) $case->total_amount,
            'total_paid' => (float) $case->total_paid,
            'balance' => (float) $case->balance_amount,
            'payment_status' => $case->payment_status ?: '-',
            'case_status' => $case->case_status ?: '-',
            'date' => $this->formatDate($case->paid_at ?: $case->created_at),
            '_sort_date' => optional($case->paid_at ?: $case->created_at)->toDateTimeString(),
        ])->values();

        return ['rows' => $rows];
    }

    private function getMasterCasesReportData(Request $request, array $branchScope): array
    {
        $query = FuneralCase::with(['branch', 'client', 'deceased', 'package', 'encodedBy'])
            ->latest('created_at');

        $this->applyCommonCaseFilters($query, $request, $branchScope);

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->string('verification_status'));
        }
        if ($request->filled('encoded_by')) {
            $query->where('encoded_by', (int) $request->input('encoded_by'));
        }

        [$intermentStart, $intermentEnd] = $this->parseDateBounds(
            $request->filled('interment_from') ? $request->input('interment_from') : null,
            $request->filled('interment_to') ? $request->input('interment_to') : null,
        );
        if ($intermentStart) {
            $query->where('interment_at', '>=', $intermentStart);
        }
        if ($intermentEnd) {
            $query->where('interment_at', '<=', $intermentEnd);
        }

        $rows = $query->get()->map(fn (FuneralCase $case) => [
            'case_no' => $case->case_number ?: $case->case_code,
            'case_code' => $case->case_code ?: '-',
            'client' => $this->personName($case->client),
            'deceased' => $this->personName($case->deceased),
            'branch' => $this->branchName($case->branch),
            'service_type' => $case->service_type ?: '-',
            'package' => $this->packageName($case),
            'interment_date' => $this->formatDate($case->interment_at),
            'payment_status' => $case->payment_status ?: '-',
            'case_status' => $case->case_status ?: '-',
            'verification_status' => $case->verification_status ?: '-',
            'encoded_by' => $this->personName($case->encodedBy),
            'date_created' => $this->formatDate($case->created_at),
            'total_amount' => (float) $case->total_amount,
            'total_paid' => (float) $case->total_paid,
            'balance' => (float) $case->balance_amount,
        ])->values();

        return ['rows' => $rows];
    }

    private function getAuditLogsReportData(Request $request, array $branchScope): array
    {
        $query = AuditLog::with(['actor:id,name,first_name,last_name,role', 'branch:id,branch_code,branch_name'])
            ->latest('created_at');

        if (Schema::hasColumn('audit_logs', 'branch_id')) {
            $this->applyReportBranchScope($query, $request, $branchScope, 'branch_id');
        }

        if ($request->filled('user_id') && Schema::hasColumn('audit_logs', 'actor_id')) {
            $query->where('actor_id', (int) $request->input('user_id'));
        }
        if ($request->filled('action') && Schema::hasColumn('audit_logs', 'action')) {
            $query->where('action', 'like', '%' . $request->input('action') . '%');
        }
        if ($request->filled('module') && Schema::hasColumn('audit_logs', 'entity_type')) {
            $query->where('entity_type', 'like', '%' . $request->input('module') . '%');
        }

        [$startAt, $endAt] = $this->parseDateBounds(
            $request->filled('date_from') ? $request->input('date_from') : null,
            $request->filled('date_to') ? $request->input('date_to') : null,
        );
        if ($startAt) {
            $query->where('created_at', '>=', $startAt);
        }
        if ($endAt) {
            $query->where('created_at', '<=', $endAt);
        }

        $rows = $query->get()->map(fn (AuditLog $log) => [
            'date' => $this->formatDateTime($log->created_at),
            'user' => $this->personName($log->actor),
            'role' => $log->actor_role ?: ($log->actor?->role ?? '-'),
            'action' => $log->action_label ?: ($log->action ?? '-'),
            'action_type' => $log->action_type ?: '-',
            'module' => $log->entity_type ?: '-',
            'record_id' => $log->entity_id ?: '-',
            'branch' => $this->branchName($log->branch),
            'status' => $log->status ?: '-',
            'remarks' => $log->remarks ?: '-',
        ])->values();

        return ['rows' => $rows];
    }

    private function getOwnerBranchAnalyticsData(Request $request, array $branchScope): array
    {
        $query = FuneralCase::query()
            ->select('branch_id')
            ->where('verification_status', 'VERIFIED')
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'UNPAID' THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw('COALESCE(SUM(total_amount), 0) as gross_amount')
            ->selectRaw('COALESCE(SUM(total_paid), 0) as collected_amount')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as remaining_balance')
            ->with('branch:id,branch_code,branch_name')
            ->groupBy('branch_id')
            ->orderBy('branch_id');

        $this->applyReportBranchScope($query, $request, $branchScope);

        [$startAt, $endAt] = $this->parseDateBounds(
            $request->filled('date_from') ? $request->input('date_from') : null,
            $request->filled('date_to') ? $request->input('date_to') : null,
        );
        if ($startAt) {
            $query->where('created_at', '>=', $startAt);
        }
        if ($endAt) {
            $query->where('created_at', '<=', $endAt);
        }

        [$intermentStart, $intermentEnd] = $this->parseDateBounds(
            $request->filled('interment_from') ? $request->input('interment_from') : null,
            $request->filled('interment_to') ? $request->input('interment_to') : null,
        );
        if ($intermentStart) {
            $query->where('interment_at', '>=', $intermentStart);
        }
        if ($intermentEnd) {
            $query->where('interment_at', '<=', $intermentEnd);
        }

        $rows = $query->get()->map(fn ($row) => [
            'branch' => $this->branchName($row->branch),
            'total_cases' => (int) $row->total_cases,
            'paid_cases' => (int) $row->paid_cases,
            'partial_cases' => (int) $row->partial_cases,
            'unpaid_cases' => (int) $row->unpaid_cases,
            'gross_amount' => (float) $row->gross_amount,
            'collected_amount' => (float) $row->collected_amount,
            'remaining_balance' => (float) $row->remaining_balance,
        ])->values();

        return ['rows' => $rows];
    }

    // ── Owner analytics drill-down ─────────────────────────────────────────

    private function validDrilldownMetrics(): array
    {
        return ['total_cases', 'paid_cases', 'partial_cases', 'unpaid_cases', 'gross_amount', 'collected_amount', 'remaining_balance'];
    }

    private function resolveOwnerDrilldownData(Request $request, array $branchScope, string $metric): array
    {
        if ($metric === 'collected_amount') {
            $rows = $this->getOwnerDrilldownPayments($request, $branchScope);
            return ['rows' => $rows, 'mode' => 'payments', 'columns' => $this->ownerDrilldownPaymentColumns()];
        }

        $rows = $this->getOwnerDrilldownCases($request, $branchScope, $metric);
        return ['rows' => $rows, 'mode' => 'cases', 'columns' => $this->ownerDrilldownCaseColumns()];
    }

    private function getOwnerDrilldownCases(Request $request, array $branchScope, string $metric): \Illuminate\Support\Collection
    {
        $query = FuneralCase::with(['branch:id,branch_code,branch_name', 'client', 'deceased'])
            ->where('verification_status', 'VERIFIED')
            ->latest('created_at');

        $this->applyReportBranchScope($query, $request, $branchScope);

        [$startAt, $endAt] = $this->parseDateBounds(
            $request->filled('date_from') ? $request->input('date_from') : null,
            $request->filled('date_to') ? $request->input('date_to') : null,
        );
        if ($startAt) {
            $query->where('created_at', '>=', $startAt);
        }
        if ($endAt) {
            $query->where('created_at', '<=', $endAt);
        }

        match ($metric) {
            'paid_cases'        => $query->where('payment_status', 'PAID'),
            'partial_cases'     => $query->where('payment_status', 'PARTIAL'),
            'unpaid_cases'      => $query->where('payment_status', 'UNPAID'),
            'gross_amount'      => $query->where('total_amount', '>', 0),
            'remaining_balance' => $query->where('balance_amount', '>', 0),
            default             => null, // total_cases: all verified cases in scope
        };

        return $query->get()->map(fn (FuneralCase $case) => [
            'case_no'           => $case->case_number ?: $case->case_code,
            'branch'            => $this->branchName($case->branch),
            'client'            => $this->personName($case->client),
            'deceased'          => $this->personName($case->deceased),
            'service'           => $case->service_type ?: '-',
            'payment_status'    => $case->payment_status ?: '-',
            'gross_amount'      => (float) $case->total_amount,
            'collected_amount'  => (float) $case->total_paid,
            'remaining_balance' => (float) $case->balance_amount,
            'last_payment_date' => $this->formatDate($case->paid_at),
        ])->values();
    }

    private function getOwnerDrilldownPayments(Request $request, array $branchScope): \Illuminate\Support\Collection
    {
        $query = \App\Models\Payment::with([
                'funeralCase:id,case_number,case_code,branch_id',
                'funeralCase.branch:id,branch_code,branch_name',
                'funeralCase.client',
                'funeralCase.deceased',
            ])
            ->where(fn ($q) => $q->where('status', 'VALID')->orWhereNull('status'))
            ->latest('paid_at');

        // Apply branch scope on the payments table
        if ($branchScope['forced_branch_id'] ?? null) {
            $query->where('branch_id', (int) $branchScope['forced_branch_id']);
        } elseif (($branchScope['can_select_all'] ?? false) && $request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        // Date filter on paid_at
        [$startAt, $endAt] = $this->parseDateBounds(
            $request->filled('date_from') ? $request->input('date_from') : null,
            $request->filled('date_to') ? $request->input('date_to') : null,
        );
        if ($startAt) {
            $query->where('paid_at', '>=', $startAt);
        }
        if ($endAt) {
            $query->where('paid_at', '<=', $endAt);
        }

        // Only payments for VERIFIED cases
        $query->whereHas('funeralCase', fn ($q) => $q->where('verification_status', 'VERIFIED'));

        return $query->get()->map(function (\App\Models\Payment $pay) {
            $client   = $this->personName($pay->funeralCase?->client);
            $deceased = $this->personName($pay->funeralCase?->deceased);
            $parts    = array_filter([$client !== '-' ? $client : null, $deceased !== '-' ? $deceased : null]);

            return [
                'payment_record_no' => $pay->payment_record_no ?: ($pay->receipt_number ?: '-'),
                'case_no'           => $pay->funeralCase?->case_number ?: ($pay->funeralCase?->case_code ?? '-'),
                'branch'            => $this->branchName($pay->funeralCase?->branch),
                'client_deceased'   => implode(' / ', $parts) ?: '-',
                'payment_method'    => $pay->payment_method ?: ($pay->payment_mode ?: ($pay->method ?? '-')),
                'amount_paid'       => (float) $pay->amount,
                'payment_date'      => $this->formatDate($pay->paid_at ?? $pay->paid_date),
                'status'            => $pay->status ?? 'VALID',
            ];
        })->values();
    }

    private function ownerDrilldownCaseColumns(): array
    {
        return [
            'case_no'           => 'Case No.',
            'branch'            => 'Branch',
            'client'            => 'Client',
            'deceased'          => 'Deceased',
            'service'           => 'Service',
            'payment_status'    => 'Payment Status',
            'gross_amount'      => 'Gross Amount',
            'collected_amount'  => 'Collected Amount',
            'remaining_balance' => 'Remaining Balance',
            'last_payment_date' => 'Last Payment Date',
        ];
    }

    private function ownerDrilldownPaymentColumns(): array
    {
        return [
            'payment_record_no' => 'Payment Record No.',
            'case_no'           => 'Case No.',
            'branch'            => 'Branch',
            'client_deceased'   => 'Client / Deceased',
            'payment_method'    => 'Payment Method',
            'amount_paid'       => 'Amount Paid',
            'payment_date'      => 'Payment Date',
        ];
    }

    private function getDrilldownSummary(array $drillData): array
    {
        $rows = collect($drillData['rows']);

        if ($drillData['mode'] === 'payments') {
            return [
                'total_records'   => $rows->count(),
                'amount_paid'     => (float) $rows->sum('amount_paid'),
            ];
        }

        return [
            'total_records'    => $rows->count(),
            'gross_amount'     => (float) $rows->sum('gross_amount'),
            'collected_amount' => (float) $rows->sum('collected_amount'),
            'remaining_balance' => (float) $rows->sum('remaining_balance'),
        ];
    }

    private function metricLabel(string $metric): string
    {
        return [
            'total_cases'       => 'Total Cases',
            'paid_cases'        => 'Paid Cases',
            'partial_cases'     => 'Partial Cases',
            'unpaid_cases'      => 'Unpaid Cases',
            'gross_amount'      => 'Gross Amount',
            'collected_amount'  => 'Collected Amount',
            'remaining_balance' => 'Remaining Balance',
        ][$metric] ?? $metric;
    }

    // ── End drill-down ─────────────────────────────────────────────────────

    private function getSummary($data, string $reportType): array
    {
        $rows = $data instanceof Collection ? $data : collect($data);

        if ($reportType === self::REPORT_AUDIT_LOGS) {
            return ['total_records' => $rows->count()];
        }

        if ($reportType === self::REPORT_OWNER_BRANCH_ANALYTICS) {
            return [
                'total_cases' => (int) $rows->sum('total_cases'),
                'paid_cases' => (int) $rows->sum('paid_cases'),
                'partial_cases' => (int) $rows->sum('partial_cases'),
                'unpaid_cases' => (int) $rows->sum('unpaid_cases'),
                'gross_amount' => (float) $rows->sum('gross_amount'),
                'collected_amount' => (float) $rows->sum('collected_amount'),
                'remaining_balance' => (float) $rows->sum('remaining_balance'),
            ];
        }

        return [
            'total_records' => $rows->count(),
            'gross_amount' => (float) $rows->sum('total_amount'),
            'collected_amount' => (float) $rows->sum('total_paid'),
            'remaining_balance' => (float) $rows->sum('balance'),
        ];
    }

    private function applyCommonCaseFilters($query, Request $request, array $branchScope): void
    {
        $this->applyReportBranchScope($query, $request, $branchScope);

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
        }
        if ($request->filled('case_status')) {
            $query->where('case_status', $request->string('case_status'));
        }
        if ($request->filled('package_id')) {
            $query->where('package_id', (int) $request->input('package_id'));
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->string('service_type'));
        }

        [$startAt, $endAt] = $this->parseDateBounds(
            $request->filled('date_from') ? $request->input('date_from') : null,
            $request->filled('date_to') ? $request->input('date_to') : null,
        );
        if ($startAt) {
            $query->where('created_at', '>=', $startAt);
        }
        if ($endAt) {
            $query->where('created_at', '<=', $endAt);
        }
    }

    private function normalizeReportType(?string $reportType): string
    {
        $reportType = $reportType ?: self::REPORT_SALES;

        if (! array_key_exists($reportType, $this->availableReportTypes())) {
            abort(404);
        }

        return $reportType;
    }

    private function authorizeReports(?string $reportType = null): void
    {
        $user = auth()->user();
        if (! $user || $user->isStaff() || (! $user->isAdmin() && ! $user->isOwner())) {
            abort(403);
        }

        if ($user->isOwner() && $reportType && $reportType !== self::REPORT_OWNER_BRANCH_ANALYTICS) {
            abort(403);
        }
    }

    private function availableReportTypes(): array
    {
        $user = auth()->user();
        if ($user?->isOwner()) {
            return [
                self::REPORT_OWNER_BRANCH_ANALYTICS => 'Owner Sales per Branch / Branch Analytics',
            ];
        }

        return [
            self::REPORT_SALES => 'Sales Report',
            self::REPORT_MASTER_CASES => 'Master Cases / Case Monitoring',
            self::REPORT_AUDIT_LOGS => 'Audit Logs',
            self::REPORT_OWNER_BRANCH_ANALYTICS => 'Owner Sales per Branch / Branch Analytics',
        ];
    }

    private function auditFilterOptions(): array
    {
        return [
            'supports_user' => Schema::hasColumn('audit_logs', 'actor_id'),
            'supports_action' => Schema::hasColumn('audit_logs', 'action'),
            'supports_module' => Schema::hasColumn('audit_logs', 'entity_type'),
            'actions' => Schema::hasColumn('audit_logs', 'action')
                ? AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action')->filter()->values()
                : collect(),
            'modules' => Schema::hasColumn('audit_logs', 'entity_type')
                ? AuditLog::query()->select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type')->filter()->values()
                : collect(),
        ];
    }

    private function reportBranchScope(Request $request): array
    {
        $user = $request->user();

        if (! $user || $user->isStaff()) {
            abort(403);
        }

        if ($user->isBranchAdmin()) {
            if (! $user->branch_id) {
                abort(403, 'No assigned branch configured.');
            }

            return [
                'forced_branch_id' => (int) $user->branch_id,
                'can_select_all' => false,
            ];
        }

        if ($user->isMainAdmin() || $user->isOwner()) {
            return [
                'forced_branch_id' => null,
                'can_select_all' => true,
            ];
        }

        abort(403);
    }

    private function applyReportBranchScope(Builder $query, Request $request, array $branchScope, string $column = 'branch_id'): void
    {
        if ($branchScope['forced_branch_id'] ?? null) {
            $query->where($column, (int) $branchScope['forced_branch_id']);
            return;
        }

        if (($branchScope['can_select_all'] ?? false) && $request->filled('branch_id')) {
            $query->where($column, (int) $request->input('branch_id'));
        }
    }

    private function reportBranches(User $user): Collection
    {
        $query = Branch::orderBy('branch_code');

        if ($user->isBranchAdmin()) {
            $query->whereKey((int) $user->branch_id);
        }

        return $query->get(['id', 'branch_code', 'branch_name']);
    }

    private function presentFilters(array $validated, array $branchScope): array
    {
        $filters = [];
        if ($branchScope['forced_branch_id'] ?? null) {
            $filters['branch_id'] = $this->branchName(Branch::find((int) $branchScope['forced_branch_id']));
        }

        foreach ($validated as $key => $value) {
            if ($key === 'report_type') {
                continue;
            }

            if ($key === 'branch_id' && ($branchScope['forced_branch_id'] ?? null)) {
                continue;
            }

            $filters[$key] = match ($key) {
                'branch_id' => optional(Branch::find($value), fn ($branch) => $this->branchName($branch)) ?? $value,
                'package_id' => optional(Package::find($value), fn ($package) => $package->name) ?? $value,
                'encoded_by', 'user_id' => optional(User::find($value), fn ($user) => $this->personName($user)) ?? $value,
                default => $value,
            };
        }

        return $filters;
    }

    private function reportTitle(string $reportType): string
    {
        return $this->availableReportTypes()[$reportType] ?? str($reportType)->headline()->toString();
    }

    private function reportColumns(string $reportType): array
    {
        return match ($reportType) {
            self::REPORT_OWNER_BRANCH_ANALYTICS => [
                'branch' => 'Branch',
                'total_cases' => 'Total Cases',
                'paid_cases' => 'Paid Cases',
                'partial_cases' => 'Partial Cases',
                'unpaid_cases' => 'Unpaid Cases',
                'gross_amount' => 'Gross Amount',
                'collected_amount' => 'Collected Amount',
                'remaining_balance' => 'Remaining Balance',
            ],
            self::REPORT_AUDIT_LOGS => [
                'date' => 'Date',
                'user' => 'User',
                'role' => 'Role',
                'action' => 'Action',
                'action_type' => 'Action Type',
                'module' => 'Module',
                'record_id' => 'Record ID',
                'branch' => 'Branch',
                'status' => 'Status',
                'remarks' => 'Remarks',
            ],
            self::REPORT_MASTER_CASES => [
                'case_no' => 'Case No.',
                'case_code' => 'Case Code',
                'client' => 'Client',
                'deceased' => 'Deceased',
                'branch' => 'Branch',
                'service_type' => 'Service Type',
                'package' => 'Package',
                'interment_date' => 'Interment Date',
                'payment_status' => 'Payment Status',
                'case_status' => 'Case Status',
                'verification_status' => 'Verification Status',
                'encoded_by' => 'Encoded By',
                'date_created' => 'Date Created',
            ],
            default => [
                'case_no' => 'Case No.',
                'client' => 'Client',
                'deceased' => 'Deceased',
                'branch' => 'Branch',
                'package' => 'Package',
                'service_type' => 'Service Type',
                'total_amount' => 'Total Amount',
                'total_paid' => 'Total Paid',
                'balance' => 'Balance',
                'payment_status' => 'Payment Status',
                'case_status' => 'Case Status',
                'date' => 'Date Created or Paid Date',
            ],
        };
    }

    private function personName($model): string
    {
        if (! $model) {
            return '-';
        }

        $firstLast = trim(implode(' ', array_filter([
            $model->first_name ?? null,
            $model->last_name ?? null,
        ])));

        return $model->full_name
            ?? ($firstLast ?: null)
            ?? $model->name
            ?? '-';
    }

    private function branchName($branch): string
    {
        if (! $branch) {
            return '-';
        }

        return trim(($branch->branch_code ? $branch->branch_code . ' - ' : '') . ($branch->branch_name ?? '')) ?: '-';
    }

    private function packageName(FuneralCase $case): string
    {
        return $case->package?->package_name
            ?: $case->package?->name
            ?: $case->custom_package_name
            ?: $case->service_package
            ?: '-';
    }

    private function formatDate($value): string
    {
        return $value ? $value->format('Y-m-d') : '-';
    }

    private function formatDateTime($value): string
    {
        return $value ? $value->format('Y-m-d H:i') : '-';
    }
}
