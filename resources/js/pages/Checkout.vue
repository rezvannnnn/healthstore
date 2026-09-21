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
const hasPayableItems = computed(() => props.cartHasPayableItems);
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
    <div dir="rtl" class="min-h-screen bg-dh-surface pb-10 text-dh-ink">
        <header class="sticky top-0 z-30 border-b border-dh-100/70 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6">
                <Link href="/" class="flex items-center gap-3" aria-label="داروخونه">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-dh-50 text-dh-700"><svg viewBox="0 0 48 48" class="size-8" fill="none"><path d="M10 19h28l-3 16H13l-3-16Z" stroke="currentColor" stroke-width="2.5"/><path d="M15 19c1-5 4-8 9-8s8 3 9 8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M25 13c-3 1-5 4-5 7 4 0 7-2 8-6" stroke="#63b95b" stroke-width="2.5" stroke-linecap="round"/></svg></span>
                    <span><span class="block text-lg font-black text-dh-800">داروخونه</span><span class="hidden text-[10px] text-dh-muted sm:block">دارو و محصولات بهداشتی</span></span>
                </Link>
                <Link href="/cart" class="rounded-xl border border-dh-100 px-4 py-2.5 text-sm font-bold text-dh-700 hover:bg-dh-50">بازگشت به سبد</Link>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-6 px-4 py-6 md:px-6 md:py-10">
            <header>
                <p class="text-xs font-bold text-dh-600">مرحله نهایی</p>
                <h1 class="mt-1 text-3xl font-black text-dh-800 md:text-4xl">تکمیل سفارش</h1>
                <p class="mt-2 text-sm leading-7 text-dh-muted">آدرس، اقلام و مبلغ نهایی را بررسی کنید؛ سپس برای پرداخت ادامه دهید.</p>
            </header>

            <div v-if="message" class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-700">{{ message }}</div>

            <section class="grid gap-6 lg:grid-cols-[1fr_350px]">
                <div class="space-y-5">
                    <section class="rounded-3xl border border-dh-100 bg-white p-5 shadow-sm md:p-6">
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div><span class="flex size-9 items-center justify-center rounded-xl bg-dh-50 text-sm font-black text-dh-700">۱</span><h2 class="mt-3 text-xl font-black text-dh-800">آدرس تحویل</h2><p class="mt-1 text-sm leading-6 text-dh-muted">آدرسی را که می‌خواهید سفارش به آن ارسال شود انتخاب کنید.</p></div>
                            <Link href="/account/addresses" class="shrink-0 text-xs font-bold text-dh-700">مدیریت آدرس‌ها</Link>
                        </div>
                        <div v-if="addresses.length === 0" class="rounded-2xl border border-dashed border-dh-200 bg-dh-surface p-6">
                            <strong class="text-dh-800">هنوز آدرسی ثبت نکرده‌اید.</strong><p class="mt-2 text-sm text-dh-muted">برای ادامه سفارش، ابتدا یک آدرس ثبت کنید.</p>
                            <Link class="mt-4 inline-flex rounded-xl bg-dh-700 px-4 py-2.5 text-sm font-bold text-white" href="/account/addresses">ثبت آدرس جدید</Link>
                        </div>
                        <div v-else class="grid gap-3">
                            <label v-for="address in addresses" :key="address.id" class="flex cursor-pointer gap-3 rounded-2xl border p-4 transition" :class="selectedAddressId === address.id ? 'border-dh-600 bg-dh-50/50 ring-2 ring-dh-100' : 'border-dh-100 bg-white hover:border-dh-200'">
                                <input v-model="selectedAddressId" type="radio" name="checkout-address" :value="address.id" class="mt-1 accent-dh-700" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2"><strong class="text-sm text-dh-800">{{ address.title || 'آدرس' }}</strong><span v-if="address.is_default" class="rounded-full bg-dh-green-50 px-2 py-0.5 text-[10px] font-bold text-dh-green-700">پیش‌فرض</span></span>
                                    <span class="mt-2 block text-sm text-dh-800">{{ address.recipient_name }} · {{ address.phone }}</span>
                                    <span class="mt-1 block text-sm leading-7 text-dh-muted">{{ address.province }}{{ address.province && address.city ? '، ' : '' }}{{ address.city }}{{ address.province || address.city ? '، ' : '' }}{{ address.address }}</span>
                                    <span v-if="address.postal_code" class="mt-1 block text-xs text-dh-muted">کد پستی: {{ address.postal_code }}</span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <section v-if="hasPayableItems" class="rounded-3xl border border-dh-100 bg-white p-5 shadow-sm md:p-6">
                        <div class="flex items-start gap-3"><span class="flex size-9 items-center justify-center rounded-xl bg-dh-50 text-sm font-black text-dh-700">۲</span><div><h2 class="text-xl font-black text-dh-800">کد تخفیف</h2><p class="mt-1 text-sm leading-6 text-dh-muted">در صورت داشتن کد، آن را وارد کنید.</p></div></div>
                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            <input v-model="couponCode" type="text" maxlength="64" autocomplete="off" placeholder="کد تخفیف" class="w-full rounded-xl border border-dh-100 bg-dh-surface px-4 py-3 text-sm outline-none focus:border-dh-500 sm:max-w-sm" />
                            <span v-if="props.appliedCoupon" class="rounded-xl bg-dh-green-50 px-3 py-2 text-xs font-bold text-dh-green-700">تخفیف اعمال شده <template v-if="Number(props.discountAmount || 0) > 0">· {{ formatPrice(props.discountAmount || 0) }}</template></span>
                        </div>
                        <p class="mt-3 text-xs leading-6 text-dh-muted">اعتبار و میزان تخفیف هنگام ثبت سفارش در سمت سرور بررسی می‌شود.</p>
                    </section>

                    <section v-if="hasChanges" class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm md:p-6">
                        <h2 class="text-xl font-black text-amber-900">تغییرات سبد خرید</h2>
                        <p class="mt-1 text-sm leading-7 text-amber-800">از آخرین مراجعه شما، وضعیت یکی یا چند محصول تغییر کرده است. موارد زیر را بررسی کنید.</p>
                        <div v-if="hasPriceChanges" class="mt-5 space-y-2"><h3 class="text-sm font-black text-amber-900">تغییر قیمت</h3><div v-for="change in priceChanges" :key="`price-${change.product_id}`" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white/70 p-3 text-sm"><strong>{{ change.product_name }}</strong><span><span class="text-gray-400 line-through">{{ formatPrice(change.old_price) }}</span><span class="mx-2">←</span><strong>{{ formatPrice(change.new_price ?? 0) }}</strong></span></div></div>
                        <div v-if="hasOutOfStockItems" class="mt-5 space-y-2"><h3 class="text-sm font-black text-amber-900">محصولات ناموجود</h3><div v-for="change in availabilityChanges" :key="`stock-${change.product_id}`" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white/70 p-3 text-sm"><strong>{{ change.product_name }}</strong><span class="font-bold text-red-600">ناموجود · <span class="font-normal text-gray-400 line-through">{{ formatPrice(change.old_price) }}</span></span></div></div>
                    </section>

                    <section class="rounded-3xl border border-dh-100 bg-white p-5 shadow-sm md:p-6">
                        <div class="flex items-start gap-3"><span class="flex size-9 items-center justify-center rounded-xl bg-dh-50 text-sm font-black text-dh-700">۳</span><div><h2 class="text-xl font-black text-dh-800">اقلام سبد خرید</h2><p class="mt-1 text-sm text-dh-muted">{{ cart.items.length.toLocaleString('fa-IR') }} قلم در سفارش</p></div></div>
                        <div v-if="cart.items.length === 0" class="mt-5 rounded-2xl bg-dh-surface p-5 text-center text-sm text-dh-muted">سبد خرید شما خالی است.</div>
                        <div v-for="item in cart.items" :key="item.id" class="flex items-center justify-between gap-4 border-b border-dh-100 py-4 last:border-0 last:pb-0">
                            <div class="min-w-0"><h3 class="line-clamp-2 text-sm font-black leading-6 text-dh-800">{{ item.product.name }}</h3><div class="mt-1 flex flex-wrap gap-3 text-xs text-dh-muted"><span v-if="item.product.sku">کد کالا: {{ item.product.sku }}</span><span>تعداد: {{ item.quantity.toLocaleString('fa-IR') }}</span></div></div>
                            <div class="shrink-0 text-left text-sm font-black text-dh-800"><template v-if="Number(item.unit_price) > 0">{{ formatPrice(item.unit_price) }}</template><template v-else><span class="block text-red-600">ناموجود</span><span class="mt-1 block text-xs font-normal text-dh-muted">۰ تومان</span></template></div>
                        </div>
                    </section>
                </div>

                <aside class="h-fit rounded-3xl border border-dh-100 bg-white p-6 shadow-sm lg:sticky lg:top-24">
                    <h2 class="text-lg font-black text-dh-800">خلاصه سفارش</h2>
                    <div class="mt-5 flex items-center justify-between text-sm text-dh-muted"><span>جمع محصولات</span><span>{{ formatPrice(subtotal) }}</span></div>
                    <div v-if="Number(discountAmount || 0) > 0" class="mt-3 flex items-center justify-between text-sm text-dh-green-700"><span>تخفیف</span><strong>- {{ formatPrice(discountAmount || 0) }}</strong></div>
                    <div class="my-5 border-t border-dh-100"></div>
                    <div class="flex items-end justify-between gap-3"><span class="text-sm font-bold text-dh-800">مبلغ قابل پرداخت</span><strong class="text-xl font-black text-dh-700">{{ formatPrice(totalAmount ?? subtotal) }}</strong></div>

                    <section v-if="!hasPayableItems" class="mt-6 rounded-2xl bg-amber-50 p-4"><div class="font-black text-amber-900">کالای قابل خریدی باقی نمانده است.</div><p class="mt-1 text-xs leading-6 text-amber-800">محصولات ناموجود پس از تأیید از سبد حذف خواهند شد.</p><button type="button" class="mt-4 w-full rounded-xl bg-white px-4 py-3 text-sm font-bold text-dh-700 ring-1 ring-dh-100" @click="goBackToCart">بازگشت به سبد خرید</button></section>

                    <section v-else-if="addresses.length === 0" class="mt-6 rounded-2xl bg-dh-surface p-4"><div class="font-black text-dh-800">آدرس تحویل لازم است</div><p class="mt-1 text-xs leading-6 text-dh-muted">برای ثبت سفارش، ابتدا یک آدرس ثبت کنید.</p></section>

                    <section v-else-if="requiresPriceConfirmation" class="mt-6">
                        <div class="rounded-2xl bg-dh-50 p-4"><div class="font-black text-dh-800">تأیید تغییرات</div><p class="mt-1 text-xs leading-6 text-dh-muted">برای ادامه، تغییرات سبد و آدرس تحویل را تأیید کنید.</p></div>
                        <button type="button" class="mt-3 w-full rounded-2xl bg-dh-700 px-5 py-3.5 text-sm font-black text-white shadow-sm hover:bg-dh-800 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!canProceedToPayment || !hasAddress" @click="submitCheckout">تأیید تغییرات و ادامه پرداخت</button>
                        <button type="button" class="mt-2 w-full rounded-2xl border border-dh-100 bg-white px-5 py-3 text-sm font-bold text-dh-700 hover:bg-dh-50" @click="rejectChanges">عدم تأیید و بازگشت به سبد</button>
                    </section>

                    <section v-else class="mt-6">
                        <div class="rounded-2xl bg-dh-green-50 p-4"><div class="font-black text-dh-green-700">سفارش آماده است</div><p class="mt-1 text-xs leading-6 text-dh-green-700/80">آدرس و قیمت سفارش را بررسی کرده‌اید.</p></div>
                        <button type="button" class="mt-3 w-full rounded-2xl bg-dh-700 px-5 py-3.5 text-sm font-black text-white shadow-sm hover:bg-dh-800 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!canProceedToPayment || !hasAddress" @click="submitCheckout">ثبت سفارش و ادامه به پرداخت</button>
                    </section>
                </aside>
            </section>
        </main>
    </div>
</template>
