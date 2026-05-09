<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\CarbonInterface;
use App\Support\BranchScoped;

class Payment extends Model
{
    use BranchScoped, SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'payment_record_no',
        'accounting_reference_no',
        'receipt_or_no',
        'funeral_case_id',
        'branch_id',
        'method',          // legacy ENUM('CASH'); kept for backward compat
        'payment_mode',    // legacy/canonical compat: cash | bank_transfer
        'payment_method',
        'cashless_type',
        'bank_name',
        'other_bank_name',
        'wallet_provider',
        'account_name',
        'mobile_number',
        'reference_number', // required when payment_mode = bank_transfer
        'approval_code',
        'card_type',
        'terminal_provider',
        'payment_channel',
        'payment_notes',
        'bank_or_channel',
        'other_bank_or_channel',
        'transaction_reference_no',
        'sender_name',
        'transfer_datetime',
        'amount',
        'balance_after_payment',
        'payment_status_after_payment',
        'paid_date',
        'paid_at',
        'received_by',
        'encoded_by',
        'recorded_by',
        'remarks',
        'status',       // VALID | VOID
        'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after_payment' => 'decimal:2',
        'paid_date' => 'date',
        'paid_at' => 'datetime',
        'transfer_datetime' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function buildReceiptNumber(int $paymentId, CarbonInterface $paidAt): string
    {
        return \sprintf('RCPT-%s-%06d', $paidAt->format('Y'), $paymentId);
    }

    public static function buildPaymentRecordNumber(int $sequence, CarbonInterface $paidAt): string
    {
        return \sprintf('PAY-%s-%06d', $paidAt->format('Y'), $sequence);
    }

    public static function nextPaymentRecordNumber(CarbonInterface $paidAt): string
    {
        $year = $paidAt->format('Y');

        $latest = static::withoutGlobalScope('branch_scope')
            ->withTrashed()
            ->where('payment_record_no', 'like', "PAY-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('payment_record_no')
            ->value('payment_record_no');

        $next = 1;
        if (is_string($latest) && preg_match('/^PAY-\d{4}-(\d{6})$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return static::buildPaymentRecordNumber($next, $paidAt);
    }

    public function getDisplayPaymentRecordNoAttribute(): ?string
    {
        return $this->payment_record_no ?: $this->receipt_number;
    }

    public function funeralCase()
    {
        return $this->belongsTo(\App\Models\FuneralCase::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }

    public function encodedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'encoded_by');
    }
}
