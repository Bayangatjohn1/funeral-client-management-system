<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseAddOn extends Model
{
    protected $fillable = [
        'funeral_case_id',
        'package_add_on_id',
        'add_on_name_snapshot',
        'add_on_description_snapshot',
        'add_on_price_snapshot',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'add_on_price_snapshot' => 'decimal:2',
        'quantity' => 'integer',
        'line_total' => 'decimal:2',
    ];

    public function funeralCase()
    {
        return $this->belongsTo(FuneralCase::class);
    }

    public function packageAddOn()
    {
        return $this->belongsTo(PackageAddOn::class);
    }
}
