<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LoginController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => [
                'required',
                'string',
            ],
            'code' => [
                'required',
                'string',
                'digits:6',
            ],
        ]);

        try {
            $phone = $this->otpService->normalizePhone($validated['phone']);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'phone' => $exception->getMessage(),
            ]);
        }

        $verified = $this->otpService->verify(
            $phone,
            $validated['code']
        );

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => 'کد تأیید واردشده صحیح نیست یا منقضی شده است.',
            ]);
        }

        $user = User::query()
            ->where('phone', $phone)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'حسابی با این شماره موبایل پیدا نشد.',
            ]);
        }

        if ($user->isAdminPanelUser()) {
            throw ValidationException::withMessages([
                'phone' => 'حساب‌های مدیریتی باید از بخش مدیریت وارد شوند.',
            ]);
        }

        if (! $user->hasVerifiedPhone()) {
            throw ValidationException::withMessages([
                'phone' => 'شماره موبایل این حساب هنوز تأیید نشده است.',
            ]);
        }

        Auth::login($user);
        $request->session()->forget('password_hash_'.Auth::getDefaultDriver());
        $request->session()->regenerate();
        app(CartService::class)->restoreIntent($request, $user->id);

        return redirect()->intended(
            route('home')
        );
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => [
                'required',
                'string',
            ],
        ]);

        try {
            $phone = $this->otpService->normalizePhone($validated['phone']);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'phone' => $exception->getMessage(),
            ]);
        }

        $userExists = User::query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->where('role', User::ROLE_USER)
            ->exists();

        if (! $userExists) {
            return back()->with('status', 'کد تأیید ارسال شد.');
        }

        try {
            $this->otpService->send($phone);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'phone' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'status',
            'کد تأیید ارسال شد.'
        );
    }
}
