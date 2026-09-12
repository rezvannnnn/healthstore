<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'province' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            Address::query()
                ->where('user_id', $request->user()->id)
                ->update([
                    'is_default' => false,
                ]);
        }

        Address::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?? null,
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'province' => $validated['province'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'],
            'postal_code' => $validated['postal_code'] ?? null,
            'is_default' => $isDefault,
        ]);

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

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'province' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            Address::query()
                ->where('user_id', $request->user()->id)
                ->whereKeyNot($model->id)
                ->update([
                    'is_default' => false,
                ]);
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

        return back()->with(
            'status',
            'آدرس با موفقیت به‌روزرسانی شد.'
        );
    }

    public function destroy(
        Request $request,
        int $address
    ): RedirectResponse {
        $model = Address::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($address);

        $wasDefault = $model->is_default;

        $model->delete();

        if ($wasDefault) {
            $newDefault = Address::query()
                ->where('user_id', $request->user()->id)
                ->orderBy('id')
                ->first();

            if ($newDefault) {
                $newDefault->update([
                    'is_default' => true,
                ]);
            }
        }

        return back()->with(
            'status',
            'آدرس با موفقیت حذف شد.'
        );
    }
}
