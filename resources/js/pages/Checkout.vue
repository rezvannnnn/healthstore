<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Product {
    id: number;
    name: string;
    sku?: string;
}

interface CartItem {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: number | string;
    product: Product;
}

interface Cart {
    id: number;
    items: CartItem[];
}

interface Address {
    id: number;
    title?: string | null;
    recipient_name: string;
    phone: string;
    province?: string | null;
    city?: string | null;
    address: string;
    postal_code?: string | null;
    is_default: boolean;
}

interface Change {
    type: 'price_changed' | 'out_of_stock' | 'unavailable';
    item_id?: number;
    cart_item_id?: number;
    product_id: number;
    product_name: string;
    old_price: number;
    new_price: number | null;
}

const props = defineProps<{
    cart: Cart;
    addresses: Address[];
    selectedAddressId?: number | null;
    couponCode?: string | null;
    appliedCoupon?: unknown;
    discountAmount?: number;
    changes: Change[];
    priceChanges: Change[];
    availabilityChanges: Change[];
    requiresPriceConfirmation: boolean;
    subtotal: number;
    totalAmount?: number;
    canProceedToPayment: boolean;
    cartHasPayableItems: boolean;
    message?: string;
}>();

const selectedAddressId = ref<number | null>(
    props.selectedAddressId ?? props.addresses[0]?.id ?? null,
);
const couponCode = ref(props.couponCode ?? '');

const priceFormatter = new Intl.NumberFormat('fa-IR');

const formatPrice = (value: number | string): string => {
    return `${priceFormatter.format(Number(value))} تومان`;
};

const hasPriceChanges = computed(() => props.priceChanges.length > 0);
const hasOutOfStockItems = computed(() => props.availabilityChanges.length > 0);
const hasChanges = computed(() => props.changes.length > 0);
const isZeroTotal = computed(() => Number(props.subtotal) <= 0);
const hasAddress = computed(() => selectedAddressId.value !== null);

const submitCheckout = () => {
    if (!selectedAddressId.value) {
        return;
    }

    router.post(
        '/checkout/confirm',
        {
            address_id: selectedAddressId.value,
            coupon_code: couponCode.value.trim() || null,
        },
        {
            preserveScroll: true,
        },
    );
};

const rejectChanges = () => {
    router.post('/checkout/reject', {}, { preserveScroll: true });
};

const goBackToCart = () => {
    router.get('/cart');
};
</script>

