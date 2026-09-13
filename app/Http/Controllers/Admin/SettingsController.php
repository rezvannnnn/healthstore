<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const DEFAULTS = [
        'store_name' => 'داروخونه',
        'support_phone' => '',
        'support_email' => '',
        'store_address' => '',
        'shipping_fee' => '0',
        'free_shipping_threshold' => '0',
        'min_order_amount' => '0',
        'currency' => 'تومان',
        'timezone' => 'Asia/Tehran',
    ];

    public function index(): Response
    {
        $settings = self::DEFAULTS;

        foreach (StoreSetting::query()->get(['key', 'value']) as $setting) {
            if (array_key_exists($setting->key, $settings)) {
                $settings[$setting->key] = $setting->value ?? '';
            }
        }

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:50'],
            'timezone' => [
                'required',
                'string',
                'max:100',
                Rule::in(DateTimeZone::listIdentifiers()),
            ],
        ]);

        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                StoreSetting::setValue($key, (string) $value);
            }
        });

        return to_route('admin.settings.index')->with('success', 'تنظیمات فروشگاه ذخیره شد.');
    }
}
