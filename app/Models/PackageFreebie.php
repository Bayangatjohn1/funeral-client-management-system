<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageFreebie extends Model
{
    protected $fillable = [
        'package_id',
        'freebie_name',
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
