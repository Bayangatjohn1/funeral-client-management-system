<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageFreebie extends Model
{
    protected $fillable = [
        'package_id',
        'freebie_catalog_id',
        'freebie_name',
        'quantity',
        'unit',
        'is_custom',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_custom' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function catalog()
    {
        return $this->belongsTo(FreebieCatalog::class, 'freebie_catalog_id');
    }
}
