<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendAccountResetLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password', ['recoveryAvailable' => $this->recoveryAvailable()]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        if (! $this->recoveryAvailable()) {
            return back()->with('status', 'Email recovery is currently unavailable. Contact your administrator for assistance.');
        }

        $email = trim((string) $request->input('email'));
        $key = 'password-recovery:'.hash('sha256', strtolower($email));
        // Apply the same cooldown to registered and unknown addresses.
        RateLimiter::attempt($key, 1, fn () => SendAccountResetLink::dispatch($email), 60);
        return back()->with('status', 'If this email belongs to an account, a password reset link will be sent. Check your inbox and spam folder. You can request another link after 60 seconds.');
    }

    private function recoveryAvailable(): bool
    {
        return in_array(config('mail.default'), ['smtp', 'brevo', 'ses', 'postmark', 'resend'], true)
            || (app()->environment('testing') && config('mail.default') === 'array');
    }
}
