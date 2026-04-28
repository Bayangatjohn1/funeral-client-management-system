<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'branch_code',
        'branch_name',
        'branch_type',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function isMain(): bool
    {
        // branch_type column is authoritative when present (Phase 1+).
        // Falls back to branch_code convention for pre-migration compatibility.
        if (! empty($this->branch_type)) {
            return $this->branch_type === 'main';
        }

        return $this->branch_code === 'BR001';
    }

    public function funeralCases()
    {
        return $this->hasMany(\App\Models\FuneralCase::class);
    }

    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function clients()
    {
        return $this->hasMany(\App\Models\Client::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }
}
