<?php

namespace App\Http\Controllers;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RegistrationController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    /**
     * Send an OTP for the registration process.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => [
                'required',
                'string',
            ],
        ]);

        try {
            $phone = $this->otpService->normalizePhone(
                $validated['phone']
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'phone' => $exception->getMessage(),
            ]);
        }

        /*
         * A phone number that already belongs to a user
         * cannot start a new registration flow.
         */
        if (
            User::query()
                ->where('phone', $phone)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'phone' => 'این شماره موبایل قبلاً ثبت شده است.',
            ]);
        }

        /*
         * Clear any previous registration verification
         * in the current session.
         */
        $request->session()->forget([
            'registration_phone',
            'registration_phone_verified',
        ]);

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

    /**
     * Verify the OTP for the registration process.
     */
    public function verifyOtp(Request $request): RedirectResponse
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
            $phone = $this->otpService->normalizePhone(
                $validated['phone']
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'phone' => $exception->getMessage(),
            ]);
        }

        if (
            User::query()
                ->where('phone', $phone)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'phone' => 'این شماره موبایل قبلاً ثبت شده است.',
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

        /*
         * Store the verified phone in the current session.
         */
        $request->session()->put([
            'registration_phone' => $phone,
            'registration_phone_verified' => true,
        ]);

        return back()->with(
            'status',
            'شماره موبایل با موفقیت تأیید شد.'
        );
    }

    /**
     * Register a new customer account using the verified session phone.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
            ],

            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email',
            ],
        ]);

        try {
            $phone = $this->otpService->normalizePhone(
                $validated['phone']
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'phone' => $exception->getMessage(),
            ]);
        }

        $registrationPhone = $request->session()->get(
            'registration_phone'
        );

        $registrationPhoneVerified = (bool) $request->session()->get(
            'registration_phone_verified',
            false
        );

        /*
         * Registration is allowed only when the current session
         * contains a successfully verified phone.
         */
        if (
            ! $registrationPhoneVerified
            || ! $registrationPhone
            || $registrationPhone !== $phone
        ) {
            throw ValidationException::withMessages([
                'phone' => 'شماره موبایل هنوز در این فرایند ثبت‌نام تأیید نشده است.',
            ]);
        }

        /*
         * Do not allow registration with an existing phone number.
         */
        if (
            User::query()
                ->where('phone', $phone)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'phone' => 'این شماره موبایل قبلاً ثبت شده است.',
            ]);
        }

        /*
         * Retrieve the latest verified OTP associated with this phone.
         */
        $verifiedOtp = OtpVerification::query()
            ->where('phone', $phone)
            ->whereNotNull('verified_at')
            ->latest('id')
            ->first();

        if (! $verifiedOtp) {
            throw ValidationException::withMessages([
                'phone' => 'تأیید شماره موبایل پیدا نشد. لطفاً دوباره درخواست کد کنید.',
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'email' => $validated['email'] ?? null,
            'phone_verified_at' => $verifiedOtp->verified_at,
            'password' => null,
        ]);

        /*
         * Registration session data is no longer needed.
         */
        $request->session()->forget([
            'registration_phone',
            'registration_phone_verified',
        ]);

        Auth::login($user);

        /*
         * Prevent session fixation after authentication.
         */
        $request->session()->regenerate();

        return redirect()->intended(
            route('home')
        );
    }
}