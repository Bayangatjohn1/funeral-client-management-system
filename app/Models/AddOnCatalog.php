<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddOnCatalog extends Model
{
    public const CATEGORY_OPTIONS = [
        'General',
        'Flowers',
        'Transportation',
        'Viewing Setup',
        'Media',
        'Documents',
        'Food & Refreshments',
        'Memorial Items',
        'Staff Assistance',
    ];

    public const UNIT_OPTIONS = [
        'item',
        'set',
        'day',
        'trip',
        'service',
    ];

    protected $fillable = [
        'name',
        'category',
        'description',
        'price',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function legacyPackageAddOns()
    {
        return $this->belongsToMany(PackageAddOn::class, 'add_on_catalog_package_add_on');
    }
}
