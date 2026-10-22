<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Security', ['enabled' => (bool) $user->two_factor_confirmed_at, 'secret' => $request->session()->get('mfa.pending_secret'), 'recoveryCodes' => $request->session()->get('mfa.recovery_codes', [])]);
    }

    private function password(Request $request): void
    {
        $request->validate(['password' => ['required', 'string', 'max:255']]);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'رمز عبور صحیح نیست.']);
        }
    }

    public function prepare(Request $request, TwoFactorService $service): RedirectResponse
    {
        $this->password($request);
        abort_if((bool) $request->user()->two_factor_confirmed_at, 409);
        $request->session()->put('mfa.pending_secret', $service->generateSecret());
        $request->session()->put('mfa.pending_at', now()->getTimestamp());

        return back();
    }

    public function enable(Request $request, TwoFactorService $service): RedirectResponse
    {
        $this->password($request);
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $secret = $request->session()->get('mfa.pending_secret');
        if (! is_string($secret) || now()->getTimestamp() - (int) $request->session()->get('mfa.pending_at', 0) > 600 || $service->matchingCounter($secret, $data['code']) === null) {
            throw ValidationException::withMessages(['code' => 'کد صحیح نیست یا زمان فعال‌سازی تمام شده است.']);
        }
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = bin2hex(random_bytes(8));
        }
        $user = $request->user();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_last_counter = $service->matchingCounter($secret, $data['code']);
        $user->two_factor_recovery_codes = array_map(fn ($code) => Hash::make($code), $codes);
        $user->save();
        $request->session()->forget(['mfa.pending_secret', 'mfa.pending_at']);
        $request->session()->flash('mfa.recovery_codes', $codes);
        $request->session()->put('admin_mfa_verified', $user->id);

        return back()->with('success', 'ورود دومرحله‌ای فعال شد. کدهای بازیابی را در جای امن نگه دارید.');
    }

    public function disable(Request $request, TwoFactorService $service): RedirectResponse
    {
        $this->password($request);
        $data = $request->validate(['code' => ['required', 'string', 'max:100']]);
        if (! $service->verify($request->user(), $data['code'])) {
            throw ValidationException::withMessages(['code' => 'کد صحیح نیست.']);
        }
        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_last_counter = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return back()->with('success', 'ورود دومرحله‌ای غیرفعال شد.');
    }
}
