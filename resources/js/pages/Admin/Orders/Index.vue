<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Order = {
    id: number;
    order_number: string;
    customer_name: string | null;
    customer_phone: string | null;
    status: string;
    payment_status: string;
    items_count: number;
    total_amount: number;
    created_at: string | null;
};

type Pagination = { current_page: number; last_page: number; total: number };
type Filters = { search: string; status: string; payment_status: string };

const props = defineProps<{
    orders: Order[];
    pagination: Pagination;
    filters: Filters;
}>();
const search = ref(props.filters.search);
const status = ref(props.filters.status || 'all');
const paymentStatus = ref(props.filters.payment_status || 'all');

const submitFilters = () => {
    router.get(
        '/admin/orders',
        {
            search: search.value,
            status: status.value,
            payment_status: paymentStatus.value,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    paymentStatus.value = 'all';
    router.get('/admin/orders', {}, { preserveState: true, replace: true });
};

const pageUrl = (page: number) => ({
    url: '/admin/orders',
    search: props.filters.search,
    status: props.filters.status,
    payment_status: props.filters.payment_status,
    page,
});

const formatAmount = (amount: number) =>
    new Intl.NumberFormat('fa-IR').format(amount);
const statusLabel = (value: string) =>
    ({
        pending: 'در انتظار',
        paid: 'پرداخت شده',
        processing: 'در حال پردازش',
        shipped: 'ارسال شده',
        delivered: 'تحویل شده',
        cancelled: 'لغو شده',
    })[value] || value;
const paymentLabel = (value: string) =>
    ({
        pending: 'در انتظار',
        paid: 'موفق',
        failed: 'ناموفق',
        refunded: 'برگشت خورده',
    })[value] || value;
</script>

<template>
    <Head title="مدیریت سفارش‌ها" />
    <div
        dir="rtl"
        class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-gray-500">HealthStore / مدیریت</p>
                    <h1 class="text-3xl font-bold">سفارش‌ها</h1>
                    <p class="mt-2 text-gray-600">
                        مشاهده، جستجو و پیگیری سفارش‌های مشتریان
                    </p>
                </div>
                <Link
                    href="/admin"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100"
                    >بازگشت به داشبورد</Link
                >
            </div>

            <div
                class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200"
            >
                <form
                    class="grid gap-3 md:grid-cols-4"
                    @submit.prevent="submitFilters"
                >
                    <input
                        v-model="search"
                        type="search"
                        placeholder="شماره سفارش، نام یا موبایل..."
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                    />
                    <select
                        v-model="status"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                    >
                        <option value="all">همه وضعیت سفارش</option>
                        <option value="pending">در انتظار</option>
                        <option value="paid">پرداخت شده</option>
                        <option value="processing">در حال پردازش</option>
                        <option value="shipped">ارسال شده</option>
                        <option value="delivered">تحویل شده</option>
                        <option value="cancelled">لغو شده</option>
                    </select>
                    <select
                        v-model="paymentStatus"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                    >
                        <option value="all">همه پرداخت‌ها</option>
                        <option value="pending">در انتظار</option>
                        <option value="paid">موفق</option>
                        <option value="failed">ناموفق</option>
                        <option value="refunded">برگشت خورده</option>
                    </select>
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="flex-1 rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            جستجو
                        </button>
                        <button
                            v-if="search || status !== 'all' || paymentStatus !== 'all'"
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium hover:bg-gray-50"
                            @click="resetFilters"
                        >
                            پاک کردن
                        </button>
                    </div>
                </form>
            </div>

            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 font-medium">سفارش</th>
                                <th class="px-4 py-3 font-medium">مشتری</th>
                                <th class="px-4 py-3 font-medium">اقلام</th>
                                <th class="px-4 py-3 font-medium">مبلغ</th>
                                <th class="px-4 py-3 font-medium">وضعیت</th>
                                <th class="px-4 py-3 font-medium">پرداخت</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="order in orders"
                                :key="order.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-4">
                                    <div class="font-semibold">
                                        {{ order.order_number }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{
                                            order.created_at
                                                ? new Date(
                                                      order.created_at,
                                                  ).toLocaleString('fa-IR')
                                                : '—'
                                        }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div>{{ order.customer_name || '—' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ order.customer_phone || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    {{ order.items_count }}
                                </td>
                                <td class="px-4 py-4 font-semibold">
                                    {{ formatAmount(order.total_amount) }} ریال
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs"
                                        >{{ statusLabel(order.status) }}</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs"
                                        >{{ paymentLabel(order.payment_status) }}</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    <Link
                                        :href="`/admin/orders/${order.id}`"
                                        class="font-medium text-blue-700 hover:underline"
                                        >جزئیات</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="orders.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-gray-500"
                                >
                                    سفارشی پیدا نشد.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    class="flex flex-col gap-3 border-t px-4 py-4 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span>مجموع: {{ pagination.total }} سفارش</span>
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="pagination.current_page > 1"
                            :href="pageUrl(pagination.current_page - 1)"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 hover:bg-gray-50"
                            preserve-scroll
                            preserve-state
                        >
                            قبلی
                        </Link>
                        <span>
                            صفحه {{ pagination.current_page }} از
                            {{ pagination.last_page }}
                        </span>
                        <Link
                            v-if="pagination.current_page < pagination.last_page"
                            :href="pageUrl(pagination.current_page + 1)"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 hover:bg-gray-50"
                            preserve-scroll
                            preserve-state
                        >
                            بعدی
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
