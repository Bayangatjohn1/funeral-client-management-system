<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasketCatalog extends Model
{
    protected $fillable = [
        'name',
        'type_or_material',
        'standard_price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'standard_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function packageInclusions()
    {
        return $this->hasMany(PackageInclusion::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $material = trim((string) $this->type_or_material);

        return $material !== '' ? "{$this->name} ({$material})" : $this->name;
    }

    public function getPriceConfiguredAttribute(): bool
    {
        return (float) $this->standard_price > 0;
    }
}
