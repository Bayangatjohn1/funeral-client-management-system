<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id',
        'actor_role',
        'action',
        'action_label',
        'action_type',
        'entity_type',
        'entity_id',
        'branch_id',
        'target_branch_id',
        'ip_address',
        'user_agent',
        'status',
        'remarks',
        'transaction_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        // Preserve audit integrity: block edits or deletions of stored logs.
        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
