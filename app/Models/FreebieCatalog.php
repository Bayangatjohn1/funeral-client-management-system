<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreebieCatalog extends Model
{
    protected $fillable = ['is_available',
        'name',
        'default_unit',
        'is_active',
    ];

    protected $casts = ['is_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function packageFreebies()
    {
        return $this->hasMany(PackageFreebie::class);
    }
}
