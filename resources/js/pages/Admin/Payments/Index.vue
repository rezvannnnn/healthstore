<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Payment = {
    id: number;
    order_id: number;
    order_number: string | null;
    customer_name: string | null;
    customer_phone: string | null;
    amount: number;
    gateway: string | null;
    status: string;
    transaction_id: string | null;
    reference_number: string | null;
    created_at: string | null;
    paid_at: string | null;
};
type Pagination = { current_page: number; last_page: number; total: number };
type Filters = { search: string; status: string; gateway: string };

const props = defineProps<{
    payments: Payment[];
    pagination: Pagination;
    filters: Filters;
    gateways: string[];
}>();
const search = ref(props.filters.search);
const status = ref(props.filters.status || 'all');
const gateway = ref(props.filters.gateway || 'all');

const submitFilters = () =>
    router.get(
        '/admin/payments',
        { search: search.value, status: status.value, gateway: gateway.value },
        { preserveState: true, replace: true },
    );

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    gateway.value = 'all';
    router.get('/admin/payments', {}, { preserveState: true, replace: true });
};

const pageUrl = (page: number) => {
    const params = new URLSearchParams({ page: String(page) });

    if (props.filters.search) params.set('search', props.filters.search);
    if (props.filters.status) params.set('status', props.filters.status);
    if (props.filters.gateway) params.set('gateway', props.filters.gateway);

    return `/admin/payments?${params.toString()}`;
};

const formatAmount = (amount: number) =>
    new Intl.NumberFormat('fa-IR').format(amount);
const statusLabel = (value: string) =>
    ({
        pending: 'در انتظار',
        paid: 'موفق',
        failed: 'ناموفق',
        cancelled: 'لغو شده',
        refunded: 'برگشت خورده',
    })[value] || value;
</script>

<template>
    <Head title="مدیریت پرداخت‌ها" />
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
                    <h1 class="text-3xl font-bold">پرداخت‌ها</h1>
                    <p class="mt-2 text-gray-600">
                        پیگیری وضعیت و جزئیات پرداخت‌های سفارش‌ها
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
                        placeholder="سفارش، تراکنش، مرجع یا مشتری..."
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                    />
                    <select
                        v-model="status"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                    >
                        <option value="all">همه وضعیت‌ها</option>
                        <option value="pending">در انتظار</option>
                        <option value="paid">موفق</option>
                        <option value="failed">ناموفق</option>
                        <option value="cancelled">لغو شده</option>
                        <option value="refunded">برگشت خورده</option>
                    </select>
                    <select
                        v-model="gateway"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                    >
                        <option value="all">همه درگاه‌ها</option>
                        <option
                            v-for="item in gateways"
                            :key="item"
                            :value="item"
                        >
                            {{ item }}
                        </option>
                    </select>
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="flex-1 rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            جستجو
                        </button>
                        <button
                            v-if="search || status !== 'all' || gateway !== 'all'"
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
                                <th class="px-4 py-3 font-medium">مبلغ</th>
                                <th class="px-4 py-3 font-medium">درگاه</th>
                                <th class="px-4 py-3 font-medium">وضعیت</th>
                                <th class="px-4 py-3 font-medium">تراکنش</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="payment in payments"
                                :key="payment.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-4 font-semibold">
                                    {{
                                        payment.order_number ||
                                        `#${payment.order_id}`
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    <div>
                                        {{ payment.customer_name || '—' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ payment.customer_phone || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-semibold">
                                    {{ formatAmount(payment.amount) }} ریال
                                </td>
                                <td class="px-4 py-4">
                                    {{ payment.gateway || '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs"
                                        >{{ statusLabel(payment.status) }}</span
                                    >
                                </td>
                                <td class="px-4 py-4 text-xs">
                                    {{
                                        payment.transaction_id ||
                                        payment.reference_number ||
                                        '—'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    <Link
                                        :href="`/admin/payments/${payment.id}`"
                                        class="font-medium text-blue-700 hover:underline"
                                        >جزئیات</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="payments.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-gray-500"
                                >
                                    پرداختی پیدا نشد.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    class="flex flex-col gap-3 border-t px-4 py-4 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span>مجموع: {{ pagination.total }} پرداخت</span>
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
