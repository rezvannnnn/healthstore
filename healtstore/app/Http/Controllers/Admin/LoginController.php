<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user?->two_factor_confirmed_at && session('admin_mfa_verified') !== $user->id) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            $user = null;
        }
        if ($user?->isAdminPanelUser() && $user->admin_active) {
            if ($user->isAdmin()) {
                return app(DashboardController::class)();
            }

            $landing = $user->adminLandingPath();

            if ($landing !== null) {
                return redirect()->to($landing);
            }
        }

        return Inertia::render('Admin/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
            'two_factor_code' => ['nullable', 'string', 'max:100'],
        ]);

        $username = Str::lower(trim($data['username']));
        $user = User::query()
            ->where('admin_username', $username)
            ->whereNotNull('password')
            ->first();

        if (
            ! $user
            || ! $user->isAdminPanelUser()
            || ! $user->admin_active
            || ! Hash::check($data['password'], $user->password)
        ) {
            throw ValidationException::withMessages([
                'username' => 'نام کاربری یا رمز عبور صحیح نیست.',
            ]);
        }

        if ($user->two_factor_confirmed_at && ! app(TwoFactorService::class)->verify($user, (string) ($data['two_factor_code'] ?? ''))) {
            throw ValidationException::withMessages(['two_factor_code' => 'کد احراز هویت دوم یا کد بازیابی صحیح نیست.']);
        }
        Auth::login($user);
        $request->session()->forget('password_hash_'.Auth::getDefaultDriver());
        $request->session()->regenerate();
        $request->session()->put('admin_mfa_verified', $user->id);

        return redirect()->intended(
            $user->adminLandingPath() ?? route('admin.login')
        );
    }
}
