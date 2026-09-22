<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
import CartLink from '@/components/CartLink.vue';
import { computed, ref } from 'vue';

interface OrderItem {
    id: number;
    product_id: number | null;
    product_name: string;
    product_sku: string | null;
    quantity: number;
    unit_price: number | string;
    discount_amount: number | string;
    total_amount: number | string;
}

interface Payment {
    id: number;
    amount: number | string;
    gateway: string | null;
    status: string;
    authority: string | null;
    transaction_id: string | null;
    reference_number: string | null;
    paid_at: string | null;
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    subtotal: number | string;
    discount_amount: number | string;
    shipping_amount: number | string;
    total_amount: number | string;
    currency: string;
    recipient_name: string | null;
    recipient_phone: string | null;
    province: string | null;
    city: string | null;
    shipping_address: string | null;
    postal_code: string | null;
    customer_note: string | null;
    confirmed_at: string | null;
    paid_at: string | null;
    cancelled_at: string | null;
    created_at: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
    items: OrderItem[];
    payments: Payment[];
}

const props = defineProps<{
    order: Order;
    success?: string;
    error?: string;
    info?: string;
}>();

const formatter = new Intl.NumberFormat('fa-IR');

const isStartingPayment = ref(false);
const isCancelling = ref(false);

function formatPrice(value: number | string): string {
    return formatter.format(Number(value)) + ' تومان';
}

function formatDateTime(value: string | null): string {
    return value
        ? new Date(value).toLocaleString('fa-IR', {
              dateStyle: 'short',
              timeStyle: 'short',
          })
        : '';
}

const timeline = computed(() => [
    {
        label: 'ثبت سفارش',
        at: props.order.created_at,
        complete: true,
    },
    {
        label: 'پرداخت موفق',
        at: props.order.paid_at,
        complete: props.order.payment_status === 'paid',
    },
    {
        label: 'در حال پردازش',
        at: props.order.confirmed_at,
        complete: ['processing', 'shipped', 'delivered'].includes(
            props.order.status,
        ),
    },
    {
        label: 'تحویل به ارسال',
        at: props.order.shipped_at,
        complete: ['shipped', 'delivered'].includes(props.order.status),
    },
    {
        label: 'تحویل سفارش',
        at: props.order.delivered_at,
        complete: props.order.status === 'delivered',
    },
]);

const orderStatusLabel = computed(() => {
    switch (props.order.status) {
        case 'pending':
            return 'در انتظار پرداخت';
        case 'paid':
            return 'پرداخت شده';
        case 'processing':
            return 'در حال پردازش';
        case 'shipped':
            return 'ارسال شده';
        case 'delivered':
            return 'تحویل شده';
        case 'cancelled':
            return 'لغو شده';
        case 'expired':
            return 'منقضی شده';
        default:
            return props.order.status;
    }
});

const paymentStatusLabel = computed(() => {
    switch (props.order.payment_status) {
        case 'pending':
            return 'در انتظار پرداخت';
        case 'paid':
            return 'پرداخت موفق';
        case 'failed':
            return 'پرداخت ناموفق';
        case 'refunded':
            return 'مسترد شده';
        case 'cancelled':
            return 'لغو شده';
        default:
            return props.order.payment_status;
    }
});

const orderStatusClasses = computed(() => {
    switch (props.order.status) {
        case 'paid':
        case 'delivered':
            return 'bg-dh-green-500/15 text-dh-green-100';
        case 'shipped':
        case 'processing':
            return 'bg-white/10 text-dh-100';
        case 'cancelled':
        case 'expired':
            return 'bg-red-500/15 text-red-100';
        case 'pending':
            return 'bg-amber-400/15 text-amber-100';
        default:
            return 'bg-white/10 text-dh-100';
    }
});

const latestPayment = computed(() => props.order.payments[props.order.payments.length - 1] ?? null);

const paymentStatusClasses = computed(() => {
    switch (props.order.payment_status) {
        case 'paid':
            return 'bg-dh-green-50 text-dh-green-700';
        case 'failed':
        case 'cancelled':
            return 'bg-red-50 text-red-700';
        default:
            return 'bg-amber-50 text-amber-700';
    }
});

const hasPayableAmount = computed(() => {
    return Number(props.order.total_amount) > 0;
});

