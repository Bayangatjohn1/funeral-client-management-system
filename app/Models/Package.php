<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'coffin_type',
        'price',
        'inclusions',
        'freebies',
        'promo_label',
        'promo_value_type',
        'promo_value',
        'promo_starts_at',
        'promo_ends_at',
        'promo_is_active',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promo_value' => 'decimal:2',
        'promo_starts_at' => 'datetime',
        'promo_ends_at' => 'datetime',
        'promo_is_active' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function funeralCases()
    {
        return $this->hasMany(\App\Models\FuneralCase::class);
    }
}
