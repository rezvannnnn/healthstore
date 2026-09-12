<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface Product {
    id: number
    name: string
    sku?: string
}

interface CartItem {
    id: number
    product_id: number
    quantity: number
    unit_price: number | string
    product: Product
}

interface Cart {
    id: number
    items: CartItem[]
}

interface Change {
    type: 'price_changed' | 'out_of_stock' | 'unavailable'
    item_id?: number
    cart_item_id?: number
    product_id: number
    product_name: string
    old_price: number
    new_price: number | null
}

const props = defineProps<{
    cart: Cart
    changes: Change[]
    priceChanges: Change[]
    availabilityChanges: Change[]
    requiresPriceConfirmation: boolean
    subtotal: number
    canProceedToPayment: boolean
    cartHasPayableItems: boolean
    message?: string
}>()

const priceFormatter = new Intl.NumberFormat('fa-IR')

const formatPrice = (value: number | string): string => {
    return `${priceFormatter.format(Number(value))} تومان`
}

const hasPriceChanges = computed(() => {
    return props.priceChanges.length > 0
})

const hasOutOfStockItems = computed(() => {
    return props.availabilityChanges.length > 0
})

const hasChanges = computed(() => {
    return props.changes.length > 0
})

const isZeroTotal = computed(() => {
    return Number(props.subtotal) <= 0
})

const confirmChanges = () => {
    router.post(
        '/checkout/confirm',
        {},
        {
            preserveScroll: true,
        }
    )
}

const rejectChanges = () => {
    router.post(
        '/checkout/reject',
        {},
        {
            preserveScroll: true,
        }
    )
}

const goBackToCart = () => {
    router.get('/cart')
}
</script>

