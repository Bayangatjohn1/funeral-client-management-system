<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageInclusion extends Model
{
    protected $fillable = [
        'package_id',
        'service_type',
        'casket_catalog_id',
        'is_custom',
        'inclusion_name',
        'included_kilometers',
        'price_per_excess_kilometer',
        'included_days',
        'price_per_extended_day',
        'casket_type',
        'sort_order',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'included_kilometers' => 'integer',
        'price_per_excess_kilometer' => 'decimal:2',
        'included_days' => 'integer',
        'price_per_extended_day' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function getServiceTypeAttribute($value): string
    {
        return Package::normalizeServiceType($value);
    }

    public function setServiceTypeAttribute($value): void
    {
        $this->attributes['service_type'] = Package::normalizeServiceType($value);
    }

    public function casketCatalog()
    {
        return $this->belongsTo(CasketCatalog::class);
    }
}
