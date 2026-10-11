<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class AccountSecurityController extends Controller
{
    public function setup(Request $request)
    {
        abort_unless($request->user()->requiresMfa(), 403);
        if ($request->user()->two_factor_confirmed_at && $request->user()->must_change_password) {
            if ($request->session()->get('mfa_user_id') !== $request->user()->id) return redirect()->route('security.challenge');
            return redirect()->route('security.password');
        }
        if ($request->user()->two_factor_confirmed_at && ! $request->user()->must_change_password) {
            return redirect()->route('security.challenge');
        }
        if (! $request->session()->has('mfa_setup_secret')) {
            $request->session()->put('mfa_setup_secret', Crypt::encryptString($request->user()->two_factor_secret ?: (new Google2FA)->generateSecretKey()));
        }
        $secret = Crypt::decryptString($request->session()->get('mfa_setup_secret'));
        $uri = (new Google2FA)->getQRCodeUrl(config('app.name', 'Funeral System'), $request->user()->email, $secret);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd)))->writeString($uri);
        return view('auth.account-security', ['mode' => 'setup', 'secret' => $secret,
            'qrCode' => 'data:image/svg+xml;base64,'.base64_encode($svg)]);
    }

    public function enroll(Request $request)
    {
        abort_unless($request->user()->requiresMfa(), 403);
        abort_if($request->user()->two_factor_confirmed_at, 403);
        $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'code' => ['required', 'regex:/^\d{6}$/'],
        ]);
        $secret = $request->session()->get('mfa_setup_secret');
        if (! is_string($secret)) abort(419);
        $secret = Crypt::decryptString($secret);
        $step = (new Google2FA)->verifyKeyNewer($secret, $request->string('code')->toString(), $request->user()->two_factor_last_step ?? 0, 1);
        if ($step === false) throw ValidationException::withMessages(['code' => 'Enter a valid authenticator code.']);

        $codes = collect(range(1, 8))->map(fn () => Str::random(20))->all();
        DB::transaction(function () use ($request, $secret, $step, $codes) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $this->ensureCurrentAccount($request, $user);
            abort_unless(! $user->two_factor_confirmed_at, 403);
            $user->two_factor_secret = $secret;
            $user->two_factor_last_step = $step;
            $user->two_factor_confirmed_at = now();
            $user->two_factor_recovery_codes = array_map(fn ($code) => Hash::make($code), $codes);
            AccountSecurity::revoke($user);
            AccountSecurity::audit('user.mfa_enabled', $user, []);
            $request->session()->put('account_security', AccountSecurity::fingerprint($user));
        });
        $request->user()->refresh();
        $request->session()->regenerate();
        $request->session()->forget('mfa_setup_secret');
        $request->session()->put('mfa_user_id', $request->user()->id);
        $request->session()->put('mfa_recovery_display', Crypt::encryptString(json_encode($codes)));
        return redirect()->route('security.recovery');
    }

    public function recovery(Request $request)
    {
        abort_unless($request->user()->requiresMfa() && $request->session()->get('mfa_user_id') === $request->user()->id, 403);
        $display = $request->session()->pull('mfa_recovery_display');
        $codes = $display ? json_decode(Crypt::decryptString($display), true) : [];
        return view('auth.account-security', ['mode' => 'recovery', 'codes' => $codes]);
    }

    public function challenge(Request $request)
    {
        abort_unless($request->user()->requiresMfa(), 403);
        if (! $request->user()->two_factor_confirmed_at) return redirect()->route('security.setup');
        return view('auth.account-security', ['mode' => 'challenge']);
    }

    public function verify(Request $request)
    {
        abort_unless($request->user()->requiresMfa(), 403);
        $request->validate(['code' => ['required', 'string', 'max:64']]);
        DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $this->ensureCurrentAccount($request, $user);
            abort_unless($user->two_factor_confirmed_at && $user->two_factor_secret, 403);
            $code = trim($request->input('code'));
            $step = preg_match('/^\d{6}$/', $code)
                ? (new Google2FA)->verifyKeyNewer($user->two_factor_secret, $code, $user->two_factor_last_step ?? 0, 1)
                : false;
            if ($step !== false) {
                $user->two_factor_last_step = $step;
            } else {
                $codes = $user->two_factor_recovery_codes ?? [];
                $index = collect($codes)->search(fn ($hash) => Hash::check($code, $hash));
                if ($index === false) throw ValidationException::withMessages(['code' => 'Invalid or already used code.']);
                unset($codes[$index]);
                $user->two_factor_recovery_codes = array_values($codes);
            }
            $user->save();
            AccountSecurity::audit('user.mfa_verified', $user, []);
        });
        $request->session()->regenerate();
        $request->session()->put('mfa_user_id', $request->user()->id);
        return redirect($request->user()->must_change_password ? route('security.password') : $this->dashboardPath($request->user()));
    }

    public function password(Request $request)
    {
        if ($redirect = $this->passwordChangeRedirect($request)) return $redirect;
        return view('auth.change-temporary-password');
    }

    public function changePassword(Request $request)
    {
        if ($redirect = $this->passwordChangeRedirect($request)) return $redirect;
        $request->validate([
            'password' => ['required', 'string', 'max:128', 'confirmed', Password::min(12)->letters()->numbers()],
        ], ['password.confirmed' => 'Passwords do not match.']);
        DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $this->ensureCurrentAccount($request, $user);
            abort_unless($user->must_change_password && $user->two_factor_confirmed_at, 403);
            if (Hash::check($request->input('password'), $user->password)) {
                throw ValidationException::withMessages(['password' => 'Choose a password different from your temporary password.']);
            }
            $user->password = Hash::make($request->input('password'));
            $user->must_change_password = false;
            AccountSecurity::revoke($user);
            AccountSecurity::audit('user.temporary_password_changed', $user, []);
            $request->session()->put('account_security', AccountSecurity::fingerprint($user));
        });
        $request->user()->refresh();
        $request->session()->regenerate();
        return redirect($this->dashboardPath($request->user()))->with('personal_password_set', true);
    }

    private function passwordChangeRedirect(Request $request): ?\Illuminate\Http\RedirectResponse
    {
        $this->ensureCurrentAccount($request, $request->user());
        if (! $request->user()->two_factor_confirmed_at) return redirect()->route('security.setup');
        if ($request->session()->get('mfa_user_id') !== $request->user()->id) return redirect()->route('security.challenge');
        if (! $request->user()->must_change_password) {
            return redirect($this->dashboardPath($request->user()))->with('success', 'Your personal password is already set.');
        }
        return null;
    }

    private function dashboardPath(User $user): string
    {
        return $user->isStaff() ? '/staff' : '/admin';
    }

    private function ensureCurrentAccount(Request $request, User $user): void
    {
        abort_unless($user->is_active && $user->requiresMfa()
            && hash_equals(AccountSecurity::fingerprint($user), (string) $request->session()->get('account_security')), 403);
        if (($user->isStaff() || $user->isBranchAdmin()) && $user->branch_id) {
            abort_unless($user->branch?->is_active, 403);
        }
    }
}
