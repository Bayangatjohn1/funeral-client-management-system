<?php

namespace App\Support;

final class UiTextCase
{
    public static function words(?string $value): ?string
    {
        if ($value === null) return null;
        return preg_replace_callback('/\p{L}[\p{L}\p{M}\p{N}]*/u', fn($part)=>mb_strtoupper(mb_substr($part[0],0,1)).mb_substr($part[0],1), trim(preg_replace('/\s+/u',' ',$value)));
    }
}
