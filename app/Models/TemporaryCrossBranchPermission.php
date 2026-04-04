<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TemporaryCrossBranchPermission extends Model
{
    protected $fillable = [
        'user_id',
        'allowed_branch_id',
        'granted_by',
        'is_active',
        'is_used',
        'granted_at',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_used' => 'boolean',
        'granted_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'allowed_branch_id');
    }

    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    public function markUsed(): void
    {
        $this->forceFill([
            'is_active' => false,
            'is_used' => true,
            'used_at' => now(),
        ])->save();
    }

    public function getStatusLabelAttribute(): string
    {
        $code = $this->branch?->branch_code ?: 'BR?';

        if ($this->is_used) {
            return "Used – {$code}";
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return "Expired – {$code}";
        }

        if ($this->is_active) {
            return "Active – {$code}";
        }

        return 'None';
    }
}
