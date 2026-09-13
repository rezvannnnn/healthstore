<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        $coupons = Coupon::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Coupon::create($this->validatedData($request));

        return to_route('admin.coupons.index')->with('success', 'کد تخفیف با موفقیت ایجاد شد.');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validatedData($request, $coupon));

        return to_route('admin.coupons.index')->with('success', 'کد تخفیف با موفقیت ویرایش شد.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return to_route('admin.coupons.index')->with('success', 'کد تخفیف حذف شد.');
    }

    protected function validatedData(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'type' => ['required', 'string', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'gt:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'gt:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if ($data['type'] === 'percent' && (float) $data['value'] > 100) {
            abort(422, 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.');
        }

        return $data;
    }
}