const canPay = computed(() => {
    return (
        hasPayableAmount.value &&
        props.order.status === 'pending' &&
        props.order.payment_status === 'pending' &&
        !isStartingPayment.value
    );
});

function goBackToCheckout(): void {
    router.get('/checkout');
}

function goBackToOrders(): void {
    router.get('/account/orders');
}

function cancelOrder(): void {
    if (
        isCancelling.value ||
        props.order.status !== 'pending' ||
        props.order.payment_status === 'paid'
    ) {
        return;
    }

    if (! window.confirm('آیا از لغو این سفارش مطمئن هستید؟')) {
        return;
    }

    router.post(
        `/orders/${props.order.order_number}/cancel`,
        {},
        {
            preserveScroll: true,
            onStart: () => { isCancelling.value = true; },
            onFinish: () => { isCancelling.value = false; },
        },
    );
}

function startPayment(): void {
    if (!canPay.value) {
        return;
    }

    isStartingPayment.value = true;

    router.post(
        `/orders/${props.order.order_number}/payment`,
        {},
        {
            onFinish: () => {
                isStartingPayment.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`سفارش ${order.order_number}`" />
    <main dir="rtl" class="min-h-screen bg-dh-50 px-4 py-6 pb-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-dh-100">
                <Link href="/" class="flex shrink-0 items-center" aria-label="داروخونه">
                    <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Link href="/account/orders" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">سفارش‌ها</Link>
                    <Link href="/account/profile" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">پروفایل</Link>
                    <CartLink />
                </nav>
            </header>

            <section class="mb-6 rounded-3xl bg-dh-800 p-6 text-white shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p class="text-sm font-bold text-dh-green-100">پیگیری خرید</p>
                        <h1 class="mt-2 text-2xl font-black sm:text-3xl">جزئیات سفارش</h1>
                        <p class="mt-2 text-sm text-dh-100">شماره سفارش: <strong class="text-white">{{ order.order_number }}</strong></p>
                    </div>
                    <span :class="orderStatusClasses" class="rounded-full px-4 py-2 text-sm font-bold">{{ orderStatusLabel }}</span>
                </div>
            </section>

            <div v-if="success" class="mb-4 rounded-2xl border border-dh-green-100 bg-dh-green-50 p-4 text-sm font-medium text-dh-green-700">{{ success }}</div>
            <div v-if="error" class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ error }}</div>
            <div v-if="info" class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-medium text-blue-700">{{ info }}</div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-6">
                    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 sm:p-6">
                        <h2 class="mb-5 text-lg font-black text-dh-900">اقلام سفارش</h2>
                        <div v-if="order.items.length === 0" class="rounded-2xl bg-dh-50 p-6 text-center text-sm text-dh-muted">هیچ قلمی برای این سفارش ثبت نشده است.</div>
                        <div v-for="item in order.items" :key="item.id" class="flex flex-col gap-4 border-b border-dh-100 py-4 first:pt-0 last:border-b-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-dh-900">{{ item.product_name }}</h3>
                                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-dh-muted">
                                    <span v-if="item.product_sku">کد کالا: {{ item.product_sku }}</span>
                                    <span>تعداد: {{ item.quantity }}</span>
                                    <span>قیمت واحد: {{ formatPrice(item.unit_price) }}</span>
                                </div>
                            </div>
                            <strong class="text-dh-800">{{ formatPrice(item.total_amount) }}</strong>
                        </div>
                    </section>

                    <section v-if="order.recipient_name || order.recipient_phone || order.shipping_address" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 sm:p-6">
                        <h2 class="mb-5 text-lg font-black text-dh-900">اطلاعات گیرنده</h2>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div v-if="order.recipient_name"><span class="block text-xs text-dh-muted">نام گیرنده</span><strong class="mt-1 block text-sm text-dh-900">{{ order.recipient_name }}</strong></div>
                            <div v-if="order.recipient_phone"><span class="block text-xs text-dh-muted">شماره تماس</span><strong class="mt-1 block text-sm text-dh-900">{{ order.recipient_phone }}</strong></div>
                            <div v-if="order.province || order.city"><span class="block text-xs text-dh-muted">موقعیت</span><strong class="mt-1 block text-sm text-dh-900">{{ order.province }}<span v-if="order.province && order.city"> - </span>{{ order.city }}</strong></div>
                            <div v-if="order.postal_code"><span class="block text-xs text-dh-muted">کد پستی</span><strong class="mt-1 block text-sm text-dh-900">{{ order.postal_code }}</strong></div>
                            <div v-if="order.shipping_address" class="sm:col-span-2"><span class="block text-xs text-dh-muted">آدرس</span><strong class="mt-1 block text-sm leading-7 text-dh-900">{{ order.shipping_address }}</strong></div>
                        </div>
                    </section>

                    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 sm:p-6">
                        <h2 class="mb-5 text-lg font-black text-dh-900">روند سفارش</h2>
                        <div class="relative space-y-1">
                            <div class="absolute right-3 top-3 bottom-3 w-px bg-dh-100" aria-hidden="true"></div>
                            <div v-for="step in timeline" :key="step.label" class="relative flex gap-4 py-2">
                                <span
                                    :class="step.complete ? 'bg-dh-green-500 ring-dh-green-100' : 'bg-white ring-dh-100'"
                                    class="relative z-10 mt-0.5 grid size-6 shrink-0 place-items-center rounded-full ring-4"
                                >
                                    <span v-if="step.complete" class="size-2 rounded-full bg-white"></span>
                                </span>
                                <div class="min-w-0 flex-1 pb-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <strong :class="step.complete ? 'text-dh-900' : 'text-dh-muted'" class="text-sm">
                                            {{ step.label }}
                                        </strong>
                                        <span v-if="step.at" class="text-[11px] text-dh-muted">{{ formatDateTime(step.at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="order.status === 'cancelled'" class="mt-4 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-700">
                            سفارش در {{ formatDateTime(order.cancelled_at) }} لغو شده است.
                        </div>
                    </section>

                    <section v-if="latestPayment" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-black text-dh-900">جزئیات پرداخت</h2>
                            <span :class="paymentStatusClasses" class="rounded-full px-3 py-1 text-xs font-black">{{ paymentStatusLabel }}</span>
                        </div>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div v-if="latestPayment.gateway">
                                <span class="block text-xs text-dh-muted">درگاه</span>
                                <strong class="mt-1 block text-sm text-dh-900">{{ latestPayment.gateway }}</strong>
                            </div>
                            <div v-if="latestPayment.reference_number">
                                <span class="block text-xs text-dh-muted">شماره مرجع</span>
                                <strong class="mt-1 block text-sm text-dh-900">{{ latestPayment.reference_number }}</strong>
                            </div>
                            <div v-if="latestPayment.transaction_id">
                                <span class="block text-xs text-dh-muted">شناسه تراکنش</span>
                                <strong class="mt-1 block break-all text-sm text-dh-900">{{ latestPayment.transaction_id }}</strong>
                            </div>
                            <div v-if="latestPayment.paid_at">
                                <span class="block text-xs text-dh-muted">زمان پرداخت</span>
                                <strong class="mt-1 block text-sm text-dh-900">{{ formatDateTime(latestPayment.paid_at) }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 sm:p-6">
                        <h2 class="mb-5 text-lg font-black text-dh-900">وضعیت سفارش</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-dh-50 p-4"><span class="text-xs text-dh-muted">وضعیت سفارش</span><strong class="mt-1 block text-sm text-dh-900">{{ orderStatusLabel }}</strong></div>
                            <div class="rounded-2xl bg-dh-50 p-4"><span class="text-xs text-dh-muted">وضعیت پرداخت</span><strong :class="paymentStatusClasses" class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-black">{{ paymentStatusLabel }}</strong></div>
                        </div>
                    </section>
                </div>

                <aside class="h-fit space-y-6 lg:sticky lg:top-6">
                    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100">
                        <h2 class="mb-5 text-lg font-black text-dh-900">خلاصه مبلغ</h2>
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between gap-4"><span class="text-dh-muted">جمع کالاها</span><strong class="text-dh-800">{{ formatPrice(order.subtotal) }}</strong></div>
                            <div class="flex justify-between gap-4"><span class="text-dh-muted">تخفیف</span><strong class="text-dh-green-600">{{ formatPrice(order.discount_amount) }}</strong></div>
                            <div class="flex justify-between gap-4"><span class="text-dh-muted">هزینه ارسال</span><strong class="text-dh-800">{{ formatPrice(order.shipping_amount) }}</strong></div>
                            <div class="border-t border-dh-100 pt-4"><div class="flex justify-between gap-4"><span class="font-bold text-dh-900">مبلغ قابل پرداخت</span><strong class="text-lg font-black text-dh-800">{{ formatPrice(order.total_amount) }}</strong></div></div>
                        </div>
                    </section>

                    <section class="rounded-3xl bg-dh-800 p-5 text-white shadow-sm">
                        <h2 class="text-lg font-black">پرداخت سفارش</h2>
                        <div class="mt-4 rounded-2xl bg-white/10 p-4"><span class="text-xs text-dh-100">وضعیت پرداخت</span><strong class="mt-1 block">{{ paymentStatusLabel }}</strong></div>
                        <button v-if="canPay" type="button" :disabled="isStartingPayment" class="mt-4 w-full rounded-2xl bg-white px-4 py-3.5 font-black text-dh-800 transition hover:bg-dh-50 disabled:opacity-60" @click="startPayment">
                            {{ isStartingPayment ? 'در حال آماده‌سازی پرداخت...' : `پرداخت ${formatPrice(order.total_amount)}` }}
                        </button>
                        <div v-else-if="order.payment_status === 'paid'" class="mt-4 rounded-2xl bg-dh-green-500/15 p-4 text-sm text-dh-green-100">این سفارش با موفقیت پرداخت شده است.</div>
                        <div v-else-if="!hasPayableAmount" class="mt-4 rounded-2xl bg-white/10 p-4 text-sm text-dh-100">مبلغ این سفارش قابل پرداخت نیست.</div>
                        <div v-else-if="order.status === 'cancelled'" class="mt-4 rounded-2xl bg-red-500/15 p-4 text-sm text-red-100">این سفارش لغو شده است.</div>
                        <button
                            v-if="order.status === 'pending' && order.payment_status === 'pending'"
                            type="button"
                            :disabled="isCancelling"
                            class="mt-3 w-full rounded-2xl border border-red-200/20 bg-red-500/10 px-4 py-3 text-sm font-bold text-red-100 transition hover:bg-red-500/15 disabled:cursor-not-allowed disabled:opacity-60"
                            @click="cancelOrder"
                        >
                            {{ isCancelling ? 'در حال لغو سفارش…' : 'لغو سفارش' }}
                        </button>
                    </section>

                    <button
                        v-if="order.status === 'pending' && order.payment_status === 'pending'"
                        type="button"
                        class="w-full rounded-2xl border border-dh-100 bg-white px-4 py-3.5 text-sm font-bold text-dh-700 transition hover:bg-dh-50"
                        @click="goBackToCheckout"
                    >
                        بازگشت به تسویه حساب
                    </button>
                    <button
                        v-else
                        type="button"
                        class="w-full rounded-2xl border border-dh-100 bg-white px-4 py-3.5 text-sm font-bold text-dh-700 transition hover:bg-dh-50"
                        @click="goBackToOrders"
                    >
                        بازگشت به سفارش‌ها
                    </button>
                </aside>
            </div>
        </div>
    
            <nav class="fixed inset-x-4 bottom-4 z-40 mx-auto grid max-w-md grid-cols-4 gap-1 rounded-2xl border border-dh-100 bg-white/95 p-2 shadow-lg backdrop-blur sm:hidden" aria-label="ناوبری حساب کاربری">
                <Link href="/products" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    فروشگاه
                </Link>
                <Link href="/account/orders" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" aria-hidden="true"><path d="M7 4h10v16H7z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    سفارش‌ها
                </Link>
                <Link href="/account/addresses" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" aria-hidden="true"><path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.7"/></svg>
                    آدرس‌ها
                </Link>
                <Link href="/cart" class="flex flex-col items-center gap-1 rounded-xl bg-dh-50 px-2 py-2 text-[11px] font-bold text-dh-700" aria-label="سبد خرید">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" aria-hidden="true"><path d="M4 5h2l1.5 10.2a2 2 0 0 0 2 1.8h7.6a2 2 0 0 0 2-1.7L20 8H7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                    سبد
                </Link>
            </nav></main>
</template>
