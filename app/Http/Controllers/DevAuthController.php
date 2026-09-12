<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class DevAuthController extends Controller
{
    /**
     * Development-only login.
     *
     * This route is intended only for local development so that
     * the application can exercise the real Laravel session/auth
     * flow before the actual login/register UI is implemented.
     */
    public function login(): RedirectResponse
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        $user = User::find(1);

        if (! $user) {
            throw new RuntimeException(
                'کاربر توسعه‌ای با ID = 1 پیدا نشد.'
            );
        }

        Auth::login($user);

        request()->session()->regenerate();

        return redirect()
            ->route('checkout.show')
            ->with(
                'success',
                'ورود توسعه‌ای با موفقیت انجام شد.'
            );
    }

    /**
     * Development-only logout.
     */
    public function logout(): RedirectResponse
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('checkout.show')
            ->with(
                'info',
                'از حساب توسعه‌ای خارج شدید.'
            );
    }
}