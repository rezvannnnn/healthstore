<?php

namespace App\Services;

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
        protected InventoryReservationService $reservationService
    ) {
    }

    /**
     * Convert an active cart into an order and reserve inventory.
     *
     * Physical inventory is NOT reduced at this stage.
     * InventoryReservationService is responsible for creating
     * the reservation records.
     */
    public function createFromCart(Cart $cart): Order
    {
        return DB::transaction(function () use ($cart) {
            $cart->load('items.product');

            if ($cart->items->isEmpty()) {
                throw new RuntimeException(
                    'سبد خرید خالی است و امکان ایجاد سفارش وجود ندارد.'
                );
            }

            if ($cart->status !== 'active') {
                throw new RuntimeException(
                    'این سبد خرید دیگر فعال نیست.'
                );
            }

            /*
             * Validate every cart item before creating the order.
             */
            foreach ($cart->items as $item) {
                if (! $item->product) {
                    throw new RuntimeException(
                        'یکی از محصولات سبد خرید دیگر وجود ندارد.'
                    );
                }

                $availableQuantity = $this->inventoryService
                    ->getAvailableQuantity($item->product);

                if ($availableQuantity < $item->quantity) {
                    throw new RuntimeException(
                        "موجودی محصول «{$item->product->name}» کافی نیست."
                    );
                }
            }

            $subtotal = $this->cartService->calculateSubtotal($cart);

            if ($subtotal <= 0) {
                throw new RuntimeException(
                    'مبلغ سفارش باید بیشتر از صفر باشد.'
                );
            }

            /*
             * Create the order.
             */
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $cart->user_id,
                'customer_type' => 'b2c',
                'business_profile_id' => null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'shipping_amount' => 0,
                'total_amount' => $subtotal,
                'currency' => 'IRR',
                'recipient_name' => null,
                'recipient_phone' => null,
                'province' => null,
                'city' => null,
                'shipping_address' => null,
                'postal_code' => null,
                'customer_note' => null,
                'admin_note' => null,
                'confirmed_at' => null,
                'paid_at' => null,
                'shipped_at' => null,
                'delivered_at' => null,
                'cancelled_at' => null,
            ]);

            /*
             * Create order items and delegate all inventory
             * reservation logic to InventoryReservationService.
             */
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

                /*
                 * The reservation service handles:
                 * - inventory row locking
                 * - existing reservations
                 * - multi-warehouse allocation
                 * - expiration
                 * - rollback on insufficient stock
                 */
                $this->reservationService->reserve(
                    $order,
                    $item->product,
                    $quantity
                );
            }

            /*
             * Cart has now been converted into an order.
             */
            $cart->update([
                'status' => 'converted',
            ]);

            $order->load([
                'items',
                'payments',
                'inventoryReservations',
            ]);

            return $order;
        });
    }

    /**
     * Cancel a customer's own unpaid order and release
     * all active inventory reservations.
     */
    public function cancel(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'سفارش پیدا نشد.'
                );
            }

            /*
             * A paid order must not be cancelled through
             * the customer cancellation flow.
             */
            if (
                $order->status === 'paid'
                || $order->payment_status === 'paid'
            ) {
                throw new RuntimeException(
                    'سفارش پرداخت‌شده قابل لغو نیست.'
                );
            }

            /*
             * A cancelled order cannot be cancelled again.
             */
            if ($order->status === 'cancelled') {
                throw new RuntimeException(
                    'این سفارش قبلاً لغو شده است.'
                );
            }

            /*
             * Only pending orders can be cancelled by the customer.
             */
            if ($order->status !== 'pending') {
                throw new RuntimeException(
                    'این سفارش در وضعیت فعلی قابل لغو نیست.'
                );
            }

            /*
             * Release all active reservations.
             *
             * Physical inventory does not change because reservation
             * never reduces physical inventory.
             */
            $reservations = $order->inventoryReservations()
                ->where('status', 'active')
                ->get();

            foreach ($reservations as $reservation) {
                if (! $this->reservationService->release($reservation)) {
                    throw new RuntimeException(
                        'آزادسازی رزرو موجودی سفارش انجام نشد.'
                    );
                }
            }

            /*
             * Mark the order as cancelled.
             */
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Change order status through the allowed lifecycle.
     *
     * Allowed transitions:
     *
     * pending    -> cancelled
     * pending    -> paid
     * paid       -> processing
     * processing -> shipped
     * shipped    -> delivered
     *
     * The paid transition normally happens through PaymentService,
     * but it is included here so the lifecycle has a single definition.
     */
    public function setStatus(
        Order $order,
        string $newStatus
    ): bool {
        return DB::transaction(function () use (
            $order,
            $newStatus
        ) {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException(
                    'سفارش پیدا نشد.'
                );
            }

            $currentStatus = $order->status;

            if ($currentStatus === $newStatus) {
                throw new RuntimeException(
                    'سفارش از قبل در همین وضعیت قرار دارد.'
                );
            }

            $allowedTransitions = [
                'pending' => [
                    'paid',
                    'cancelled',
                ],
                'paid' => [
                    'processing',
                ],
                'processing' => [
                    'shipped',
                ],
                'shipped' => [
                    'delivered',
                ],
            ];

            $allowedStatuses =
                $allowedTransitions[$currentStatus] ?? [];

            if (! in_array(
                $newStatus,
                $allowedStatuses,
                true
            )) {
                throw new RuntimeException(
                    "تغییر وضعیت سفارش از «{$currentStatus}» به «{$newStatus}» مجاز نیست."
                );
            }

            /*
             * A payment status must exist before an order
             * enters the processing stage.
             */
            if (
                $newStatus === 'processing'
                && $order->payment_status !== 'paid'
            ) {
                throw new RuntimeException(
                    'فقط سفارش پرداخت‌شده می‌تواند وارد مرحله پردازش شود.'
                );
            }

            /*
             * The paid state represents a successfully paid order.
             * PaymentService remains the primary owner of payment
             * finalization, so keep payment_status synchronized here.
             */
            if ($newStatus === 'paid') {
                if ($order->payment_status !== 'paid') {
                    throw new RuntimeException(
                        'سفارش بدون پرداخت موفق نمی‌تواند به وضعیت paid برسد.'
                    );
                }

                $order->update([
                    'status' => 'paid',
                    'paid_at' => $order->paid_at ?? now(),
                    'confirmed_at' => $order->confirmed_at ?? now(),
                ]);

                return true;
            }

            $updates = [
                'status' => $newStatus,
            ];

            /*
             * Processing begins after successful payment.
             */
            if ($newStatus === 'processing') {
                $updates['confirmed_at'] =
                    $order->confirmed_at ?? now();
            }

            /*
             * Record the exact shipping timestamp.
             */
            if ($newStatus === 'shipped') {
                $updates['shipped_at'] = now();
            }

            /*
             * Record the exact delivery timestamp.
             */
            if ($newStatus === 'delivered') {
                $updates['delivered_at'] = now();
            }

            /*
             * Cancellation is handled by cancel().
             * This guard prevents bypassing its reservation logic.
             */
            if ($newStatus === 'cancelled') {
                throw new RuntimeException(
                    'برای لغو سفارش از عملیات لغو سفارش استفاده کنید.'
                );
            }

            $order->update($updates);

            return true;
        });
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        do {
            $number =
                'ORD-' .
                now()->format('YmdHis') .
                '-' .
                strtoupper(Str::random(6));
        } while (
            Order::where('order_number', $number)->exists()
        );

        return $number;
    }
}