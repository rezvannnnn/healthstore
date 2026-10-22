<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $addresses = Address::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return Inertia::render(
            'Account/Addresses/Index',
            [
                'addresses' => $addresses,
            ]
        );
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $phone = app(OtpService::class)->normalizePhone((string) $request->input('phone'));
        } catch (\RuntimeException $exception) {
            $phone = (string) $request->input('phone');
        }
        $request->merge([
            'phone' => $phone,
            'postal_code' => strtr((string) $request->input('postal_code'), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']),
        ]);
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'postal_code' => ['required', 'regex:/^[0-9]{10}$/'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $isDefault = (bool) ($validated['is_default'] ?? false);

        DB::transaction(function () use ($user, $validated, $isDefault): void {
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($isDefault) {
                Address::query()
                    ->where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            Address::create([
                'user_id' => $user->id,
                'title' => $validated['title'] ?? null,
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['phone'],
                'province' => $validated['province'] ?? null,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'],
                'postal_code' => $validated['postal_code'] ?? null,
                'is_default' => $isDefault,
            ]);
        });

        return back()->with(
            'status',
            'آدرس با موفقیت ثبت شد.'
        );
    }

    public function update(
        Request $request,
        int $address
    ): RedirectResponse {
        $model = Address::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($address);

        try {
            $phone = app(OtpService::class)->normalizePhone((string) $request->input('phone'));
        } catch (\RuntimeException $exception) {
            $phone = (string) $request->input('phone');
        }
        $request->merge([
            'phone' => $phone,
            'postal_code' => strtr((string) $request->input('postal_code'), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']),
        ]);
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'postal_code' => ['required', 'regex:/^[0-9]{10}$/'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $isDefault = (bool) ($validated['is_default'] ?? false);

        DB::transaction(function () use ($user, $model, $validated, $isDefault): void {
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $model->refresh();

            if ($isDefault) {
                Address::query()
                    ->where('user_id', $user->id)
                    ->whereKeyNot($model->id)
                    ->update(['is_default' => false]);
            }

            $model->update([
                'title' => $validated['title'] ?? null,
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['phone'],
                'province' => $validated['province'] ?? null,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'],
                'postal_code' => $validated['postal_code'] ?? null,
                'is_default' => $isDefault,
            ]);
        });

        return back()->with(
            'status',
            'آدرس با موفقیت به‌روزرسانی شد.'
        );
    }

    public function destroy(
        Request $request,
        int $address
    ): RedirectResponse {
        $user = $request->user();

        DB::transaction(function () use ($user, $address): void {
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $model = Address::query()
                ->where('user_id', $user->id)
                ->findOrFail($address);

            $wasDefault = $model->is_default;
            $model->delete();

            if ($wasDefault) {
                $newDefault = Address::query()
                    ->where('user_id', $user->id)
                    ->orderBy('id')
                    ->first();

                if ($newDefault) {
                    $newDefault->update([
                        'is_default' => true,
                    ]);
                }
            }
        });

        return back()->with(
            'status',
            'آدرس با موفقیت حذف شد.'
        );
    }
}
