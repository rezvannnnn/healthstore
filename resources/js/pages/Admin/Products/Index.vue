<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Product = {
    id: number;
    name: string;
    sku: string | null;
    brand: string | null;
    category: string | null;
    image: string | null;
    price: number | null;
    is_active: boolean;
    is_featured: boolean;
    physical_quantity: number;
    reserved_quantity: number;
    available_quantity: number;
};

type Pagination = {
    current_page: number;
    last_page: number;
    total: number;
};

type Filters = {
    search: string;
    status: string;
};

const props = defineProps<{
    products: Product[];
    pagination: Pagination;
    filters: Filters;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status || 'all');

const submitFilters = () => {
    router.get(
        '/admin/products',
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );
};

const formatAmount = (amount: number | null) =>
    amount === null ? '—' : new Intl.NumberFormat('fa-IR').format(amount);
</script>

<template>
    <Head title="مدیریت محصولات" />

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
                    <h1 class="text-3xl font-bold">محصولات</h1>
                    <p class="mt-2 text-gray-600">
                        مدیریت و بررسی موجودی محصولات فروشگاه
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/admin"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100"
                    >
                        داشبورد
                    </Link>
                    <Link
                        href="/admin/products/create"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        محصول جدید
                    </Link>
                </div>
            </div>

            <div
                class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200"
            >
                <form
                    class="flex flex-col gap-3 md:flex-row"
                    @submit.prevent="submitFilters"
                >
                    <input
                        v-model="search"
                        type="search"
                        placeholder="نام، SKU یا بارکد..."
                        class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm outline-none focus:border-gray-500"
                    />
                    <select
                        v-model="status"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                    >
                        <option value="all">همه محصولات</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                    </select>
                    <button
                        type="submit"
                        class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        جستجو
                    </button>
                </form>
            </div>

            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 font-medium">محصول</th>
                                <th class="px-4 py-3 font-medium">دسته / برند</th>
                                <th class="px-4 py-3 font-medium">قیمت</th>
                                <th class="px-4 py-3 font-medium">موجودی فیزیکی</th>
                                <th class="px-4 py-3 font-medium">رزرو شده</th>
                                <th class="px-4 py-3 font-medium">قابل فروش</th>
                                <th class="px-4 py-3 font-medium">وضعیت</th>
                                <th class="px-4 py-3 font-medium">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="product in products"
                                :key="product.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-4">
                                    <div class="font-semibold">
                                        {{ product.name }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        SKU: {{ product.sku || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div>
                                        {{ product.category || 'بدون دسته' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ product.brand || 'بدون برند' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-medium">
                                    {{ formatAmount(product.price) }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ product.physical_quantity }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ product.reserved_quantity }}
                                </td>
                                <td class="px-4 py-4 font-semibold">
                                    {{ product.available_quantity }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            product.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{ product.is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <Link
                                        :href="`/admin/products/${product.id}/edit`"
                                        class="font-medium text-blue-700 hover:underline"
                                    >
                                        ویرایش
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-12 text-center text-gray-500"
                                >
                                    محصولی پیدا نشد.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex items-center justify-between border-t px-4 py-4 text-sm text-gray-600"
                >
                    <span>مجموع: {{ pagination.total }} محصول</span>
                    <span>
                        صفحه {{ pagination.current_page }} از
                        {{ pagination.last_page }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
