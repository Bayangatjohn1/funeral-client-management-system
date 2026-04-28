<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\BranchScoped;
use App\Support\NormalizesName;

class Client extends Model
{
    use BranchScoped, SoftDeletes, NormalizesName;

    protected $fillable = [
        'branch_id',
        // Legacy single-field name (kept for backward compat; prefer split fields below).
        'full_name',
        // Normalized name fields (Phase 1+).
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        // Relationship fields.
        'relationship',           // canonical short name (Phase 1+)
        'relationship_to_deceased', // legacy name (preserved)
        'contact_number',
        'address',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $client) {
            $client->normalizeNameFields();
        });
    }

    /**
     * Canonical relationship accessor — reads from whichever column is populated.
     */
    public function getRelationshipAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        $legacy = $this->attributes['relationship_to_deceased'] ?? null;
        return ($legacy && $legacy !== 'Other') ? $legacy : null;
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function deceaseds()
    {
        return $this->hasMany(\App\Models\Deceased::class);
    }

    public function funeralCases()
    {
        return $this->hasMany(\App\Models\FuneralCase::class);
    }

    public function latestFuneralCase()
    {
        return $this->hasOne(\App\Models\FuneralCase::class)->latestOfMany('created_at');
    }
}
