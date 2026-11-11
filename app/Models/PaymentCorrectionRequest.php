<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PaymentCorrectionRequest extends Model
{
    protected $fillable = ['payment_id', 'branch_id', 'requested_by', 'correction_type', 'status', 'original_values', 'proposed_values', 'reason', 'reason_category', 'evidence_reference', 'evidence_path', 'evidence_name', 'withdrawn_at', 'reviewed_by', 'reviewed_at', 'review_reason', 'replacement_payment_id'];

    protected $casts = ['original_values' => 'array', 'proposed_values' => 'array', 'reviewed_at' => 'datetime', 'withdrawn_at' => 'datetime'];

    public const REASONS = ['Incorrect amount', 'Incorrect payment date', 'Incorrect payment details', 'Incorrect receipt reference', 'Multiple payment details', 'Wrong case', 'Duplicate entry', 'Other'];

    public const PAYMENT_FIELDS = ['funeral_case_id', 'amount', 'paid_date', 'paid_at', 'receipt_or_no', 'accounting_reference_no', 'payment_method', 'payment_mode', 'method', 'cashless_type', 'bank_name', 'other_bank_name', 'wallet_provider', 'account_name', 'mobile_number', 'reference_number', 'approval_code', 'card_type', 'terminal_provider', 'payment_channel', 'payment_notes', 'bank_or_channel', 'other_bank_or_channel', 'transaction_reference_no', 'sender_name'];

    public function scopeForMonitoring(Builder $query, User $user): Builder
    {
        return $query
            ->when(!$user->isSystemAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($user->isStaff(), fn ($q) => $q->where('requested_by', $user->id));
    }

    public function isVisibleTo(User $user): bool
    {
        return $user->isSystemAdmin() || (
            (int) $user->branch_id === (int) $this->branch_id
            && ($user->isBranchAdmin() || ($user->isStaff() && (int) $this->requested_by === (int) $user->id))
        );
    }

    public static function pendingSummaryFor(User $user): array
    {
        $summary = static::forMonitoring($user)->where('status', 'PENDING')
            ->selectRaw('COUNT(*) AS pending_count, MAX(id) AS latest_pending_id')->toBase()->first();

        return ['pending' => (int) $summary->pending_count, 'latest_pending_id' => (int) $summary->latest_pending_id];
    }

    public function approvalPreviewToken(FuneralCase $originalCase, FuneralCase $targetCase): string
    {
        return hash_hmac('sha256', json_encode([
            $this->id,
            $this->proposed_values,
            $originalCase->only(['id', 'total_amount', 'total_paid', 'balance_amount']),
            $targetCase->only(['id', 'total_amount', 'total_paid', 'balance_amount']),
        ], JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function snapshot(\App\Models\Payment $payment): array
    {
        $snapshot = json_decode(json_encode($payment->only(self::PAYMENT_FIELDS)), true);
        // Requests created before receipt corrections retain their original snapshot fields.
        return $this->exists && is_array($this->original_values)
            ? array_intersect_key($snapshot, $this->original_values) : $snapshot;
    }

    public function setReasonCategoryAttribute($value)
    {
        $this->attributes['reason_category'] = $value;
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function replacementPayment()
    {
        return $this->belongsTo(Payment::class, 'replacement_payment_id');
    }
}
