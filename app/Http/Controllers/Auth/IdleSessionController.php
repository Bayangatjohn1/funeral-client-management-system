<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\IdleSession;
use Illuminate\Http\Request;

class IdleSessionController extends Controller
{
    public function status(Request $request)
    {
        return response()->json(IdleSession::state($request));
    }

    public function keepAlive(Request $request)
    {
        // A stale tab must not extend a newly logged-in account's session.
        if (! hash_equals((string) $request->session()->get('idle_session_key'), (string) $request->header('X-Idle-Session'))) {
            return response()->json(['message' => 'This tab belongs to a different sign-in. Reload the page.'], 409);
        }
        $request->session()->put('idle_last_activity', now()->timestamp);
        return response()->json(IdleSession::state($request));
    }
}
