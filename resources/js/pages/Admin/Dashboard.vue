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

const sections = [
    {
        href: '/admin/products',
        label: 'محصولات',
        description: 'محصولات و قیمت‌ها',
    },
    {
        href: '/admin/categories',
        label: 'دسته‌بندی‌ها',
        description: 'ساختار دسته‌ها',
    },
    { href: '/admin/brands', label: 'برندها', description: 'برندهای محصولات' },
    {
        href: '/admin/inventory',
        label: 'موجودی',
        description: 'موجودی و گردش انبار',
    },
    {
        href: '/admin/orders',
        label: 'سفارش‌ها',
        description: 'پیگیری سفارش‌ها',
    },
    {
        href: '/admin/payments',
        label: 'پرداخت‌ها',
        description: 'وضعیت تراکنش‌ها',
    },
    {
        href: '/admin/customers',
        label: 'مشتری‌ها',
        description: 'مشتریان فروشگاه',
    },
    {
        href: '/admin/coupons',
        label: 'کدهای تخفیف',
        description: 'کوپن و محدودیت مصرف',
    },
    { href: '/admin/articles', label: 'مقالات', description: 'محتوای وبلاگ' },
    {
        href: '/admin/reports',
        label: 'گزارش‌ها',
        description: 'گزارش‌های مدیریتی',
    },
    {
        href: '/admin/settings',
        label: 'تنظیمات',
        description: 'تنظیمات فروشگاه',
    },
];
</script>

<template>
    <Head title="پنل مدیریت" />

    <div
        dir="rtl"
        class="min-h-screen bg-dh-50 px-4 py-8 text-dh-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-dh-muted">HealthStore</p>
                    <h1 class="text-3xl font-bold">پنل مدیریت</h1>
                    <p class="mt-2 text-dh-muted">
                        نمای کلی فروشگاه و دسترسی سریع به بخش‌های مدیریتی
                    </p>
                </div>

                <Link
                    href="/"
                    class="rounded-xl border border-dh-100 bg-white px-4 py-2 text-sm font-bold text-dh-700 shadow-sm hover:bg-dh-50"
                >
                    بازگشت به فروشگاه
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    href="/admin/reports"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">فروش امروز</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ formatAmount(stats.today_sales) }}
                    </p>
                </Link>

                <Link
                    href="/admin/reports"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">فروش این ماه</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ formatAmount(stats.month_sales) }}
                    </p>
                </Link>

                <Link
                    href="/admin/orders"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">کل سفارش‌ها</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.orders_count }}
                    </p>
                </Link>

                <Link
                    href="/admin/customers"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">مشتری‌ها</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.customers_count }}
                    </p>
                </Link>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    href="/admin/orders?status=pending"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">سفارش‌های در انتظار</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.pending_orders }}
                    </p>
                </Link>

                <Link
                    href="/admin/orders?status=processing"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">سفارش‌های در حال پردازش</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.processing_orders }}
                    </p>
                </Link>

                <Link
                    href="/admin/payments?status=pending"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">پرداخت‌های در انتظار</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.pending_payments }}
                    </p>
                </Link>

                <Link
                    href="/admin/inventory"
                    class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-dh-100 transition hover:ring-dh-200"
                >
                    <p class="text-sm text-dh-muted">موجودی کم</p>
                    <p class="mt-2 text-2xl font-bold">
                        {{ stats.low_stock_products }}
                    </p>
                </Link>
            </div>

            <div
                class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">مدیریت بخش‌ها</h2>
                        <p class="mt-1 text-sm text-dh-muted">
                            دسترسی مستقیم به تمام بخش‌های پنل مدیریت
                        </p>
                    </div>
                </div>

                <div
                    class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Link
                        v-for="section in sections"
                        :key="section.href"
                        :href="section.href"
                        class="rounded-lg border border-dh-100 bg-dh-50 p-4 transition hover:border-dh-200 hover:bg-white"
                    >
                        <div class="font-semibold">{{ section.label }}</div>
                        <div class="mt-1 text-xs text-dh-muted">
                            {{ section.description }}
                        </div>
                    </Link>
                </div>
            </div>

            <div
                class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
            >
                <h2 class="text-lg font-semibold">وضعیت فروشگاه</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <Link
                        href="/admin/products"
                        class="rounded-lg bg-dh-50 p-4 hover:bg-dh-50"
                    >
                        <p class="text-sm text-dh-muted">کل محصولات</p>
                        <p class="mt-1 font-semibold">
                            {{ stats.products_count }}
                        </p>
                    </Link>
                    <Link
                        href="/admin/products?status=active"
                        class="rounded-lg bg-dh-50 p-4 hover:bg-dh-50"
                    >
                        <p class="text-sm text-dh-muted">محصولات فعال</p>
                        <p class="mt-1 font-semibold">
                            {{ stats.active_products_count }}
                        </p>
                    </Link>
                    <Link
                        href="/admin/payments?status=failed"
                        class="rounded-lg bg-dh-50 p-4 hover:bg-dh-50"
                    >
                        <p class="text-sm text-dh-muted">پرداخت ناموفق</p>
                        <p class="mt-1 font-semibold">
                            {{ stats.failed_payments }}
                        </p>
                    </Link>
                    <Link
                        href="/admin/coupons"
                        class="rounded-lg bg-dh-50 p-4 hover:bg-dh-50"
                    >
                        <p class="text-sm text-dh-muted">کدهای تخفیف</p>
                        <p class="mt-1 font-semibold">مدیریت</p>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
