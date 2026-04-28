<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function parseDateBounds(?string $dateFrom, ?string $dateTo): array
    {
        return [
            $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null,
            $dateTo ? Carbon::parse($dateTo)->endOfDay() : null,
        ];
    }
}
