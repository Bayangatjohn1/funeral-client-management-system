<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemporaryCrossBranchPermission extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $permission) {
            if (($permission->is_active ?? true) && !$permission->is_used
                && (!$permission->exists || $permission->isDirty(['is_active', 'is_used', 'user_id', 'allowed_branch_id', 'expires_at']))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'permission' => 'Temporary cross-branch encoding is disabled. Use the assigned branch.',
                ]);
            }
        });
    }

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
        return $query->whereRaw('1 = 0');
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
            return "Disabled legacy grant – {$code}";
        }

        return 'None';
    }
}
