<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDetail extends Model
{
    protected $fillable = [
        'funeral_case_id',
        'start_of_wake',
        'internment_date',
        'wake_days',
        'wake_location',
        'cemetery_place',
        'case_status',
    ];

    protected $casts = [
        'start_of_wake'   => 'date',
        'internment_date' => 'date',
        'wake_days'       => 'integer',
    ];

    public function funeralCase()
    {
        return $this->belongsTo(FuneralCase::class);
    }
}
