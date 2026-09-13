<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

type Customer = {
    id: number;
    name: string | null;
    phone: string | null;
    email: string | null;
    phone_verified: boolean;
    created_at: string | null;
};

type Order = {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    items_count: number;
    total_amount: number;
    created_at: string | null;
};

type Pagination = {
    current_page: number;
    last_page: number;
    total: number;
};

const props = defineProps<{
    customer: Customer;
    orders: Order[];
    pagination: Pagination;
}>();

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

function goToPage(page: number) {
    if (
        page < 1 ||
        page > props.pagination.last_page ||
        page === props.pagination.current_page
    ) {
        return;
    }

    router.get(
        `/admin/customers/${props.customer.id}`,
        { page },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head
        :title="`مشتری ${props.customer.name || props.customer.phone || props.customer.id}`"
    />
    <div
        dir="rtl"
        class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-5xl">
            <div class="mb-8 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">HealthStore / مشتریان</p>
                    <h1 class="text-2xl font-bold">
                        {{ props.customer.name || 'بدون نام' }}
                    </h1>
                </div>
                <Link
                    href="/admin/customers"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-100"
                    >بازگشت</Link
                >
            </div>
            <section
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
            >
                <h2 class="mb-5 font-bold">اطلاعات مشتری</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs text-gray-500">نام</p>
                        <p class="mt-1 font-medium">
                            {{ props.customer.name || '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">موبایل</p>
                        <p class="mt-1 font-medium">
                            {{ props.customer.phone || '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">ایمیل</p>
                        <p class="mt-1 font-medium">
                            {{ props.customer.email || '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">تأیید موبایل</p>
                        <p class="mt-1 font-medium">
                            {{
                                props.customer.phone_verified
                                    ? 'تأیید شده'
                                    : 'تأیید نشده'
                            }}
                        </p>
                    </div>
                </div>
            </section>
            <section
                class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
            >
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-bold">آخرین سفارش‌ها</h2>
                    <span class="text-sm text-gray-500">
                        {{ props.pagination.total }} سفارش
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 font-medium">سفارش</th>
                                <th class="px-4 py-3 font-medium">اقلام</th>
                                <th class="px-4 py-3 font-medium">مبلغ</th>
                                <th class="px-4 py-3 font-medium">وضعیت</th>
                                <th class="px-4 py-3 font-medium">پرداخت</th>
                                <th class="px-4 py-3 font-medium">تاریخ</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in props.orders" :key="order.id">
                                <td class="px-4 py-4 font-semibold">
                                    {{ order.order_number }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ order.items_count }}
                                </td>
                                <td class="px-4 py-4 font-medium">
                                    {{ formatAmount(order.total_amount) }} ریال
                                </td>
                                <td class="px-4 py-4">
                                    {{ statusLabel(order.status) }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ paymentLabel(order.payment_status) }}
                                </td>
                                <td class="px-4 py-4 text-xs text-gray-500">
                                    {{
                                        order.created_at
                                            ? new Date(
                                                  order.created_at,
                                              ).toLocaleDateString('fa-IR')
                                            : '—'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    <Link
                                        :href="`/admin/orders/${order.id}`"
                                        class="font-medium text-blue-700 hover:underline"
                                        >مشاهده</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="props.orders.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-gray-500"
                                >
                                    سفارشی برای این مشتری ثبت نشده است.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="props.pagination.last_page > 1"
                    class="flex items-center justify-between border-t px-5 py-4 text-sm"
                >
                    <button
                        :disabled="props.pagination.current_page === 1"
                        class="rounded border px-3 py-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="goToPage(props.pagination.current_page - 1)"
                    >
                        قبلی
                    </button>
                    <span>
                        صفحه {{ props.pagination.current_page }} از
                        {{ props.pagination.last_page }}
                    </span>
                    <button
                        :disabled="
                            props.pagination.current_page ===
                            props.pagination.last_page
                        "
                        class="rounded border px-3 py-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="goToPage(props.pagination.current_page + 1)"
                    >
                        بعدی
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