<template>
    <div class="checkout-page" dir="rtl">
        <div class="checkout-container">
            <header class="checkout-header">
                <div>
                    <h1>تکمیل سفارش</h1>
                    <p>لطفاً اطلاعات، آدرس و قیمت نهایی سفارش را بررسی کنید.</p>
                </div>
            </header>

            <div v-if="message" class="feedback-message">
                {{ message }}
            </div>

            <section class="address-section">
                <div class="section-heading">
                    <div>
                        <h2>آدرس تحویل</h2>
                        <p>
                            آدرسی را که می‌خواهید سفارش به آن ارسال شود انتخاب
                            کنید.
                        </p>
                    </div>
                    <Link href="/account/addresses">مدیریت آدرس‌ها</Link>
                </div>

                <div v-if="addresses.length === 0" class="no-addresses">
                    <strong>هنوز آدرسی ثبت نکرده‌اید.</strong>
                    <p>برای ادامه سفارش، ابتدا یک آدرس ثبت کنید.</p>
                    <Link class="btn btn-secondary" href="/account/addresses"
                        >ثبت آدرس جدید</Link
                    >
                </div>

                <div v-else class="address-list">
                    <label
                        v-for="address in addresses"
                        :key="address.id"
                        class="address-card"
                        :class="{
                            'is-selected': selectedAddressId === address.id,
                        }"
                    >
                        <input
                            v-model="selectedAddressId"
                            type="radio"
                            name="checkout-address"
                            :value="address.id"
                        />

                        <div class="address-content">
                            <div class="address-title-row">
                                <strong>{{ address.title || 'آدرس' }}</strong>
                                <span
                                    v-if="address.is_default"
                                    class="default-badge"
                                    >پیش‌فرض</span
                                >
                            </div>
                            <div>
                                {{ address.recipient_name }} -
                                {{ address.phone }}
                            </div>
                            <div class="address-text">
                                {{ address.province
                                }}{{
                                    address.province && address.city
                                        ? '، '
                                        : ''
                                }}{{ address.city }}
                                <span v-if="address.province || address.city"
                                    >، </span
                                >{{ address.address }}
                            </div>
                            <div v-if="address.postal_code" class="postal-code">
                                کد پستی: {{ address.postal_code }}
                            </div>
                        </div>
                    </label>
                </div>
            </section>

            <section class="coupon-section" v-if="cartHasPayableItems">
                <div class="section-heading">
                    <div>
                        <h2>کد تخفیف</h2>
                        <p>
                            در صورت داشتن کد تخفیف، آن را پیش از ثبت سفارش وارد
                            کنید.
                        </p>
                    </div>
                </div>
                <div class="coupon-form">
                    <input
                        v-model="couponCode"
                        type="text"
                        maxlength="64"
                        autocomplete="off"
                        placeholder="کد تخفیف"
                        class="coupon-input"
                    />
                    <span v-if="props.appliedCoupon" class="coupon-applied">
                        تخفیف اعمال شده
                        <template v-if="Number(props.discountAmount || 0) > 0">
                            - {{ formatPrice(props.discountAmount || 0) }}
                        </template>
                    </span>
                </div>
                <p class="coupon-note">
                    اعتبار و میزان تخفیف هنگام ثبت سفارش در سمت سرور بررسی
                    می‌شود.
                </p>
            </section>

            <div v-if="hasChanges" class="changes-notice">
                <h2>تغییرات سبد خرید</h2>
                <p>
                    از آخرین مراجعه شما، وضعیت یکی یا چند محصول تغییر کرده است.
                    لطفاً موارد زیر را بررسی کنید.
                </p>

                <div v-if="hasPriceChanges" class="change-section">
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
                            <span class="old-price">{{
                                formatPrice(change.old_price)
                            }}</span>
                            <span class="arrow">←</span>
                            <span class="new-price">{{
                                formatPrice(change.new_price ?? 0)
                            }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="hasOutOfStockItems" class="change-section">
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
                            <span class="strikethrough-price">{{
                                formatPrice(change.old_price)
                            }}</span>
                            <span>ناموجود</span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="cart-section">
                <h2>اقلام سبد خرید</h2>

                <div v-if="cart.items.length === 0" class="empty-cart">
                    سبد خرید شما خالی است.
                </div>

                <div
                    v-for="item in cart.items"
                    :key="item.id"
                    class="cart-item"
                >
                    <div class="cart-item-main">
                        <strong>{{ item.product.name }}</strong>
                        <span v-if="item.product.sku">{{
                            item.product.sku
                        }}</span>
                    </div>
                    <div class="cart-item-meta">
                        <span>تعداد: {{ item.quantity }}</span>
                        <span>{{ formatPrice(item.unit_price) }}</span>
                    </div>
                </div>
            </section>

            <section class="summary-section">
                <div class="summary-row">
                    <span>جمع سبد خرید</span>
                    <strong>{{ formatPrice(props.subtotal) }}</strong>
                </div>
                <div
                    v-if="Number(props.discountAmount || 0) > 0"
                    class="summary-row discount-row"
                >
                    <span>تخفیف</span>
                    <strong
                        >- {{ formatPrice(props.discountAmount || 0) }}</strong
                    >
                </div>
                <div class="summary-row total-row">
                    <span>مبلغ نهایی</span>
                    <strong>{{
                        formatPrice(props.totalAmount ?? props.subtotal)
                    }}</strong>
                </div>
            </section>

            <section v-if="isZeroTotal" class="zero-total">
                <strong>مبلغ قابل پرداخت صفر است.</strong>
                <p>برای ادامه، سفارش با مبلغ نهایی صفر ثبت خواهد شد.</p>
            </section>

            <section v-if="!hasAddress" class="checkout-warning">
                <strong>برای ثبت سفارش، ابتدا یک آدرس انتخاب کنید.</strong>
            </section>

            <section v-if="requiresPriceConfirmation" class="checkout-warning">
                <strong>قیمت برخی اقلام تغییر کرده است.</strong>
                <p>برای ادامه، تغییرات بالا را بررسی و تأیید کنید.</p>
            </section>

            <div class="checkout-actions">
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="!canProceedToPayment || !hasAddress"
                    @click="submitCheckout"
                >
                    ثبت سفارش و ادامه پرداخت
                </button>
                <button
                    v-if="hasChanges"
                    type="button"
                    class="btn btn-secondary"
                    @click="rejectChanges"
                >
                    رد تغییرات
                </button>
                <button
                    type="button"
                    class="btn btn-link"
                    @click="goBackToCart"
                >
                    بازگشت به سبد خرید
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.checkout-page {
    min-height: 100vh;
    padding: 2rem 1rem;
}
.checkout-container {
    width: min(100%, 1100px);
    margin: 0 auto;
}
.checkout-header,
.section-heading,
.cart-item,
.summary-row,
.checkout-actions,
.coupon-form,
.change-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
section,
.checkout-header,
.feedback-message,
.changes-notice,
.checkout-warning,
.zero-total {
    margin-bottom: 1.5rem;
}
.address-list {
    display: grid;
    gap: 1rem;
}
.address-card {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    cursor: pointer;
}
.address-content {
    flex: 1;
}
.address-title-row,
.price-change,
.stock-status,
.cart-item-main,
.cart-item-meta {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}
.coupon-input {
    min-width: 240px;
}
.change-row,
.cart-item,
.summary-row {
    padding: 0.75rem 0;
}
.checkout-actions {
    flex-wrap: wrap;
}
</style>