<template>
    <div class="checkout-page" dir="rtl">
        <div class="checkout-container">

            <header class="checkout-header">
                <div>
                    <h1>تکمیل سفارش</h1>
                    <p>
                        لطفاً اطلاعات و قیمت نهایی سبد خرید خود را بررسی کنید.
                    </p>
                </div>
            </header>

            <!-- Changes notice -->
            <div
                v-if="hasChanges"
                class="changes-notice"
            >
                <h2>تغییرات سبد خرید</h2>

                <p>
                    از آخرین مراجعه شما، وضعیت یکی یا چند محصول تغییر کرده است.
                    لطفاً موارد زیر را بررسی کنید.
                </p>

                <!-- Price changes -->
                <div
                    v-if="hasPriceChanges"
                    class="change-section"
                >
                    <h3>تغییر قیمت</h3>

                    <div
                        v-for="change in priceChanges"
                        :key="`price-${change.product_id}`"
                        class="change-row"
                    >
                        <div>
                            <strong>{{ change.product_name }}</strong>
                        </div>

                        <div class="price-change">
                            <span class="old-price">
                                {{ formatPrice(change.old_price) }}
                            </span>

                            <span class="arrow">←</span>

                            <span class="new-price">
                                {{ formatPrice(change.new_price ?? 0) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Out of stock -->
                <div
                    v-if="hasOutOfStockItems"
                    class="change-section"
                >
                    <h3>محصولات ناموجود</h3>

                    <div
                        v-for="change in availabilityChanges"
                        :key="`stock-${change.product_id}`"
                        class="change-row out-of-stock-row"
                    >
                        <div>
                            <strong>{{ change.product_name }}</strong>
                        </div>

                        <div class="stock-status">
                            <span class="strikethrough-price">
                                {{ formatPrice(change.old_price) }}
                            </span>

                            <span>ناموجود</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart items -->
            <section class="cart-section">
                <h2>اقلام سبد خرید</h2>

                <div
                    v-if="cart.items.length === 0"
                    class="empty-cart"
                >
                    سبد خرید شما خالی است.
                </div>

                <div
                    v-for="item in cart.items"
                    :key="item.id"
                    class="cart-item"
                    :class="{
                        'is-unavailable': Number(item.unit_price) === 0
                    }"
                >
                    <div class="item-info">
                        <h3>{{ item.product.name }}</h3>

                        <span v-if="item.product.sku">
                            کد کالا: {{ item.product.sku }}
                        </span>

                        <span>
                            تعداد: {{ item.quantity }}
                        </span>
                    </div>

                    <div class="item-price">
                        <template v-if="Number(item.unit_price) > 0">
                            {{ formatPrice(item.unit_price) }}
                        </template>

                        <template v-else>
                            <span class="unavailable-label">
                                ناموجود
                            </span>

                            <span class="zero-price">
                                ۰ تومان
                            </span>
                        </template>
                    </div>
                </div>
            </section>

            <!-- Summary -->
            <section class="summary-section">
                <div class="summary-row">
                    <span>مبلغ قابل پرداخت</span>

                    <strong>
                        {{ formatPrice(subtotal) }}
                    </strong>
                </div>
            </section>

            <!-- ZERO TOTAL -->
            <section
                v-if="isZeroTotal"
                class="zero-total-section"
            >
                <div class="zero-total-icon">
                    !
                </div>

                <h2>
                    هیچ کالای قابل خریدی در سبد شما باقی نمانده است.
                </h2>

                <p>
                    محصولات ناموجود پس از تأیید از سبد حذف خواهند شد.
                </p>

                <div class="actions">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="goBackToCart"
                    >
                        بازگشت به سبد خرید
                    </button>
                </div>
            </section>

            <!-- Changes require confirmation -->
            <section
                v-else-if="requiresPriceConfirmation"
                class="confirmation-section"
            >
                <h2>تأیید تغییرات</h2>

                <p>
                    برای ادامه، باید تغییرات سبد خرید را تأیید کنید.
                    محصولات ناموجود پس از تأیید از سبد شما حذف خواهند شد.
                </p>

                <div class="actions">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="!canProceedToPayment"
                        @click="confirmChanges"
                    >
                        تأیید تغییرات و ادامه پرداخت
                    </button>

                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="rejectChanges"
                    >
                        عدم تأیید و بازگشت به سبد
                    </button>
                </div>
            </section>

            <!-- No changes -->
            <section
                v-else
                class="confirmation-section"
            >
                <h2>سبد خرید آماده است</h2>

                <p>
                    قیمت‌ها و وضعیت محصولات تغییر نکرده‌اند.
                </p>

                <div class="actions">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="!canProceedToPayment"
                        @click="confirmChanges"
                    >
                        ادامه به پرداخت
                    </button>
                </div>
            </section>

        </div>
    </div>
</template>

<style scoped>
.checkout-page {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 40px 20px;
}

.checkout-container {
    width: min(100%, 1000px);
    margin: 0 auto;
}

.checkout-header {
    margin-bottom: 30px;
}

.checkout-header h1 {
    margin: 0 0 8px;
    font-size: 30px;
}

.checkout-header p {
    margin: 0;
    color: #666;
}

.changes-notice {
    margin-bottom: 25px;
    padding: 24px;
    border: 1px solid #f0c36d;
    border-radius: 16px;
    background: #fff9e8;
}

.changes-notice h2 {
    margin-top: 0;
    margin-bottom: 10px;
}

.change-section {
    margin-top: 20px;
}

.change-section h3 {
    margin-bottom: 12px;
}

.change-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 14px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.change-row:last-child {
    border-bottom: 0;
}

.price-change,
.stock-status {
    display: flex;
    align-items: center;
    gap: 10px;
}

.old-price,
.strikethrough-price {
    color: #999;
    text-decoration: line-through;
}

.new-price {
    font-weight: 700;
}

.stock-status {
    font-weight: 700;
}

.cart-section,
.summary-section,
.confirmation-section,
.zero-total-section {
    margin-bottom: 25px;
    padding: 24px;
    border-radius: 16px;
    background: #fff;
}

.cart-section h2,
.confirmation-section h2,
.zero-total-section h2 {
    margin-top: 0;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 18px 0;
    border-bottom: 1px solid #eee;
}

.cart-item:last-child {
    border-bottom: 0;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.item-info h3 {
    margin: 0;
}

.item-info span {
    color: #777;
    font-size: 14px;
}

.item-price {
    font-weight: 700;
    white-space: nowrap;
}

.is-unavailable {
    opacity: 0.7;
}

.unavailable-label {
    display: block;
    color: #c62828;
}

.zero-price {
    display: block;
    margin-top: 5px;
    color: #777;
    font-size: 14px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 18px;
}

.confirmation-section p,
.zero-total-section p {
    color: #666;
}

.zero-total-section {
    border: 1px solid #e0e0e0;
    text-align: center;
}

.zero-total-icon {
    width: 42px;
    height: 42px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background: #eee;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}

.btn {
    border: 0;
    border-radius: 10px;
    padding: 12px 20px;
    cursor: pointer;
    font-size: 15px;
}

.btn-primary {
    background: #111;
    color: #fff;
}

.btn-secondary {
    background: #e9ecef;
    color: #222;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.empty-cart {
    padding: 30px 0;
    color: #777;
    text-align: center;
}

@media (max-width: 700px) {
    .change-row,
    .cart-item,
    .summary-row {
        align-items: flex-start;
        flex-direction: column;
    }

    .price-change,
    .stock-status {
        align-items: flex-start;
    }

    .btn {
        width: 100%;
    }
}
</style>