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

    <main class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-5xl space-y-6">
            <header class="flex items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        سفارش‌های من
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        سوابق سفارش‌های ثبت‌شده شما
                    </p>
                </div>
                <Link
                    href="/account/addresses"
                    class="text-sm font-medium text-indigo-600 hover:underline"
                    >آدرس‌ها</Link
                >
            </header>

            <section v-if="orders.length" class="space-y-4">
                <article
                    v-for="order in orders"
                    :key="order.id"
                    class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
                >
                    <div
                        class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ order.order_number }}
                                </h2>
                                <span
                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{
                                        statusLabels[order.status] ||
                                        order.status
                                    }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ formatDate(order.created_at) }} · وضعیت پرداخت:
                                {{
                                    paymentStatusLabels[order.payment_status] ||
                                    order.payment_status
                                }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div
                                class="font-semibold text-gray-900 dark:text-white"
                            >
                                {{
                                    formatAmount(
                                        order.total_amount,
                                        order.currency,
                                    )
                                }}
                            </div>
                            <Link
                                :href="`/orders/${order.order_number}`"
                                class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:underline"
                                >مشاهده سفارش</Link
                            >
                        </div>
                    </div>
                </article>
            </section>
            <div
                v-else
                class="rounded-2xl border border-dashed border-gray-300 p-10 text-center text-gray-500 dark:border-gray-700"
            >
                هنوز سفارشی ثبت نکرده‌اید.
            </div>
        </div>
    </main>
</template>
