<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageInclusion extends Model
{
    protected $fillable = [
        'package_id',
        'inclusion_name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
