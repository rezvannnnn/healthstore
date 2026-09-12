<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    /**
     * Login an existing customer using a verified OTP.
     */
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

        /*
         * Normalize the phone number before verification
         * and database lookup.
         */
        $phone = $this->otpService->normalizePhone(
            $validated['phone']
        );

        /*
         * The OTP must be valid and successfully verified.
         */
        $verified = $this->otpService->verify(
            $phone,
            $validated['code']
        );

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => 'کد تأیید واردشده صحیح نیست یا منقضی شده است.',
            ]);
        }

        /*
         * Only an existing registered customer may log in.
         */
        $user = User::query()
            ->where('phone', $phone)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'حسابی با این شماره موبایل پیدا نشد.',
            ]);
        }

        /*
         * Only a verified phone may be used for authentication.
         */
        if (! $user->hasVerifiedPhone()) {
            throw ValidationException::withMessages([
                'phone' => 'شماره موبایل این حساب هنوز تأیید نشده است.',
            ]);
        }

        /*
         * Authenticate the customer.
         */
        Auth::login($user);

        /*
         * Prevent session fixation after authentication.
         */
        $request->session()->regenerate();

        return redirect()->intended(
            route('home')
        );
    }

    /**
     * Request a new OTP for an existing customer.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => [
                'required',
                'string',
            ],
        ]);

        $phone = $this->otpService->normalizePhone(
            $validated['phone']
        );

        /*
         * Do not reveal whether a phone number is registered.
         * The same generic response can be used by the frontend.
         */
        $userExists = User::query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if (! $userExists) {
            throw ValidationException::withMessages([
                'phone' => 'حسابی با این شماره موبایل پیدا نشد.',
            ]);
        }

        $this->otpService->send(
            $phone
        );

        return back()->with(
            'status',
            'کد تأیید ارسال شد.'
        );
    }
}