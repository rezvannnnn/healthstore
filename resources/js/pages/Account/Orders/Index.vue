<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="سفارش‌های من" />
    <main dir="rtl" class="min-h-screen bg-dh-50 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-dh-100">
                <Link href="/products" class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-2xl bg-dh-700 text-white">
                        <svg viewBox="0 0 48 48" class="size-7" fill="none" aria-hidden="true">
                            <path d="M11 16h26l-3 17H14l-3-17Z" stroke="currentColor" stroke-width="3" />
                            <path d="M17 16c0-5 3-8 7-8s7 3 7 8" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            <path d="m22 24 3 3 7-7" stroke="#77c8a0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span>
                        <strong class="block text-lg font-black text-dh-800">داروخونه</strong>
                        <span class="text-xs text-dh-muted">دارو و محصولات بهداشتی</span>
                    </span>
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Link href="/account/profile" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">پروفایل</Link>
                    <Link href="/account/addresses" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">آدرس‌ها</Link>
                    <Link href="/cart" class="rounded-xl bg-dh-700 px-4 py-2 font-semibold text-white hover:bg-dh-800">سبد خرید</Link>
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
                                <span class="rounded-full bg-dh-50 px-3 py-1 text-xs font-bold text-dh-700">{{ statusLabels[order.status] || order.status }}</span>
                            </div>
                            <p class="mt-3 text-sm text-dh-muted">
                                {{ formatDate(order.created_at) }}
                                <span class="mx-1 text-dh-200">•</span>
                                پرداخت: {{ paymentStatusLabels[order.payment_status] || order.payment_status }}
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
    </main>
</template>
