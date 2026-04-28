<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\BranchScoped;
use App\Support\NormalizesName;

class Deceased extends Model
{
    use BranchScoped, SoftDeletes, NormalizesName;

    protected $table = 'deceased';

    protected $fillable = [
        'branch_id',
        'client_id',
        'address',
        // Legacy single-field name (preserved).
        'full_name',
        // Normalized name fields (Phase 1+).
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        // Legacy date columns (preserved).
        'born',
        'died',
        'date_of_death',
        // Canonical date-of-birth (Phase 1+, mirrors born).
        'date_of_birth',
        'age',
        'interment',
        'wake_days',
        'interment_at',
        'place_of_cemetery',
        // Legacy senior flag (preserved).
        'senior_citizen_status',
        // Canonical senior flag (Phase 1+, mirrors senior_citizen_status).
        'is_senior',
        'senior_citizen_id_number',
        'photo_path',
        'senior_proof_path',
    ];

    protected $casts = [
        'born'                  => 'date',
        'died'                  => 'date',
        'date_of_death'         => 'date',
        'date_of_birth'         => 'date',
        'interment'             => 'date',
        'interment_at'          => 'datetime',
        'senior_citizen_status' => 'boolean',
        'is_senior'             => 'boolean',
        'deleted_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $deceased) {
            $deceased->normalizeNameFields();

            // Sync died ↔ date_of_death — whichever was just written updates the other.
            if ($deceased->isDirty('date_of_death') && ! $deceased->isDirty('died')) {
                $deceased->died = $deceased->date_of_death;
            } elseif ($deceased->isDirty('died') && ! $deceased->isDirty('date_of_death')) {
                $deceased->date_of_death = $deceased->died;
            }

            // Sync born ↔ date_of_birth.
            if ($deceased->isDirty('date_of_birth') && ! $deceased->isDirty('born')) {
                $deceased->born = $deceased->date_of_birth;
            } elseif ($deceased->isDirty('born') && ! $deceased->isDirty('date_of_birth')) {
                $deceased->date_of_birth = $deceased->born;
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function funeralCase()
    {
        return $this->hasOne(\App\Models\FuneralCase::class, 'deceased_id');
    }
}
