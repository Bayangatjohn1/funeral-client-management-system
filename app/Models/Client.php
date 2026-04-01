<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\BranchScoped;

class Client extends Model
{
    use BranchScoped;

    protected $fillable = [
        'branch_id',
        'full_name',
        'relationship_to_deceased',
        'contact_number',
        'address',
    ];

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
}
