<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddOnCatalog extends Model
{
    public const CHAPEL_VIEWING = 'Chapel Viewing';

    public const CATEGORY_OPTIONS = [
        'General',
        'Flowers',
        'Facilities',
        'Transportation',
        'Viewing Setup',
        self::CHAPEL_VIEWING,
        'Memorial Decor',
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

    public const DEFAULT_UNITS_BY_CATEGORY = [
        'General' => 'item',
        'Flowers' => 'set',
        'Facilities' => 'day',
        'Transportation' => 'trip',
        'Viewing Setup' => 'set',
        self::CHAPEL_VIEWING => 'day',
        'Memorial Decor' => 'service',
        'Media' => 'service',
        'Documents' => 'item',
        'Food & Refreshments' => 'item',
        'Memorial Items' => 'item',
        'Staff Assistance' => 'service',
    ];

    protected $fillable = ['is_available', 'branch_id', 'chapel_address', 'chapel_identity',
        'name',
        'category',
        'description',
        'price',
        'unit',
        'is_active',
    ];

    protected $casts = ['is_available' => 'boolean',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function legacyPackageAddOns()
    {
        return $this->belongsToMany(PackageAddOn::class, 'add_on_catalog_package_add_on');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getChapelReadyAttribute(): bool
    {
        return $this->category === self::CHAPEL_VIEWING && $this->branch_id && filled($this->chapel_address)
            && $this->unit === 'day' && $this->is_active && $this->is_available && $this->price !== null;
    }
}
