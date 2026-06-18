<?php

namespace App\Http\Controllers\Platform\Auth;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TotpService $totp) {}

    // ── Challenge TOTP (après login password) ────────────────────────────────

    public function showChallenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa_pending_user_id')) {
            return redirect()->route('platform.login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $userId   = $request->session()->get('2fa_pending_user_id');
        $remember = $request->session()->get('2fa_pending_remember', false);

        if (! $userId) {
            return redirect()->route('platform.login')
                ->withErrors(['code' => 'Session expirée. Reconnectez-vous.']);
        }

        $user = \App\Models\User::find($userId);

        if (! $user || ! $this->totp->verify($user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code invalide. Vérifiez votre application d\'authentification.']);
        }

        $request->session()->forget(['2fa_pending_user_id', '2fa_pending_remember']);
        $request->session()->regenerate();

        Auth::login($user, $remember);
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('platform.dashboard'));
    }

    // ── Challenge code de secours ────────────────────────────────────────────

    public function verifyBackupCode(Request $request): RedirectResponse
    {
        $request->validate(['backup_code' => ['required', 'string', 'max:20']]);

        $userId   = $request->session()->get('2fa_pending_user_id');
        $remember = $request->session()->get('2fa_pending_remember', false);

        if (! $userId) {
            return redirect()->route('platform.login')
                ->withErrors(['backup_code' => 'Session expirée. Reconnectez-vous.']);
        }

        $user = \App\Models\User::find($userId);

        if (! $user || ! $user->consumeRecoveryCode($request->input('backup_code'))) {
            return back()->withErrors(['backup_code' => 'Code de secours invalide ou déjà utilisé.']);
        }

        $request->session()->forget(['2fa_pending_user_id', '2fa_pending_remember']);
        $request->session()->regenerate();

        Auth::login($user, $remember);
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('platform.dashboard'));
    }

    // ── Setup (dans le profil) ────────────────────────────────────────────────

    public function showSetup(): View
    {
        $user   = Auth::user();
        $secret = null;
        $qrUri  = null;

        if (! $user->hasTwoFactorEnabled()) {
            $secret = session('2fa_pending_secret') ?? $this->totp->generateSecret();
            session(['2fa_pending_secret' => $secret]);
            $qrUri = $this->totp->qrCodeUri('RansomShield SOC', $user->email, $secret);
        }

        return view('platform.users.two-factor', [
            'user'      => $user,
            'secret'    => $secret,
            'qrUri'     => $qrUri,
            'remaining' => $user->hasTwoFactorEnabled() ? $user->recoveryCodesRemaining() : null,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $secret = session('2fa_pending_secret');

        if (! $secret || ! $this->totp->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code invalide. Scannez à nouveau le QR code et réessayez.']);
        }

        $user = Auth::user();
        $user->update([
            'two_factor_secret'       => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        session()->forget('2fa_pending_secret');

        $plain = $user->generateRecoveryCodes();
        session(['2fa_recovery_codes_flash' => $plain]);

        return redirect()->route('platform.two-factor.recovery-codes')
            ->with('success', '2FA activée. Conservez ces codes de secours dans un endroit sûr.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = Auth::user();

        if (! $this->totp->verify($user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code invalide. La 2FA reste active.']);
        }

        $user->update([
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
        ]);

        return redirect()->route('platform.two-factor.setup')
            ->with('success', 'Authentification à deux facteurs désactivée.');
    }

    // ── Codes de secours ─────────────────────────────────────────────────────

    public function showRecoveryCodes(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('platform.two-factor.setup');
        }

        $flash = session()->pull('2fa_recovery_codes_flash');

        return view('platform.users.recovery-codes', [
            'user'       => $user,
            'flashCodes' => $flash,
            'remaining'  => $user->recoveryCodesRemaining(),
        ]);
    }

    public function regenerateCodes(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = Auth::user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('platform.two-factor.setup');
        }

        if (! $this->totp->verify($user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code TOTP invalide.']);
        }

        $plain = $user->generateRecoveryCodes();
        session(['2fa_recovery_codes_flash' => $plain]);

        return redirect()->route('platform.two-factor.recovery-codes')
            ->with('success', 'Nouveaux codes de secours générés. Conservez-les précieusement.');
    }
}
