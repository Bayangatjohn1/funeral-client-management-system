<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\BranchScoped;

class Deceased extends Model
{
    use BranchScoped;

    protected $table = 'deceased';

    protected $fillable = [
        'branch_id',
        'client_id',
        'address',
        'full_name',
        'born',
        'died',
        'date_of_death',
        'age',
        'interment',
        'wake_days',
        'interment_at',
        'place_of_cemetery',
        'senior_citizen_status',
        'senior_citizen_id_number',
        'photo_path',
        'senior_proof_path',
    ];

    protected $casts = [
        'born' => 'date',
        'died' => 'date',
        'date_of_death' => 'date',
        'interment' => 'date',
        'interment_at' => 'datetime',
        'senior_citizen_status' => 'boolean',
    ];

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
