<script setup lang="ts">
import { router } from '@inertiajs/vue3';
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
                    <a href="/account/addresses">مدیریت آدرس‌ها</a>
                </div>

                <div v-if="addresses.length === 0" class="no-addresses">
                    <strong>هنوز آدرسی ثبت نکرده‌اید.</strong>
                    <p>برای ادامه سفارش، ابتدا یک آدرس ثبت کنید.</p>
                    <a class="btn btn-secondary" href="/account/addresses"
                        >ثبت آدرس جدید</a
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
                    :class="{ 'is-unavailable': Number(item.unit_price) === 0 }"
                >
                    <div class="item-info">
                        <h3>{{ item.product.name }}</h3>
                        <span v-if="item.product.sku"
                            >کد کالا: {{ item.product.sku }}</span
                        >
                        <span>تعداد: {{ item.quantity }}</span>
                    </div>
                    <div class="item-price">
                        <template v-if="Number(item.unit_price) > 0">{{
                            formatPrice(item.unit_price)
                        }}</template>
                        <template v-else>
                            <span class="unavailable-label">ناموجود</span>
                            <span class="zero-price">۰ تومان</span>
                        </template>
                    </div>
                </div>
            </section>

            <section class="summary-section">
                <div class="summary-row">
                    <span>مبلغ قابل پرداخت</span>
                    <strong>{{ formatPrice(totalAmount ?? subtotal) }}</strong>
                </div>
                <div
                    v-if="Number(discountAmount || 0) > 0"
                    class="summary-row discount-row"
                >
                    <span>تخفیف</span>
                    <strong>- {{ formatPrice(discountAmount || 0) }}</strong>
                </div>
            </section>

            <section v-if="isZeroTotal" class="zero-total-section">
                <div class="zero-total-icon">!</div>
                <h2>هیچ کالای قابل خریدی در سبد شما باقی نمانده است.</h2>
                <p>محصولات ناموجود پس از تأیید از سبد حذف خواهند شد.</p>
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

            <section
                v-else-if="addresses.length === 0"
                class="confirmation-section"
            >
                <h2>آدرس تحویل لازم است</h2>
                <p>برای ثبت سفارش، ابتدا یک آدرس برای تحویل سفارش ثبت کنید.</p>
            </section>

            <section
                v-else-if="requiresPriceConfirmation"
                class="confirmation-section"
            >
                <h2>تأیید تغییرات</h2>
                <p>
                    برای ادامه، باید تغییرات سبد خرید و آدرس تحویل را تأیید
                    کنید.
                </p>
                <div class="actions">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="!canProceedToPayment || !hasAddress"
                        @click="submitCheckout"
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

            <section v-else class="confirmation-section">
                <h2>سفارش آماده است</h2>
                <p>
                    آدرس و قیمت سفارش را بررسی کرده‌اید. برای ایجاد سفارش ادامه
                    دهید.
                </p>
                <div class="actions">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="!canProceedToPayment || !hasAddress"
                        @click="submitCheckout"
                    >
                        ثبت سفارش و ادامه به پرداخت
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
.checkout-header p,
.section-heading p,
.confirmation-section p,
.zero-total-section p {
    margin: 0;
    color: #666;
}
.feedback-message {
    margin-bottom: 25px;
    padding: 14px 18px;
    border: 1px solid #b7d7c1;
    border-radius: 12px;
    background: #effaf2;
    color: #25643a;
}
.address-section,
.coupon-section,
.cart-section,
.summary-section,
.confirmation-section,
.zero-total-section {
    margin-bottom: 25px;
    padding: 24px;
    border-radius: 16px;
    background: #fff;
}
.section-heading {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 20px;
}
.section-heading h2,
.cart-section h2,
.confirmation-section h2,
.zero-total-section h2 {
    margin-top: 0;
}
.section-heading a {
    white-space: nowrap;
}
.address-list {
    display: grid;
    gap: 12px;
}
.address-card {
    display: flex;
    gap: 14px;
    padding: 18px;
    border: 1px solid #ddd;
    border-radius: 12px;
    cursor: pointer;
}
.address-card.is-selected {
    border-color: #111;
    box-shadow: 0 0 0 1px #111;
}
.address-card input {
    margin-top: 4px;
}
.address-content {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.address-title-row {
    display: flex;
    gap: 10px;
    align-items: center;
}
.default-badge {
    padding: 3px 8px;
    border-radius: 999px;
    background: #eee;
    font-size: 12px;
}
.address-text {
    color: #444;
    line-height: 1.8;
}
.postal-code {
    color: #777;
    font-size: 14px;
}
.no-addresses {
    padding: 20px;
    border: 1px dashed #ccc;
    border-radius: 12px;
}
.no-addresses p {
    color: #666;
}
.coupon-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
.coupon-input {
    width: min(100%, 360px);
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 12px 14px;
    outline: none;
}
.coupon-input:focus {
    border-color: #111;
}
.coupon-applied {
    color: #25643a;
    font-size: 14px;
    font-weight: 600;
}
.coupon-note {
    margin: 10px 0 0;
    color: #777;
    font-size: 13px;
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
.new-price,
.stock-status {
    font-weight: 700;
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
.discount-row {
    margin-top: 10px;
    color: #25643a;
    font-size: 15px;
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
    display: inline-block;
    border: 0;
    border-radius: 10px;
    padding: 12px 20px;
    cursor: pointer;
    font-size: 15px;
    text-decoration: none;
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
    .summary-row,
    .section-heading {
        align-items: flex-start;
        flex-direction: column;
    }
    .price-change,
    .stock-status {
        align-items: flex-start;
    }
    .btn {
        width: 100%;
        text-align: center;
    }
    .coupon-input {
        width: 100%;
    }
}
</style>
