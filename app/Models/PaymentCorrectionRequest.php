<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCorrectionRequest extends Model
{
    protected $fillable = ['payment_id', 'branch_id', 'requested_by', 'correction_type', 'status', 'original_values', 'proposed_values', 'reason', 'reviewed_by', 'reviewed_at', 'review_reason', 'replacement_payment_id'];
    protected $casts = ['original_values' => 'array', 'proposed_values' => 'array', 'reviewed_at' => 'datetime'];
    public function payment() { return $this->belongsTo(Payment::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function replacementPayment() { return $this->belongsTo(Payment::class, 'replacement_payment_id'); }
}
