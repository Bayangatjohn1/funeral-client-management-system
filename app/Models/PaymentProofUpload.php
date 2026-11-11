<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentProofUpload extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['session_hash', 'token_hash', 'path'];

    protected $casts = [
        'expires_at' => 'datetime', 'retain_until' => 'datetime',
        'claimed_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
