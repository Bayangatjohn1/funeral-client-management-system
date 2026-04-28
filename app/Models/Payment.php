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
        'funeral_case_id',
        'branch_id',
        'method',          // legacy ENUM('CASH'); kept for backward compat
        'payment_mode',    // canonical: cash | bank_transfer (Phase 1+)
        'reference_number', // required when payment_mode = bank_transfer
        'amount',
        'balance_after_payment',
        'payment_status_after_payment',
        'paid_date',
        'paid_at',
        'recorded_by',
        'status',       // VALID | VOID
        'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after_payment' => 'decimal:2',
        'paid_date' => 'date',
        'paid_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function buildReceiptNumber(int $paymentId, CarbonInterface $paidAt): string
    {
        return \sprintf('RCPT-%s-%06d', $paidAt->format('Y'), $paymentId);
    }

    public function funeralCase()
    {
        return $this->belongsTo(\App\Models\FuneralCase::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }
}
