<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentCorrectionRequest;
use App\Models\WorkflowNotice;
use App\Support\AuditLogger;
use App\Support\Payments\PaymentDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentCorrectionController extends Controller
{
    private const MISSING_ORIGINAL_PROOF = 'The original payment must have an attached proof before you can request a correction.';

    public function create(Request $request, Payment $payment)
    {
        abort_unless($request->user()->isStaff() && (int) $request->user()->branch_id === (int) $payment->branch_id
            && (int) ($payment->recorded_by ?? $payment->encoded_by ?? $payment->received_by) === (int) $request->user()->id, 403);
        abort_if(in_array($payment->status, ['VOID', 'VOIDED'], true), 422, 'A voided payment cannot be corrected.');
        abort_if($payment->correctionRequests()->where('status', 'PENDING')->exists(), 422, 'This payment already has a pending correction request.');
        abort_unless($this->hasOriginalProof($payment), 422, self::MISSING_ORIGINAL_PROOF);
        $payment->load('funeralCase.client', 'funeralCase.deceased');
        abort_if($payment->funeralCase?->entry_source === 'OTHER_BRANCH', 422, 'Administrative other-branch reports are locked for payment updates.');
        $retainedCandidates = $this->retainedPaymentQuery($payment)->orderByDesc('paid_at')->orderByDesc('id')->get()
            ->filter(fn (Payment $candidate) => $candidate->display_payment_record_no && PaymentDetails::label($candidate) === PaymentDetails::label($payment));

        return view('staff.payments.correction-create', compact('payment', 'retainedCandidates'));
    }

    public function caseSearch(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $cases = \App\Models\FuneralCase::where('branch_id', $request->user()->branch_id)->where('entry_source', '<>', 'OTHER_BRANCH')
            ->whereIn('case_status', ['ACTIVE', 'COMPLETED'])->with('client', 'deceased')
            ->where(function ($q) use ($data) {
                $term = $data['q'] ?? '';
                $q->where('case_code', 'like', '%'.$term.'%')->orWhereHas('client', fn ($q) => $q->where('full_name', 'like', '%'.$term.'%'))
                    ->orWhereHas('deceased', fn ($q) => $q->where('full_name', 'like', '%'.$term.'%'));
            })->orderByDesc('id')->limit(10)->get();

        return response()->json($cases->map(fn ($case) => ['id' => $case->id, 'label' => $case->case_code.' · '.$case->client?->full_name.' / '.$case->deceased?->full_name, 'balance' => $case->balance_amount]));
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->isBranchAdmin(), 403);

        return redirect()->route('admin.payments.index', ['tab' => 'corrections']);
    }

    public function store(Request $request, Payment $payment)
    {
        $evidencePath = null;
        try {
            return DB::transaction(function () use ($request, $payment, &$evidencePath) {
                $payment = Payment::lockForUpdate()->findOrFail($payment->id);
                abort_unless($request->user()->role === 'staff' && (int) $request->user()->branch_id === (int) $payment->branch_id
                    && (int) ($payment->recorded_by ?? $payment->encoded_by ?? $payment->received_by) === (int) $request->user()->id, 403);
                abort_if($payment->funeralCase?->entry_source === 'OTHER_BRANCH', 422, 'Administrative other-branch reports are locked for payment updates.');
                abort_if(in_array($payment->status, ['VOID', 'VOIDED'], true), 422, 'A voided payment cannot be corrected.');
                abort_if($payment->correctionRequests()->where('status', 'PENDING')->exists(), 422, 'This payment already has a pending correction request.');
                if (! $this->hasOriginalProof($payment)) {
                    throw ValidationException::withMessages(['payment_proof' => self::MISSING_ORIGINAL_PROOF]);
                }

                $rules = [
                    'correction_type' => ['required', Rule::in(['CORRECT', 'VOID_ONLY'])],
                    'reason_category' => ['nullable', Rule::in(PaymentCorrectionRequest::REASONS)],
                    'evidence_reference' => ['required_if:correction_type,VOID_ONLY', 'nullable', 'string', 'min:5', 'max:500'],
                    'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                    'changes' => ['required_if:correction_type,CORRECT', 'array'],
                    'changes.*' => [Rule::in(['amount', 'paid_date', 'payment_details', 'receipt_or_no', 'funeral_case_id'])],
                    'reason' => ['required', 'string', 'min:10', 'max:1000'],
                    'amount' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
                    'paid_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
                    'payment_method' => ['nullable', Rule::in(['cash', 'cashless', 'bank_transfer'])],
                    'reference_number' => ['nullable', 'string', 'max:100'],
                    'receipt_or_no' => ['nullable', 'string', 'min:3', 'max:50', 'regex:~^[A-Za-z0-9 /-]+$~'],
                    'funeral_case_id' => ['nullable', 'integer', Rule::exists('funeral_cases', 'id')->where('branch_id', $payment->branch_id)],
                ];
                if (in_array('payment_details', $request->input('changes', []), true) && $request->input('correction_type') === 'CORRECT') {
                    $rules = array_replace($rules, PaymentDetails::rules('amount', 'paid_date'));
                    $rules['payment_method'] = ['required', Rule::in(['cash', 'cashless'])];
                }
                $validated = $request->validate($rules, PaymentDetails::messages());
                if ($validated['correction_type'] === 'VOID_ONLY') {
                    $this->requireRetainedPayment($payment, 'Duplicate entry', $validated['evidence_reference']);
                }

                $original = (new PaymentCorrectionRequest)->snapshot($payment);
                $proposed = $validated['correction_type'] === 'CORRECT'
                    ? array_filter(collect($validated)->only(['funeral_case_id', 'amount', 'paid_date', 'payment_method', 'reference_number'])->all(), fn ($value) => $value !== null && $value !== '')
                    : null;

                if ($proposed !== null) {
                    $changes = $validated['changes'];
                    $proposed = collect($validated)->only(array_intersect(['amount', 'paid_date', 'funeral_case_id', 'receipt_or_no'], $changes))->all();
                    foreach (array_intersect(['amount', 'paid_date', 'funeral_case_id', 'receipt_or_no'], $changes) as $field) {
                        if (blank($proposed[$field] ?? null)) {
                            throw ValidationException::withMessages([$field => 'Enter the proposed value for this correction.']);
                        }
                    }
                    if (isset($proposed['receipt_or_no'])) {
                        $this->validateReceiptReference($payment, $proposed['receipt_or_no']);
                        $proposed['accounting_reference_no'] = $proposed['receipt_or_no'];
                    }
                    if (in_array('payment_details', $changes, true)) {
                        $details = PaymentDetails::normalize($validated);
                        if ($errors = PaymentDetails::validateNormalized($details)) {
                            throw ValidationException::withMessages($errors);
                        }
                        foreach ($details as $field => $value) {
                            $field = match ($field) {
                                'legacy_payment_mode' => 'payment_mode', 'legacy_method' => 'method', 'legacy_bank_or_channel' => 'bank_or_channel',
                                'legacy_other_bank_or_channel' => 'other_bank_or_channel', 'legacy_transaction_reference_no' => 'transaction_reference_no', 'legacy_sender_name' => 'sender_name', default => $field,
                            };
                            if (in_array($field, PaymentCorrectionRequest::PAYMENT_FIELDS, true)) {
                                $proposed[$field] = $value;
                            }
                        }
                    }
                }

                if ($proposed !== null) {
                    if (isset($proposed['paid_date'])) {
                        // Reports use paid_at; keep the original payment time for date-only corrections.
                        $proposed['paid_at'] = $proposed['paid_date'].' '.($payment->paid_at?->format('H:i:s') ?? '00:00:00');
                    }
                    $changed = collect($proposed)->except(['paid_at', 'accounting_reference_no'])->filter(function ($value, $field) use ($original) {
                        if ($field === 'amount') {
                            return round((float) $value, 2) !== round((float) ($original[$field] ?? 0), 2);
                        }
                        if ($field === 'paid_date') {
                            return $value !== substr((string) ($original['paid_at'] ?? $original['paid_date']), 0, 10);
                        }

                        return (string) $value !== (string) ($original[$field] ?? '');
                    });
                    if ($changed->isEmpty()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['correction_type' => 'Change at least one payment detail before requesting a correction.']);
                    }
                    $categories = $changed->keys()->map(fn ($field) => match ($field) {
                        'amount' => 'Incorrect amount', 'paid_date' => 'Incorrect payment date',
                        'funeral_case_id' => 'Wrong case', 'receipt_or_no' => 'Incorrect receipt reference',
                        default => 'Incorrect payment details',
                    })->unique();
                }

                $correction = PaymentCorrectionRequest::create([
                    'payment_id' => $payment->id,
                    'branch_id' => $payment->branch_id,
                    'requested_by' => $request->user()->id,
                    'status' => 'PENDING',
                    'correction_type' => $validated['correction_type'],
                    'original_values' => $original,
                    'proposed_values' => $proposed,
                    'reason' => $validated['reason'],
                    'reason_category' => $proposed === null ? 'Duplicate entry' : ($categories->count() === 1 ? $categories->first() : 'Multiple payment details'),
                    'evidence_reference' => $validated['evidence_reference'] ?? ($payment->receipt_or_no ?: $payment->reference_number ?: $payment->approval_code ?: $payment->transaction_reference_no),
                ]);
                if ($request->hasFile('evidence')) {
                    $evidencePath ??= $request->file('evidence')->store('payment-corrections', 'local');
                    if (! $evidencePath) {
                        throw ValidationException::withMessages(['evidence' => 'The supporting file could not be saved. Please try again.']);
                    }
                    $correction->update(['evidence_path' => $evidencePath, 'evidence_name' => $request->file('evidence')->getClientOriginalName()]);
                }
                WorkflowNotice::correctionEvent($correction, 'requested');
                AuditLogger::log('PAYMENT_CORRECTION_REQUESTED', 'CREATE', 'payment_correction_request', $correction->id, $this->auditDetails($correction), $payment->branch_id);

                return $this->completed($request, 'Payment correction request submitted for Branch Admin review.');
            }, 3);
        } catch (\Throwable $error) {
            if ($evidencePath) {
                Storage::disk('local')->delete($evidencePath);
            }
            throw $error;
        }
    }

    public function reject(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeReviewer($request, $correction);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($request, $correction, $validated) {
            $locked = PaymentCorrectionRequest::lockForUpdate()->findOrFail($correction->id);
            abort_unless($locked->status === 'PENDING', 409, 'This request has already been resolved.');
            $locked->update(['status' => 'REJECTED', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_reason' => $validated['reason']]);
            WorkflowNotice::correctionEvent($locked, 'rejected');
            AuditLogger::log('PAYMENT_CORRECTION_REJECTED', 'UPDATE', 'payment_correction_request', $locked->id, $this->auditDetails($locked), $locked->branch_id);
        });

        return $this->completed($request, 'Correction request rejected; the original payment remains posted.');
    }

    public function approve(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeReviewer($request, $correction);
        $request->validate([
            'review_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'preview_token' => ['nullable', 'string', 'size:64'],
        ]);
        DB::transaction(function () use ($request, $correction) {
            $locked = PaymentCorrectionRequest::lockForUpdate()->findOrFail($correction->id);
            abort_unless($locked->status === 'PENDING', 409, 'This request has already been resolved.');
            abort_if((int) $locked->requested_by === (int) $request->user()->id, 403, 'You cannot approve your own request.');

            $payment = Payment::withoutGlobalScope('branch_scope')->lockForUpdate()->findOrFail($locked->payment_id);
            abort_if(in_array($payment->status, ['VOID', 'VOIDED'], true), 409, 'The original payment is already voided.');
            $oldCase = $payment->funeralCase()->lockForUpdate()->firstOrFail();
            abort_if($oldCase->entry_source === 'OTHER_BRANCH', 422, 'Administrative other-branch reports are locked for payment updates.');
            if ($locked->original_values != $locked->snapshot($payment)) {
                throw ValidationException::withMessages(['request' => 'The original payment changed after this request. Reject it and request a fresh review.']);
            }
            $values = $locked->correction_type === 'CORRECT' ? ($locked->proposed_values ?? []) : [];
            if (isset($values['receipt_or_no'])) {
                $this->validateReceiptReference($payment, $values['receipt_or_no']);
            }
            $newCaseId = (int) ($values['funeral_case_id'] ?? $payment->funeral_case_id);
            $newCase = $newCaseId === (int) $oldCase->id ? $oldCase
                : \App\Models\FuneralCase::withoutGlobalScope('branch_scope')->lockForUpdate()->findOrFail($newCaseId);
            abort_unless((int) $newCase->branch_id === (int) $locked->branch_id, 422, 'The corrected case must belong to the same branch.');
            abort_if($newCase->entry_source === 'OTHER_BRANCH', 422, 'Administrative other-branch reports are locked for payment updates.');
            if ($locked->correction_type === 'VOID_ONLY') {
                $request->validate(['duplicate_reconciled' => ['accepted'] ], [
                    'duplicate_reconciled.accepted' => 'Confirm that the receipt or collection log shows one actual payment before approving this void.',
                ]);
                $this->requireRetainedPayment($payment, $locked->reason_category, $locked->evidence_reference, lock: true);
            }
            if (!hash_equals($locked->approvalPreviewToken($oldCase, $newCase), $request->input('preview_token') ?? '')) {
                throw ValidationException::withMessages(['preview_token' => 'The financial preview must be refreshed. Review the current balances before approving.']);
            }
            $payment->update(['status' => 'VOIDED', 'void_reason' => $locked->reason]);
            $replacement = null;

            if ($locked->correction_type === 'CORRECT') {
                $posted = $newCase->payments()->where(function ($query) {
                    $query->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']);
                })->sum('amount');
                $replacementCents = (int) round((float) ($values['amount'] ?? $payment->amount) * 100);
                $remainingCents = (int) round((float) $newCase->total_amount * 100) - (int) round((float) $posted * 100);
                abort_if($replacementCents <= 0 || $replacementCents > $remainingCents, 422,
                    'The corrected payment exceeds the case remaining balance. Review the correction.');
                $replacement = $payment->replicate(['receipt_number', 'payment_record_no', 'status', 'void_reason', 'submission_id', 'submission_hash']);
                $replacement->fill($values);
                $replacement->funeral_case_id = $newCase->id;
                $replacement->branch_id = $newCase->branch_id;
                $replacement->replaces_payment_id = $payment->id;
                $replacement->status = 'POSTED';
                $replacement->recorded_by = $payment->recorded_by;
                $totals = $newCase->paymentTotalsFor((float) $posted + $replacementCents / 100);
                $replacement->balance_after_payment = $totals['balance_amount'];
                $replacement->payment_status_after_payment = $totals['payment_status'];
                $replacement->payment_record_no = Payment::nextPaymentRecordNumber(now());
                $replacement->save();
                $newCase->recalculatePaymentTotals();
            }
            $oldCase->recalculatePaymentTotals();
            $locked->update(['status' => 'APPROVED', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_reason' => $request->input('review_reason'), 'replacement_payment_id' => $replacement?->id]);
            WorkflowNotice::correctionEvent($locked, 'approved');
            AuditLogger::log('PAYMENT_CORRECTION_APPROVED', 'UPDATE', 'payment_correction_request', $locked->id, $this->auditDetails($locked), $locked->branch_id);
        }, 3);

        return $this->completed($request, 'Payment correction approved and financial totals recalculated.');
    }

    private function validateReceiptReference(Payment $payment, string $number): void
    {
        if ($number === $payment->receipt_or_no) return;
        $duplicate = Payment::where('branch_id', $payment->branch_id)->whereKeyNot($payment->id)
            ->where('receipt_or_no', $number)
            ->where(fn ($query) => $query->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']))
            ->lockForUpdate()->first();
        if ($duplicate) {
            throw ValidationException::withMessages(['receipt_or_no' => 'This Receipt / OR number is already assigned to another payment in this branch.']);
        }
    }

    private function retainedPaymentQuery(Payment $payment): \Illuminate\Database\Eloquent\Builder
    {
        return Payment::query()
            ->where('branch_id', $payment->branch_id)
            ->where('funeral_case_id', $payment->funeral_case_id)
            ->whereKeyNot($payment->id)
            ->where('amount', $payment->amount)
            ->whereIn('status', ['POSTED', 'VALID']);
    }

    private function retainedPayment(Payment $payment, ?string $reference, bool $lock = false): ?Payment
    {
        $number = trim((string) $reference);
        $candidate = $this->retainedPaymentQuery($payment)
            ->where(fn ($query) => $query->where('payment_record_no', $number)
                ->orWhere(fn ($query) => $query->whereNull('payment_record_no')->where('receipt_number', $number)))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();

        return $number !== '' && $candidate && PaymentDetails::label($candidate) === PaymentDetails::label($payment)
            ? $candidate : null;
    }

    private function requireRetainedPayment(Payment $payment, ?string $reason, ?string $reference, bool $lock = false): void
    {
        if ($reason !== 'Duplicate entry' || !$this->retainedPayment($payment, $reference, $lock)) {
            throw ValidationException::withMessages([
                'evidence_reference' => $lock
                    ? 'The payment to keep is no longer eligible. Recheck both records in Payment History before deciding.'
                    : 'Choose a separate posted payment from the same case with the same amount and method. Use Correct Payment for a genuine payment entered incorrectly.',
            ]);
        }
    }

    private function auditDetails(PaymentCorrectionRequest $correction): array
    {
        $payment = $correction->payment;
        $replacement = $correction->replacementPayment;
        $format = function ($field, $value) {
            if ($value === null || $value === '') {
                return 'Not provided';
            }
            if ($field === 'amount') {
                return 'PHP '.number_format((float) $value, 2);
            }
            if (in_array($field, ['paid_date', 'paid_at'], true)) {
                return \Carbon\Carbon::parse($value)->timezone(config('app.timezone'))->format($field === 'paid_date' ? 'M d, Y' : 'M d, Y g:i A');
            }

            return (string) $value;
        };
        $changes = [];
        foreach ($correction->proposed_values ?? [] as $field => $value) {
            if ($field === 'paid_at') {
                continue;
            } // Payment date is displayed once.
            $before = $format($field, $correction->original_values[$field] ?? null);
            $after = $format($field, $value);
            if ($before !== $after) {
                $changes[] = ['field' => $field, 'before' => $before, 'after' => $after];
            }
        }

        return [
            'payment_id' => $correction->payment_id,
            'payment_record_no' => $payment?->display_payment_record_no,
            ...($correction->correction_type === 'VOID_ONLY' ? [
                'void_payment_record_no' => $payment?->display_payment_record_no,
                'retained_payment_record_no' => $correction->evidence_reference,
            ] : []),
            'case_code' => $payment?->funeralCase?->case_code,
            'correction_type' => $correction->correction_type === 'VOID_ONLY' ? 'Void without replacement' : 'Correct payment',
            'request_status' => $correction->status,
            'requested_by' => $correction->requester?->name,
            'reason' => $correction->reason,
            'reviewed_by' => $correction->reviewer?->name,
            'review_reason' => $correction->review_reason,
            'replacement_payment_id' => $correction->replacement_payment_id,
            'replacement_payment_record_no' => $replacement?->display_payment_record_no,
            'changes' => $changes,
        ];
    }

    public function show(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeRead($request, $correction);
        $correction->load(['payment.funeralCase.client', 'payment.funeralCase.deceased', 'requester', 'reviewer', 'replacementPayment', 'branch']);
        $retainedPayment = $correction->payment && $correction->correction_type === 'VOID_ONLY'
            ? $this->retainedPayment($correction->payment, $correction->evidence_reference) : null;
        $correctionHistory = $correction->payment?->correctionRequests()
            ->forMonitoring($request->user())->with(['requester:id,name', 'reviewer:id,name'])->orderByDesc('id')->get() ?? collect();
        $historyCases = collect();
        if ($request->user()->isBranchAdmin()) {
            $caseIds = array_filter([$correction->payment?->funeral_case_id, $correction->proposed_values['funeral_case_id'] ?? null]);
            $historyCases = \App\Models\FuneralCase::where('branch_id', $correction->branch_id)->whereIn('id', $caseIds)
                ->with(['deceased', 'payments' => fn ($query) => $query->withTrashed()->with('recordedBy')
                    ->orderByDesc('paid_at')->orderByDesc('paid_date')->orderByDesc('id')])->get()
                ->sortBy(fn ($case) => (int) $case->id === (int) $correction->payment?->funeral_case_id ? 0 : 1)->values();
        }
        WorkflowNotice::where('user_id', $request->user()->id)->where('payment_correction_request_id', $correction->id)->whereNull('read_at')->update(['read_at' => now()]);

        $lastReminderAt = $request->user()->isSystemAdmin() && $correction->status === 'PENDING'
            ? WorkflowNotice::latestManualCorrectionReminder($correction)?->created_at
            : null;

        return view('staff.payments.correction-detail', [
            'item' => $correction,
            'historyCases' => $historyCases,
            'correctionHistory' => $correctionHistory,
            'lastReminderAt' => $lastReminderAt,
            'retainedPayment' => $retainedPayment,
        ]);
    }

    public function remind(Request $request, PaymentCorrectionRequest $correction)
    {
        abort_unless($request->user()->isSystemAdmin(), 403);

        $sentAt = DB::transaction(function () use ($correction) {
            $locked = PaymentCorrectionRequest::whereKey($correction->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'PENDING', 409, 'This request has already been resolved.');

            $now = now();
            $lastSentAt = WorkflowNotice::latestManualCorrectionReminder($locked)?->created_at;
            $cooldown = WorkflowNotice::CORRECTION_REMINDER_COOLDOWN_MINUTES;
            abort_if($lastSentAt && $lastSentAt->addMinutes($cooldown)->isAfter($now), 429,
                "A reminder was already sent. You can send another after {$cooldown} minutes.");

            $recipients = WorkflowNotice::correctionEvent($locked, WorkflowNotice::MANUAL_CORRECTION_REMINDER_PREFIX.$now->timestamp);
            abort_unless($recipients > 0, 422, 'This branch has no active Branch Admin to notify.');

            AuditLogger::log('PAYMENT_CORRECTION_REMINDER_SENT', 'UPDATE', 'payment_correction_request', $locked->id, [
                'request_status' => $locked->status,
                'payment_record_no' => $locked->payment?->display_payment_record_no,
                'recipients' => $recipients,
            ], $locked->branch_id);

            return $now;
        }, 3);

        $message = 'Reminder sent to the Branch Admin.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'sent_at' => $sentAt->format('M d, Y g:i A')]);
        }

        return redirect()->route('payments.corrections.show', $correction)->with('success', $message);
    }

    public function withdraw(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeRead($request, $correction);
        abort_unless($request->user()->isStaff() && (int) $correction->requested_by === (int) $request->user()->id, 403);
        DB::transaction(function () use ($correction) {
            $locked = PaymentCorrectionRequest::lockForUpdate()->findOrFail($correction->id);
            abort_unless($locked->status === 'PENDING', 409, 'This request has already been resolved.');
            $locked->update(['status' => 'WITHDRAWN', 'withdrawn_at' => now()]);
            WorkflowNotice::correctionEvent($locked, 'withdrawn');
            AuditLogger::log('PAYMENT_CORRECTION_WITHDRAWN', 'UPDATE', 'payment_correction_request', $locked->id, $this->auditDetails($locked), $locked->branch_id);
        });

        return $this->completed($request, 'Request withdrawn. The original payment remains unchanged.');
    }

    public function evidence(Request $request, PaymentCorrectionRequest $correction)
    {
        $this->authorizeRead($request, $correction);
        abort_unless($correction->evidence_path && Storage::disk('local')->exists($correction->evidence_path), 404);

        return Storage::disk('local')->download($correction->evidence_path, $correction->evidence_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function notices(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isSystemAdmin() || $user->isStaff() || $user->isBranchAdmin(), 403);

        return response()->json(WorkflowNotice::correctionFeedFor($user))->header('Cache-Control', 'no-store');
    }

    private function hasOriginalProof(Payment $payment): bool
    {
        return $payment->proof_path && $payment->proof_mime && Storage::disk('local')->exists($payment->proof_path);
    }

    private function authorizeRead(Request $request, PaymentCorrectionRequest $correction): void
    {
        abort_unless($correction->isVisibleTo($request->user()), 403);
    }

    private function completed(Request $request, string $message)
    {
        $route = $request->user()->isBranchAdmin() ? 'admin.payments.index' : 'payments.history';
        $url = route($route, ['tab' => 'corrections']);
        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);

            return response()->json(['message' => $message, 'redirect' => $url]);
        }

        return redirect($url)->with('success', $message);
    }

    private function authorizeReviewer(Request $request, PaymentCorrectionRequest $correction): void
    {
        abort_unless($request->user()->isBranchAdmin() && (int) $request->user()->branch_id === (int) $correction->branch_id, 403);
    }
}
