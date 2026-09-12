<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Stats = {
    today_sales: number;
    month_sales: number;
    orders_count: number;
    pending_orders: number;
    processing_orders: number;
    customers_count: number;
    products_count: number;
    active_products_count: number;
    pending_payments: number;
    failed_payments: number;
    low_stock_products: number;
};

defineProps<{
    stats: Stats;
}>();

const formatAmount = (amount: number) =>
    new Intl.NumberFormat('fa-IR').format(amount);
</script>

<template>
    <Head title="پنل مدیریت" />

    <div
        dir="rtl"
        class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-gray-500">HealthStore</p>
                    <h1 class="text-3xl font-bold">پنل مدیریت</h1>
                    <p class="mt-2 text-gray-600">
                        نمای کلی فروشگاه و وضعیت عملیاتی
                    </p>
                </div>

                <Link
                    href="/"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100"
                >
                    بازگشت به فروشگاه
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">فروش امروز</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ formatAmount(stats.today_sales) }}
                    </p>
                </div>

                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">فروش این ماه</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ formatAmount(stats.month_sales) }}
                    </p>
                </div>

                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">کل سفارش‌ها</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.orders_count }}
                    </p>
                </div>

                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">مشتری‌ها</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.customers_count }}
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">سفارش‌های در انتظار</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.pending_orders }}
                    </p>
                </div>

                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">سفارش‌های در حال پردازش</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.processing_orders }}
                    </p>
                </div>

                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">پرداخت‌های در انتظار</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.pending_payments }}
                    </p>
                </div>

                <div
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                >
                    <p class="text-sm text-gray-500">موجودی کم</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.low_stock_products }}
                    </p>
                </div>
            </div>

            <div
                class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
            >
                <h2 class="text-lg font-semibold">وضعیت فروشگاه</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-500">کل محصولات</p>
                        <p class="mt-1 font-semibold">
                            {{ stats.products_count }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">محصولات فعال</p>
                        <p class="mt-1 font-semibold">
                            {{ stats.active_products_count }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">پرداخت ناموفق</p>
                        <p class="mt-1 font-semibold">
                            {{ stats.failed_payments }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
