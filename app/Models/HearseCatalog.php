<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HearseCatalog extends Model
{
    protected $fillable = ['reference_value', 'is_available', 'name', 'description', 'is_active'];

    protected $casts = ['reference_value' => 'decimal:2', 'is_available' => 'boolean', 'is_active' => 'boolean'];

    public function packageInclusions() { return $this->hasMany(PackageInclusion::class); }

    public function funeralCases()
    {
        return $this->hasMany(FuneralCase::class);
    }
}
