<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected CartService $cartService,
        protected InventoryReservationService $reservationService,
        protected ?CouponService $couponService = null,
        protected ?StorePricingService $pricingService = null
    ) {}

    public function createFromCart(
        Cart $cart,
        ?Address $address = null,
        ?string $couponCode = null
    ): Order {
        return DB::transaction(function () use ($cart, $address, $couponCode) {
            $cart->load('items.product');

            if ($cart->items->isEmpty()) {
                throw new RuntimeException('سبد خرید خالی است و امکان ایجاد سفارش وجود ندارد.');
            }

            if ($cart->status !== 'active') {
                throw new RuntimeException('این سبد خرید دیگر فعال نیست.');
            }

            if ($address && (int) $address->user_id !== (int) $cart->user_id) {
                throw new RuntimeException('آدرس انتخاب‌شده متعلق به این کاربر نیست.');
            }

            foreach ($cart->items as $item) {
                if (! $item->product) {
                    throw new RuntimeException('یکی از محصولات سبد خرید دیگر وجود ندارد.');
                }

                $availableQuantity = $this->inventoryService->getAvailableQuantity($item->product);

                if ($availableQuantity < $item->quantity) {
                    throw new RuntimeException("موجودی محصول «{$item->product->name}» کافی نیست.");
                }
            }

            $subtotal = $this->cartService->calculateSubtotal($cart);

            if ($subtotal <= 0) {
                throw new RuntimeException('مبلغ سفارش باید بیشتر از صفر باشد.');
            }

            $couponService = $this->couponService ?? app(CouponService::class);
            $couponResult = $couponService->prepareForOrder(
                $couponCode,
                (int) $cart->user_id,
                (float) $subtotal
            );
            $coupon = $couponResult['coupon'];
            $discountAmount = (float) $couponResult['discount_amount'];
            $pricingService = $this->pricingService ?? app(StorePricingService::class);
            $pricing = $pricingService->calculateTotal($subtotal, $discountAmount);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $cart->user_id,
                'address_id' => $address?->id,
                'customer_type' => 'b2c',
                'business_profile_id' => null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount_amount'],
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'shipping_amount' => $pricing['shipping_amount'],
                'total_amount' => $pricing['total_amount'],
                'currency' => 'IRR',
                'recipient_name' => $address?->recipient_name,
                'recipient_phone' => $address?->phone,
                'province' => $address?->province,
                'city' => $address?->city,
                'shipping_address' => $address?->address,
                'postal_code' => $address?->postal_code,
                'customer_note' => null,
                'admin_note' => null,
                'confirmed_at' => null,
                'paid_at' => null,
                'shipped_at' => null,
                'delivered_at' => null,
                'cancelled_at' => null,
            ]);

            foreach ($cart->items as $item) {
                $quantity = (int) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                $lineTotal = $unitPrice * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'total_amount' => $lineTotal,
                ]);
            }

            foreach ($cart->items->sortBy('product_id')->values() as $item) {
                $this->reservationService->reserve(
                    $order,
                    $item->product,
                    (int) $item->quantity
                );
            }

            if ($coupon) {
                $couponService->reserveForOrder(
                    $coupon,
                    (int) $cart->user_id,
                    $order
                );
            }

            $cart->update(['status' => 'converted']);

            $order->load([
                'items',
                'payments',
                'inventoryReservations',
                'address',
                'coupon',
                'couponUsages',
            ]);

            return $order;
        });
    }

    public function cancel(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $order) {
                throw new RuntimeException('سفارش پیدا نشد.');
            }

            if ($order->status === 'paid' || $order->payment_status === 'paid') {
                throw new RuntimeException('سفارش پرداخت‌شده قابل لغو نیست.');
            }

            if ($order->status === 'cancelled') {
                throw new RuntimeException('این سفارش قبلاً لغو شده است.');
            }

            if ($order->status !== 'pending') {
                throw new RuntimeException('این سفارش در وضعیت فعلی قابل لغو نیست.');
            }

            $reservations = $order->inventoryReservations()->where('status', 'active')->get();

            foreach ($reservations as $reservation) {
                if (! $this->reservationService->release($reservation)) {
                    throw new RuntimeException('آزادسازی رزرو موجودی سفارش انجام نشد.');
                }
            }

            ($this->couponService ?? app(CouponService::class))->releaseForOrder($order);

            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return true;
        });
    }

    public function setStatus(Order $order, string $newStatus): bool
    {
        return DB::transaction(function () use ($order, $newStatus) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $order) {
                throw new RuntimeException('سفارش پیدا نشد.');
            }

            $currentStatus = $order->status;

            if ($currentStatus === $newStatus) {
                throw new RuntimeException('سفارش از قبل در همین وضعیت قرار دارد.');
            }

            $allowedTransitions = [
                'pending' => ['paid', 'cancelled'],
                'paid' => ['processing'],
                'processing' => ['shipped'],
                'shipped' => ['delivered'],
            ];

            $allowedStatuses = $allowedTransitions[$currentStatus] ?? [];

            if (! in_array($newStatus, $allowedStatuses, true)) {
                throw new RuntimeException("تغییر وضعیت سفارش از «{$currentStatus}» به «{$newStatus}» مجاز نیست.");
            }

            if ($newStatus === 'processing' && $order->payment_status !== 'paid') {
                throw new RuntimeException('فقط سفارش پرداخت‌شده می‌تواند وارد مرحله پردازش شود.');
            }

            if ($newStatus === 'paid') {
                if ($order->payment_status !== 'paid') {
                    throw new RuntimeException('سفارش بدون پرداخت موفق نمی‌تواند به وضعیت paid برسد.');
                }

                $order->update([
                    'status' => 'paid',
                    'paid_at' => $order->paid_at ?? now(),
                    'confirmed_at' => $order->confirmed_at ?? now(),
                ]);

                return true;
            }

            $updates = ['status' => $newStatus];

            if ($newStatus === 'processing') {
                $updates['confirmed_at'] = $order->confirmed_at ?? now();
            }

            if ($newStatus === 'shipped') {
                $updates['shipped_at'] = now();
            }

            if ($newStatus === 'delivered') {
                $updates['delivered_at'] = now();
            }

            if ($newStatus === 'cancelled') {
                throw new RuntimeException('برای لغو سفارش از عملیات لغو سفارش استفاده کنید.');
            }

            $order->update($updates);

            return true;
        });
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
