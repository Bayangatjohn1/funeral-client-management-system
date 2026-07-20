<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreebieCatalog extends Model
{
    protected $fillable = [
        'name',
        'default_unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function packageFreebies()
    {
        return $this->hasMany(PackageFreebie::class);
    }
}
