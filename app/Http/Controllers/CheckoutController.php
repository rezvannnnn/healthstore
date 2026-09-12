<?php

namespace App\Http\Controllers;

use App\Models\Address;
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
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $cart = $this->cartService->getCartForUser($user->id);
        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        if ($cart->items()->count() === 0) {
            return Inertia::render('Checkout', [
                'cart' => $cart->load('items.product'),
                'addresses' => $addresses,
                'selectedAddressId' => null,
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
        $selectedAddressId = old(
            'address_id',
            $addresses->firstWhere('is_default', true)?->id
                ?? $addresses->first()?->id
        );

        return Inertia::render('Checkout', [
            'cart' => $result['cart'],
            'addresses' => $addresses,
            'selectedAddressId' => $selectedAddressId,
            'changes' => $result['changes'],
            'priceChanges' => $result['price_changes'],
            'availabilityChanges' => $result['availability_changes'],
            'requiresPriceConfirmation' => $result['requires_price_confirmation'],
            'subtotal' => $result['subtotal'],
            'canProceedToPayment' => $result['can_proceed_to_payment'],
            'cartHasPayableItems' => $result['cart_has_payable_items'],
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
        ]);

        $address = Address::query()
            ->where('user_id', $user->id)
            ->find($validated['address_id']);

        if (! $address) {
            return redirect()
                ->route('checkout.show')
                ->withErrors([
                    'address_id' => 'لطفاً یکی از آدرس‌های خود را انتخاب کنید.',
                ]);
        }

        $cart = $this->cartService->getCartForUser($user->id);

        try {
            $result = $this->checkoutService->confirmPriceChanges($cart);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('checkout.show')
                ->with('error', $e->getMessage());
        }

        if (! $result['payment_allowed']) {
            return redirect()
                ->route('checkout.show')
                ->with(
                    'info',
                    $result['message']
                        ?? 'هیچ کالای قابل پرداختی در سبد باقی نمانده است.'
                );
        }

        $cart = $this->cartService->getCartForUser($user->id);

        try {
            $order = $this->orderService->createFromCart($cart, $address);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('checkout.show')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('orders.show', [
                'orderNumber' => $order->order_number,
            ])
            ->with('success', 'سفارش شما با موفقیت ایجاد شد.');
    }

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
