<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageAddOn extends Model
{
    protected $fillable = [
        'package_id',
        'name',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function caseAddOns()
    {
        return $this->hasMany(CaseAddOn::class);
    }
}
