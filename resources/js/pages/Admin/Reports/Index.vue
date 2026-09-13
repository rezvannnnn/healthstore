<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

interface Summary {
    orders_count: number;
    cancelled_count: number;
    gross_sales: number;
    successful_payments: number;
    items_sold: number;
    average_order: number;
}

interface Product {
    product_id: number;
    name: string;
    quantity: number;
    sales: number;
}

interface Props {
    summary: Summary;
    top_products: Product[];
    filters: { from: string; to: string };
}

const props = defineProps<Props>();

function applyFilter(event: Event) {
    const form = event.currentTarget as HTMLFormElement;
    const data = new FormData(form);
    router.get('/admin/reports', {
        from: data.get('from'),
        to: data.get('to'),
    }, { preserveState: true, replace: true });
}

function money(value: number) {
    return new Intl.NumberFormat('fa-IR').format(value);
}
</script>

<template>
    <Head title="گزارش‌ها" />

    <div class="mx-auto max-w-7xl space-y-6 p-6" dir="rtl">
        <div>
            <h1 class="text-2xl font-bold">گزارش‌ها</h1>
            <p class="mt-1 text-sm text-gray-500">گزارش فروش، سفارش‌ها و محصولات پرفروش</p>
        </div>

        <form class="grid gap-4 rounded-xl border bg-white p-5 shadow-sm md:grid-cols-3" @submit.prevent="applyFilter">
            <label class="space-y-1">
                <span class="text-sm">از تاریخ</span>
                <input name="from" type="date" :value="props.filters.from" class="w-full rounded-lg border p-2" />
            </label>
            <label class="space-y-1">
                <span class="text-sm">تا تاریخ</span>
                <input name="to" type="date" :value="props.filters.to" class="w-full rounded-lg border p-2" />
            </label>
            <div class="flex items-end">
                <button class="w-full rounded-lg bg-black px-5 py-2 text-white">نمایش گزارش</button>
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">تعداد سفارش‌ها</p><p class="mt-2 text-2xl font-bold">{{ money(props.summary.orders_count) }}</p></div>
            <div class="rounded-xl border bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">فروش ناخالص</p><p class="mt-2 text-2xl font-bold">{{ money(props.summary.gross_sales) }}</p></div>
            <div class="rounded-xl border bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">پرداخت‌های موفق</p><p class="mt-2 text-2xl font-bold">{{ money(props.summary.successful_payments) }}</p></div>
            <div class="rounded-xl border bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">تعداد اقلام فروخته‌شده</p><p class="mt-2 text-2xl font-bold">{{ money(props.summary.items_sold) }}</p></div>
            <div class="rounded-xl border bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">میانگین مبلغ سفارش</p><p class="mt-2 text-2xl font-bold">{{ money(props.summary.average_order) }}</p></div>
            <div class="rounded-xl border bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">سفارش‌های لغوشده</p><p class="mt-2 text-2xl font-bold">{{ money(props.summary.cancelled_count) }}</p></div>
        </div>

        <section class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <div class="border-b p-5"><h2 class="text-lg font-semibold">۱۰ محصول پرفروش</h2></div>
            <div v-if="props.top_products.length" class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-gray-50"><tr><th class="p-3">محصول</th><th class="p-3">تعداد فروش</th><th class="p-3">مبلغ فروش</th></tr></thead>
                    <tbody>
                        <tr v-for="product in props.top_products" :key="product.product_id" class="border-t">
                            <td class="p-3">{{ product.name }}</td><td class="p-3">{{ money(product.quantity) }}</td><td class="p-3">{{ money(product.sales) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="p-5 text-sm text-gray-500">در این بازه فروش ثبت‌شده‌ای وجود ندارد.</p>
        </section>
    </div>
</template>
