<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountSecurity;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::min(12)->letters()->numbers()],
        ]);

        $status = DB::transaction(function () use ($request) {
            // Serialize resets for this account, including validation and token consumption.
            User::where('email', $request->email)->lockForUpdate()->first();
            return Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user) use ($request) {
                    $user->forceFill([
                        'password' => Hash::make($request->password),
                        'must_change_password' => false,
                        // Possession of the single-use emailed token proves inbox ownership.
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();
                    AccountSecurity::revoke($user);
                    AccountSecurity::audit('user.password_recovered', $user, ['method' => 'email_link']);
                    DB::afterCommit(fn () => event(new PasswordReset($user)));
                }
            );
        });

        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your new password. Your authenticator is still required if enabled.')
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'This reset link is invalid or expired. Request a new link and use the email address it was sent to.']);
    }
}
