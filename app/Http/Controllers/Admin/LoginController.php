<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isAdminPanelUser() && $user->admin_active) {
            return redirect()->to($user->adminLandingPath() ?? route('admin.login'));
        }

        return Inertia::render('Admin/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
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

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(
            $user->adminLandingPath() ?? route('admin.login')
        );
    }
}
