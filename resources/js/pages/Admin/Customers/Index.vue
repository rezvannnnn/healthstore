<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Customer = {
    id: number;
    name: string | null;
    phone: string | null;
    email: string | null;
    phone_verified: boolean;
    orders_count: number;
    created_at: string | null;
};
type Pagination = { current_page: number; last_page: number; total: number };
type Filters = { search: string };

const props = defineProps<{
    customers: Customer[];
    pagination: Pagination;
    filters: Filters;
}>();

const search = ref(props.filters.search);

const submitSearch = () =>
    router.get(
        '/admin/customers',
        { search: search.value },
        { preserveState: true, replace: true },
    );

const resetSearch = () => {
    search.value = '';
    router.get('/admin/customers', {}, { preserveState: true, replace: true });
};

const pageUrl = (page: number) => {
    const params = new URLSearchParams({ page: String(page) });

    if (props.filters.search) {
        params.set('search', props.filters.search);
    }

    return `/admin/customers?${params.toString()}`;
};
</script>

<template>
    <Head title="مدیریت مشتریان" />
    <div
        dir="rtl"
        class="min-h-screen bg-dh-50 px-4 py-8 text-dh-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-dh-muted">HealthStore / مدیریت</p>
                    <h1 class="text-3xl font-bold">مشتریان</h1>
                    <p class="mt-2 text-dh-700">
                        مشاهده اطلاعات مشتریان و سابقه سفارش‌ها
                    </p>
                </div>
                <Link
                    href="/admin"
                    class="rounded-lg border border-dh-200 bg-white px-4 py-2 text-sm font-medium hover:bg-dh-50"
                    >بازگشت به داشبورد</Link
                >
            </div>

            <div
                class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-dh-100"
            >
                <form class="flex gap-3" @submit.prevent="submitSearch">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="نام، موبایل یا ایمیل..."
                        class="min-w-0 flex-1 rounded-lg border border-dh-200 px-4 py-2 text-sm"
                    />
                    <button
                        type="submit"
                        class="rounded-lg bg-dh-700 px-5 py-2 text-sm font-medium text-white hover:bg-dh-800"
                    >
                        جستجو
                    </button>
                    <button
                        v-if="search"
                        type="button"
                        class="rounded-lg border border-dh-200 bg-white px-5 py-2 text-sm font-medium hover:bg-dh-50"
                        @click="resetSearch"
                    >
                        پاک کردن
                    </button>
                </form>
            </div>

            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-dh-100"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-dh-50 text-dh-700">
                            <tr>
                                <th class="px-4 py-3 font-medium">مشتری</th>
                                <th class="px-4 py-3 font-medium">موبایل</th>
                                <th class="px-4 py-3 font-medium">ایمیل</th>
                                <th class="px-4 py-3 font-medium">
                                    تأیید موبایل
                                </th>
                                <th class="px-4 py-3 font-medium">سفارش‌ها</th>
                                <th class="px-4 py-3 font-medium">عضویت</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dh-100">
                            <tr
                                v-for="customer in customers"
                                :key="customer.id"
                                class="hover:bg-dh-50"
                            >
                                <td class="px-4 py-4 font-medium">
                                    {{ customer.name || 'بدون نام' }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ customer.phone || '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ customer.email || '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs"
                                        :class="
                                            customer.phone_verified
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-dh-50 text-dh-700'
                                        "
                                    >
                                        {{
                                            customer.phone_verified
                                                ? 'تأیید شده'
                                                : 'تأیید نشده'
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    {{ customer.orders_count }}
                                </td>
                                <td class="px-4 py-4 text-xs text-dh-muted">
                                    {{
                                        customer.created_at
                                            ? new Date(
                                                  customer.created_at,
                                              ).toLocaleDateString('fa-IR')
                                            : '—'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    <Link
                                        :href="`/admin/customers/${customer.id}`"
                                        class="font-medium text-dh-700 hover:underline"
                                        >جزئیات</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="customers.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-dh-muted"
                                >
                                    مشتری‌ای پیدا نشد.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    class="flex flex-col gap-3 border-t px-4 py-4 text-sm text-dh-700 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span>مجموع: {{ pagination.total }} مشتری</span>
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="pagination.current_page > 1"
                            :href="pageUrl(pagination.current_page - 1)"
                            class="rounded-lg border border-dh-200 bg-white px-3 py-1.5 hover:bg-dh-50"
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
                            v-if="
                                pagination.current_page < pagination.last_page
                            "
                            :href="pageUrl(pagination.current_page + 1)"
                            class="rounded-lg border border-dh-200 bg-white px-3 py-1.5 hover:bg-dh-50"
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
