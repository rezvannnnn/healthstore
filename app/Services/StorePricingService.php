<?php

namespace App\Services;

use App\Models\StoreSetting;
use RuntimeException;

class StorePricingService
{
    public function calculateShipping(float $subtotal): float
    {
        $shippingFee = $this->numericSetting('shipping_fee');
        $freeShippingThreshold = $this->numericSetting('free_shipping_threshold');

        if ($shippingFee <= 0 || ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold)) {
            return 0;
        }

        return $shippingFee;
    }

    public function minimumOrderAmount(): float
    {
        return $this->numericSetting('min_order_amount');
    }

    public function calculateTotal(float $subtotal, float $discountAmount = 0): array
    {
        $minimumOrderAmount = $this->minimumOrderAmount();

        if ($minimumOrderAmount > 0 && $subtotal < $minimumOrderAmount) {
            throw new RuntimeException(
                'حداقل مبلغ سفارش '.number_format($minimumOrderAmount, 0, '.', ',').' تومان است.'
            );
        }

        $discountAmount = max(0, min($discountAmount, $subtotal));
        $shippingAmount = $this->calculateShipping($subtotal);
        $totalAmount = $subtotal - $discountAmount + $shippingAmount;

        if ($totalAmount <= 0) {
            throw new RuntimeException('مبلغ نهایی سفارش باید بیشتر از صفر باشد.');
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'shipping_amount' => $shippingAmount,
            'total_amount' => $totalAmount,
        ];
    }

    protected function numericSetting(string $key): float
    {
        return max(0, (float) StoreSetting::getValue($key, '0'));
    }
}
