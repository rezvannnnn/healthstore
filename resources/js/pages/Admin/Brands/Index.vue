<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Brand = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
    is_active: boolean;
    products_count: number;
};

type Pagination = {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

defineProps<{ brands: { data: Brand[] } & Pagination }>();

const pageUrl = (page: number) => `/admin/brands?page=${page}`;
</script>

<template>
    <Head title="مدیریت برندها" />
    <div
        dir="rtl"
        class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-6xl">
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-gray-500">HealthStore / مدیریت</p>
                    <h1 class="text-3xl font-bold">برندها</h1>
                    <p class="mt-2 text-gray-600">مدیریت برندهای محصولات</p>
                </div>
                <div class="flex gap-2">
                    <Link
                        href="/admin"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium"
                        >داشبورد</Link
                    ><Link
                        href="/admin/brands/create"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white"
                        >برند جدید</Link
                    >
                </div>
            </div>
            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 font-medium">برند</th>
                                <th class="px-4 py-3 font-medium">Slug</th>
                                <th class="px-4 py-3 font-medium">محصولات</th>
                                <th class="px-4 py-3 font-medium">وضعیت</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="brand in brands.data"
                                :key="brand.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-4 font-semibold">
                                    {{ brand.name }}
                                </td>
                                <td class="px-4 py-4 text-gray-500">
                                    {{ brand.slug }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ brand.products_count }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            brand.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-600'
                                        "
                                        >{{
                                            brand.is_active ? 'فعال' : 'غیرفعال'
                                        }}</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    <Link
                                        :href="`/admin/brands/${brand.id}/edit`"
                                        class="font-medium text-blue-600 hover:underline"
                                        >ویرایش</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="brands.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-12 text-center text-gray-500"
                                >
                                    برندی ثبت نشده است.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="brands.last_page > 1"
                    class="flex flex-col gap-3 border-t px-4 py-4 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        نمایش {{ brands.from }} تا {{ brands.to }} از
                        {{ brands.total }} برند
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="brands.current_page > 1"
                            :href="pageUrl(brands.current_page - 1)"
                            preserve-scroll
                            preserve-state
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium hover:bg-gray-50"
                            >قبلی</Link
                        >
                        <span
                            class="rounded-lg bg-gray-100 px-3 py-2 font-medium"
                            >صفحه {{ brands.current_page }} از
                            {{ brands.last_page }}</span
                        >
                        <Link
                            v-if="brands.current_page < brands.last_page"
                            :href="pageUrl(brands.current_page + 1)"
                            preserve-scroll
                            preserve-state
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium hover:bg-gray-50"
                            >بعدی</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
