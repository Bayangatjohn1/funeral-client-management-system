<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IdleSession
{
    public static function start(Request $request): void
    {
        $request->session()->put('idle_last_activity', now()->timestamp);
        $request->session()->put('idle_session_key', (string) Str::uuid());
    }

    public static function seconds(User $user): int
    {
        return $user->isStaff() ? 1800 : 900;
    }

    public static function state(Request $request): array
    {
        return ['expires_at' => (int) $request->session()->get('idle_last_activity') + self::seconds($request->user()),
            'server_now' => now()->timestamp, 'session_key' => $request->session()->get('idle_session_key')];
    }
}
