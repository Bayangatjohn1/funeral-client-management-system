<?php

namespace App\Http\Controllers;

use App\Models\HistoricalCaseSubmission;
use App\Models\Branch;
use App\Services\HistoricalCasePromotionService;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HistoricalCaseSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isStaff() || $user->isBranchAdmin() || $user->isSystemAdmin(), 403);
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'submitted_from' => ['nullable', 'date'],
            'submitted_to' => ['nullable', 'date', 'after_or_equal:submitted_from'],
            'service_from' => ['nullable', 'date'],
            'service_to' => ['nullable', 'date', 'after_or_equal:service_from'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'per_page' => ['nullable', 'integer', 'in:10,20,25,50,100'],
        ]);
        $query = HistoricalCaseSubmission::with(['branch:id,branch_code,branch_name', 'encoder:id,name', 'officialCase:id,case_code']);
        if ($user->isStaff()) {
            $query->where('encoded_by', $user->id);
        } elseif ($user->isBranchAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }
        $status = $request->query('status');
        if (in_array($status, [HistoricalCaseSubmission::PENDING, HistoricalCaseSubmission::NEEDS_CHANGES, HistoricalCaseSubmission::REJECTED, HistoricalCaseSubmission::APPROVED], true)) {
            $query->where('status', $status);
        } else {
            $status = '';
        }
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $normalizedStatusSearch = '%'.str_replace([' ', '-'], '_', strtolower($search)).'%';
            $searchDate = null;
            if (preg_match('/^(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4}|[A-Za-z]{3,9}\s+\d{1,2},?\s+\d{4})$/', $search)) {
                try {
                    $searchDate = Carbon::parse($search)->toDateString();
                } catch (\Throwable) {
                    $searchDate = null;
                }
            }
            $query->where(function ($builder) use ($like, $normalizedStatusSearch, $searchDate) {
                $builder->where('submission_code', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereRaw('LOWER(status) LIKE ?', [$normalizedStatusSearch])
                    ->orWhere('created_at', 'like', $like)
                    ->orWhere('submission_data->validated->client_first_name', 'like', $like)
                    ->orWhere('submission_data->validated->client_middle_name', 'like', $like)
                    ->orWhere('submission_data->validated->client_last_name', 'like', $like)
                    ->orWhere('submission_data->validated->deceased_first_name', 'like', $like)
                    ->orWhere('submission_data->validated->deceased_middle_name', 'like', $like)
                    ->orWhere('submission_data->validated->deceased_last_name', 'like', $like)
                    ->orWhere('submission_data->schedule->wake_start_date', 'like', $like)
                    ->orWhere('submission_data->schedule->wake_end_date', 'like', $like)
                    ->orWhere('submission_data->schedule->funeral_date', 'like', $like)
                    ->orWhere('submission_data->schedule->interment', 'like', $like)
                    ->orWhere('submission_data->validated->service_requested_at', 'like', $like)
                    ->orWhere('submission_data->validated->wake_start_date', 'like', $like)
                    ->orWhere('submission_data->validated->wake_end_date', 'like', $like)
                    ->orWhere('submission_data->validated->funeral_service_at', 'like', $like)
                    ->orWhere('submission_data->validated->interment_at', 'like', $like)
                    ->orWhereHas('branch', fn ($branch) => $branch->where('branch_code', 'like', $like)->orWhere('branch_name', 'like', $like))
                    ->orWhereHas('encoder', fn ($encoder) => $encoder->where('name', 'like', $like))
                    ->orWhereHas('officialCase', fn ($case) => $case->where('case_code', 'like', $like));
                if ($searchDate) {
                    $builder->orWhereDate('created_at', $searchDate)
                        ->orWhereDate('submission_data->schedule->wake_start_date', $searchDate)
                        ->orWhereDate('submission_data->schedule->wake_end_date', $searchDate)
                        ->orWhereDate('submission_data->schedule->funeral_date', $searchDate)
                        ->orWhereDate('submission_data->schedule->interment', $searchDate)
                        ->orWhereDate('submission_data->validated->service_requested_at', $searchDate)
                        ->orWhereDate('submission_data->validated->wake_start_date', $searchDate)
                        ->orWhereDate('submission_data->validated->wake_end_date', $searchDate)
                        ->orWhereDate('submission_data->validated->funeral_service_at', $searchDate)
                        ->orWhereDate('submission_data->validated->interment_at', $searchDate);
                }
            });
        }
        $submittedFrom = $request->query('submitted_from');
        $submittedTo = $request->query('submitted_to');
        if ($submittedFrom) $query->whereDate('created_at', '>=', $submittedFrom);
        if ($submittedTo) $query->whereDate('created_at', '<=', $submittedTo);
        $serviceFrom = $request->query('service_from');
        $serviceTo = $request->query('service_to');
        if ($serviceFrom || $serviceTo) {
            $query->where(function ($range) use ($serviceFrom, $serviceTo) {
                if ($serviceFrom) {
                    $range->where(function ($endDates) use ($serviceFrom) {
                        $endDates->whereDate('submission_data->schedule->interment', '>=', $serviceFrom)
                            ->orWhereDate('submission_data->schedule->funeral_date', '>=', $serviceFrom)
                            ->orWhereDate('submission_data->schedule->wake_end_date', '>=', $serviceFrom);
                    });
                }
                if ($serviceTo) {
                    $range->whereDate('submission_data->schedule->wake_start_date', '<=', $serviceTo);
                }
            });
        }
        $branchId = null;
        if ($user->isSystemAdmin() && $request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $query->where('branch_id', $branchId);
        }
        $sort = in_array($request->query('sort'), ['oldest'], true) ? 'oldest' : 'newest';
        $sortDirection = $sort === 'oldest' ? 'asc' : 'desc';
        $perPage = (int) ($request->query('per_page') ?: 20);
        $submissions = $query->orderBy('created_at', $sortDirection)->orderBy('id', $sortDirection)->paginate($perPage)->withQueryString();
        $branches = $user->isSystemAdmin() ? Branch::where('is_active', true)->orderBy('branch_code')->get(['id', 'branch_code', 'branch_name']) : collect();
        return view('historical_cases.index', compact('submissions', 'branches', 'status', 'search', 'branchId', 'sort', 'perPage', 'submittedFrom', 'submittedTo', 'serviceFrom', 'serviceTo'));
    }

    public function show(Request $request, HistoricalCaseSubmission $submission)
    {
        $this->authorizeSubmission($request, $submission);
        $submission->load(['branch:id,branch_code,branch_name', 'encoder:id,name', 'authorizer:id,name', 'reviews.reviewer:id,name', 'officialCase']);
        return view('historical_cases.show', compact('submission'));
    }

    public function paymentProof(Request $request, HistoricalCaseSubmission $submission, int $paymentIndex)
    {
        $this->authorizeSubmission($request, $submission);
        $proof = $submission->submission_data['historical_payments'][$paymentIndex]['proof'] ?? null;
        abort_unless(is_array($proof) && !empty($proof['path']) && !empty($proof['mime']), 404);
        abort_unless(Storage::disk('local')->exists($proof['path']), 404);
        $extension = match ($proof['mime']) {
            'application/pdf' => 'pdf', 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg',
        };
        return Storage::disk('local')->response($proof['path'], 'historical-payment-proof.'.$extension, [
            'Content-Type' => $proof['mime'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function decide(Request $request, HistoricalCaseSubmission $submission, HistoricalCasePromotionService $promotion)
    {
        $this->authorizeSubmission($request, $submission);
        abort_unless($request->user()->isBranchAdmin() || $request->user()->isSystemAdmin(), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,needs_changes,reject'],
            'note' => ['required_if:decision,needs_changes,reject', 'nullable', 'string', 'max:2000'],
        ]);
        if ((int) $submission->encoded_by === (int) $request->user()->id && $data['decision'] === 'approve') {
            throw ValidationException::withMessages(['decision' => 'You cannot approve a historical entry that you submitted.']);
        }
        if ($data['decision'] === 'approve') {
            $case = DB::transaction(function () use ($promotion, $submission, $request) {
                $case = $promotion->approve($submission, $request->user());
                AuditLogger::log(
                    action: 'historical_case.approved', actionType: 'approve', entityType: 'historical_case_submission',
                    entityId: $submission->id, metadata: ['submission_code' => $submission->submission_code, 'case_id' => $case->id, 'case_code' => $case->case_code],
                    branchId: (int) $submission->branch_id, targetBranchId: (int) $submission->branch_id,
                    status: 'success', remarks: 'Historical case approved and promoted to official records', actionLabel: 'Historical case approved'
                );
                return $case;
            });
            return redirect()->route('historical-cases.show', $submission)->with('success', 'Historical case approved. Official case '.$case->case_code.' was created.');
        }

        $status = $data['decision'] === 'needs_changes' ? HistoricalCaseSubmission::NEEDS_CHANGES : HistoricalCaseSubmission::REJECTED;
        $action = strtoupper($data['decision']);
        DB::transaction(function () use ($submission, $request, $data, $status, $action) {
            $submission = HistoricalCaseSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($submission->status !== HistoricalCaseSubmission::PENDING) {
                throw ValidationException::withMessages(['submission' => 'This historical submission has already been reviewed.']);
            }
            $submission->update(['status' => $status, 'review_note' => $data['note'], 'authorized_by' => $request->user()->id, 'authorized_at' => now()]);
            $submission->reviews()->create(['reviewed_by' => $request->user()->id, 'action' => $action, 'note' => $data['note'], 'created_at' => now()]);
            AuditLogger::log(
                action: 'historical_case.'.$data['decision'], actionType: 'review', entityType: 'historical_case_submission',
                entityId: $submission->id, metadata: ['submission_code' => $submission->submission_code, 'note' => $data['note']],
                branchId: (int) $submission->branch_id, targetBranchId: (int) $submission->branch_id,
                status: 'success', remarks: 'Historical case review decision recorded', actionLabel: 'Historical case reviewed'
            );
        });
        return redirect()->route('historical-cases.show', $submission)->with('success', $status === HistoricalCaseSubmission::NEEDS_CHANGES ? 'Changes requested. The encoder can correct and resubmit this entry.' : 'Historical submission rejected and kept for audit history.');
    }

    public function resume(Request $request, HistoricalCaseSubmission $submission)
    {
        $this->authorizeSubmission($request, $submission);
        abort_unless((int) $submission->encoded_by === (int) $request->user()->id, 403);
        abort_unless(in_array($submission->status, [HistoricalCaseSubmission::NEEDS_CHANGES, HistoricalCaseSubmission::REJECTED], true), 409);
        $data = $submission->submission_data;
        $values = $data['validated'] ?? [];
        $payments = $data['historical_payments'] ?? [];
        $oldPaymentInputs = [];
        $paymentDetails = [];
        if (!empty($data['mark_as_paid']) && $payments) {
            $primary = array_shift($payments);
            $details = $primary['details'] ?? [];
            $values = array_merge($values, [
                'mark_as_paid' => 1, 'payment_type' => (float) $primary['amount'] >= (float) ($data['pricing']['total'] ?? 0) ? 'FULL' : 'PARTIAL',
                'amount_paid' => $primary['amount'], 'paid_at' => \Carbon\Carbon::parse($primary['paid_at'])->format('Y-m-d\\TH:i'),
                'receipt_or_no' => $primary['receipt_or_no'] ?? '', 'payment_proof_missing_reason' => $primary['proof_missing_reason'] ?? '',
                'payment_method' => $details['payment_method'] ?? 'cash', 'cashless_type' => $details['cashless_type'] ?? '',
                'bank_name' => $details['bank_name'] ?? '', 'other_bank_name' => $details['other_bank_name'] ?? '',
                'account_name' => $details['account_name'] ?? '', 'mobile_number' => $details['mobile_number'] ?? '',
                'reference_number' => $details['reference_number'] ?? '', 'approval_code' => $details['approval_code'] ?? '',
                'card_type' => $details['card_type'] ?? '', 'terminal_provider' => $details['terminal_provider'] ?? '',
                'payment_channel' => $details['payment_channel'] ?? '', 'payment_notes' => $details['payment_notes'] ?? '',
                'historical_primary_parent_payment_index' => 0,
                'historical_primary_existing_proof' => $primary['proof'] ?? null,
            ]);
            $values['payment_proof_id'] = $values['payment_proof_id'] ?? '';
        }
        $startIndex = !empty($data['mark_as_paid']) ? 1 : 0;
        foreach ($payments as $offset => $payment) {
            $details = $payment['details'] ?? [];
            $oldPaymentInputs[] = array_merge([
                'amount' => $payment['amount'],
                'paid_at' => \Carbon\Carbon::parse($payment['paid_at'])->format('Y-m-d\\TH:i'),
                'receipt_or_no' => $payment['receipt_or_no'] ?? '',
                'proof_missing_reason' => $payment['proof_missing_reason'] ?? '',
                'parent_payment_index' => $startIndex + $offset,
                'existing_proof' => $payment['proof'] ?? null,
            ], $details);
        }
        $values['historical_payments'] = $oldPaymentInputs;
        return redirect()->route('historical-intake.create')
            ->withInput($values)
            ->with('historical_parent_submission_id', $submission->id);
    }

    private function authorizeSubmission(Request $request, HistoricalCaseSubmission $submission): void
    {
        $user = $request->user();
        if ($user->isSystemAdmin()) return;
        if ($user->isBranchAdmin() && (int) $user->branch_id === (int) $submission->branch_id) return;
        if ($user->isStaff() && (int) $user->id === (int) $submission->encoded_by) return;
        abort(403);
    }
}
