<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPackageDefault extends Model
{
    public $incrementing = false;

    public const SERVICE_FIELDS = [
        Package::SERVICE_EMBALMING => ['embalming_days', 'embalming_extra_rate', 'days'],
        Package::SERVICE_HOME_VIEWING => ['home_viewing_days', 'home_viewing_extra_rate', 'days'],
        Package::SERVICE_BODY_RETRIEVAL => ['retrieval_kilometers', 'retrieval_extra_rate', 'km'],
        Package::SERVICE_HEARSE => ['hearse_kilometers', 'hearse_extra_rate', 'km'],
    ];

    protected $fillable = ['embalming_days', 'home_viewing_days', 'retrieval_kilometers', 'hearse_kilometers',
        'embalming_extra_rate', 'home_viewing_extra_rate', 'retrieval_extra_rate', 'hearse_extra_rate'];

    protected $casts = ['embalming_days' => 'integer', 'home_viewing_days' => 'integer', 'retrieval_kilometers' => 'decimal:2'];

    public function coverage(): array
    {
        return array_map(fn ($fields) => $this->{$fields[0]}, self::SERVICE_FIELDS);
    }

    public function rates(): array
    {
        return array_map(fn ($fields) => $this->{$fields[1]}, self::SERVICE_FIELDS);
    }
}
