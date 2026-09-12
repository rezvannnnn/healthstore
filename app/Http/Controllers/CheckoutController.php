<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected CartService $cartService,
        protected OrderService $orderService
    ) {
    }

    /**
     * Show checkout page.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $cart = $this->cartService->getCartForUser($user->id);

        if ($cart->items()->count() === 0) {
            return Inertia::render('Checkout', [
                'cart' => $cart->load('items.product'),
                'changes' => [],
                'priceChanges' => [],
                'availabilityChanges' => [],
                'requiresPriceConfirmation' => false,
                'subtotal' => 0,
                'canProceedToPayment' => false,
                'cartHasPayableItems' => false,
                'message' => 'سبد خرید شما خالی است.',
            ]);
        }

        $result = $this->checkoutService->prepare($cart);

        return Inertia::render('Checkout', [
            'cart' => $result['cart'],
            'changes' => $result['changes'],
            'priceChanges' => $result['price_changes'],
            'availabilityChanges' => $result['availability_changes'],
            'requiresPriceConfirmation' => $result['requires_price_confirmation'],
            'subtotal' => $result['subtotal'],
            'canProceedToPayment' => $result['can_proceed_to_payment'],
            'cartHasPayableItems' => $result['cart_has_payable_items'],
        ]);
    }

    /**
     * Confirm checkout changes and create the order.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $cart = $this->cartService->getCartForUser($user->id);

        try {
            /*
             * First confirm the latest price and availability changes.
             */
            $result = $this->checkoutService->confirmPriceChanges($cart);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('checkout.show')
                ->with('error', $e->getMessage());
        }

        /*
         * If nothing payable remains, do not create an order.
         */
        if (! $result['payment_allowed']) {
            return redirect()
                ->route('checkout.show')
                ->with(
                    'info',
                    $result['message']
                        ?? 'هیچ کالای قابل پرداختی در سبد باقی نمانده است.'
                );
        }

        /*
         * Reload the cart after the checkout confirmation because
         * unavailable items may have been removed.
         */
        $cart = $this->cartService->getCartForUser($user->id);

        try {
            /*
             * Convert the confirmed cart into a real order and
             * create inventory reservations.
             */
            $order = $this->orderService->createFromCart($cart);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('checkout.show')
                ->with('error', $e->getMessage());
        }

        /*
         * The order has been created successfully.
         * Payment will be created from the order page.
         */
        return redirect()
            ->route('orders.show', [
                'orderNumber' => $order->order_number,
            ])
            ->with(
                'success',
                'سفارش شما با موفقیت ایجاد شد.'
            );
    }

    /**
     * Reject checkout changes.
     */
    public function reject(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $cart = $this->cartService->getCartForUser($user->id);

        $this->checkoutService->rejectPriceChanges($cart);

        return redirect()
            ->route('checkout.show')
            ->with(
                'info',
                'تغییرات سبد خرید تأیید نشد. سبد شما با وضعیت جدید حفظ شد.'
            );
    }
}