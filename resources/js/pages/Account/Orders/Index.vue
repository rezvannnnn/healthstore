<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
import CartLink from '@/components/CartLink.vue';

interface Order {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    total_amount: number | string;
    currency: string;
    created_at: string | null;
}

defineProps<{ orders: Order[] }>();

const statusLabels: Record<string, string> = {
    pending: 'در انتظار پرداخت',
    paid: 'پرداخت‌شده',
    processing: 'در حال پردازش',
    shipped: 'ارسال‌شده',
    delivered: 'تحویل‌شده',
    cancelled: 'لغوشده',
};

const paymentStatusLabels: Record<string, string> = {
    pending: 'در انتظار پرداخت',
    paid: 'پرداخت موفق',
    failed: 'پرداخت ناموفق',
    refunded: 'مستردشده',
    cancelled: 'لغوشده',
};

function formatAmount(value: number | string, currency: string): string {
    return `${Number(value).toLocaleString('fa-IR')} ${currency}`;
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString('fa-IR') : '—';
}

function statusClasses(status: string): string {
    switch (status) {
        case 'paid':
        case 'delivered':
            return 'bg-dh-green-50 text-dh-green-700';
        case 'shipped':
        case 'processing':
            return 'bg-dh-50 text-dh-700';
        case 'cancelled':
            return 'bg-red-50 text-red-700';
        case 'pending':
            return 'bg-amber-50 text-amber-700';
        default:
            return 'bg-dh-50 text-dh-muted';
    }
}

function paymentClasses(status: string): string {
    switch (status) {
        case 'paid':
            return 'text-dh-green-700';
        case 'failed':
        case 'cancelled':
            return 'text-red-600';
        default:
            return 'text-amber-700';
    }
}
</script>

<template>
    <Head title="سفارش‌های من" />
    <main dir="rtl" class="min-h-screen bg-dh-50 px-4 py-6 pb-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-dh-100">
                <Link href="/" class="flex shrink-0 items-center" aria-label="داروخونه">
                    <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Link href="/account/profile" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">پروفایل</Link>
                    <Link href="/account/addresses" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">آدرس‌ها</Link>
                    <CartLink />
                </nav>
            </header>

            <section class="mb-6 rounded-3xl bg-dh-800 p-6 text-white shadow-sm sm:p-8">
                <p class="text-sm font-bold text-dh-green-100">حساب کاربری</p>
                <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black sm:text-3xl">سفارش‌های من</h1>
                        <p class="mt-2 text-sm leading-7 text-dh-100">سوابق خرید، وضعیت سفارش و جزئیات پرداخت را اینجا ببینید.</p>
                    </div>
                    <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold">{{ orders.length }} سفارش</span>
                </div>
            </section>

            <section v-if="orders.length" class="space-y-4">
                <article v-for="order in orders" :key="order.id" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-black text-dh-900">{{ order.order_number }}</h2>
                                <span :class="statusClasses(order.status)" class="rounded-full px-3 py-1 text-xs font-bold">{{ statusLabels[order.status] || order.status }}</span>
                            </div>
                            <p class="mt-3 text-sm text-dh-muted">
                                {{ formatDate(order.created_at) }}
                                <span class="mx-1 text-dh-200">•</span>
                                پرداخت: <span :class="paymentClasses(order.payment_status)" class="font-bold">{{ paymentStatusLabels[order.payment_status] || order.payment_status }}</span>
                            </p>
                        </div>
                        <div class="flex items-center justify-between gap-5 md:justify-end">
                            <div class="text-left">
                                <div class="text-lg font-black text-dh-900">{{ formatAmount(order.total_amount, order.currency) }}</div>
                                <div class="mt-1 text-xs text-dh-muted">مبلغ نهایی سفارش</div>
                            </div>
                            <Link :href="`/orders/${order.order_number}`" class="rounded-2xl bg-dh-700 px-4 py-3 text-sm font-bold text-white hover:bg-dh-800">
                                جزئیات
                            </Link>
                        </div>
                    </div>
                </article>
            </section>

            <div v-else class="rounded-3xl border border-dashed border-dh-200 bg-white p-12 text-center">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-dh-50 text-dh-600">
                    <span class="text-2xl">⌁</span>
                </div>
                <h2 class="mt-4 font-black text-dh-900">هنوز سفارشی ثبت نکرده‌اید</h2>
                <p class="mt-2 text-sm text-dh-muted">محصولات مورد نیازتان را پیدا کنید و اولین سفارش را ثبت کنید.</p>
                <Link href="/products" class="mt-5 inline-flex rounded-2xl bg-dh-700 px-5 py-3 text-sm font-bold text-white hover:bg-dh-800">مشاهده محصولات</Link>
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
