<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Category = {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    products_count: number;
    parent: { id: number; name: string } | null;
};

type Pagination = {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

defineProps<{ categories: { data: Category[] } & Pagination }>();

const pageUrl = (page: number) => `/admin/categories?page=${page}`;
</script>

<template>
    <Head title="مدیریت دسته‌بندی‌ها" />
    <div
        dir="rtl"
        class="min-h-screen bg-dh-50 px-4 py-8 text-dh-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-6xl">
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-dh-muted">HealthStore / مدیریت</p>
                    <h1 class="text-3xl font-bold">دسته‌بندی‌ها</h1>
                    <p class="mt-2 text-dh-700">
                        مدیریت دسته‌بندی محصولات و زیر‌دسته‌ها
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        href="/admin"
                        class="rounded-lg border border-dh-200 bg-white px-4 py-2 text-sm font-medium hover:bg-dh-50"
                        >داشبورد</Link
                    >
                    <Link
                        href="/admin/categories/create"
                        class="rounded-lg bg-dh-700 px-4 py-2 text-sm font-medium text-white hover:bg-dh-800"
                        >دسته جدید</Link
                    >
                </div>
            </div>
            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-dh-100"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-dh-50 text-dh-700">
                            <tr>
                                <th class="px-4 py-3 font-medium">نام</th>
                                <th class="px-4 py-3 font-medium">والد</th>
                                <th class="px-4 py-3 font-medium">Slug</th>
                                <th class="px-4 py-3 font-medium">محصولات</th>
                                <th class="px-4 py-3 font-medium">وضعیت</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dh-100">
                            <tr
                                v-for="category in categories.data"
                                :key="category.id"
                                class="hover:bg-dh-50"
                            >
                                <td class="px-4 py-4 font-semibold">
                                    {{ category.name }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ category.parent?.name || 'دسته اصلی' }}
                                </td>
                                <td class="px-4 py-4 text-dh-muted">
                                    {{ category.slug }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ category.products_count }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            category.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-dh-50 text-dh-700'
                                        "
                                        >{{
                                            category.is_active
                                                ? 'فعال'
                                                : 'غیرفعال'
                                        }}</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    <Link
                                        :href="`/admin/categories/${category.id}/edit`"
                                        class="font-medium text-dh-700 hover:underline"
                                        >ویرایش</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-12 text-center text-dh-muted"
                                >
                                    دسته‌بندی‌ای ثبت نشده است.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="categories.last_page > 1"
                    class="flex flex-col gap-3 border-t px-4 py-4 text-sm text-dh-700 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        نمایش {{ categories.from }} تا {{ categories.to }} از
                        {{ categories.total }} دسته‌بندی
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="categories.current_page > 1"
                            :href="pageUrl(categories.current_page - 1)"
                            preserve-scroll
                            preserve-state
                            class="rounded-lg border border-dh-200 bg-white px-3 py-2 font-medium hover:bg-dh-50"
                            >قبلی</Link
                        >
                        <span
                            class="rounded-lg bg-dh-50 px-3 py-2 font-medium"
                            >صفحه {{ categories.current_page }} از
                            {{ categories.last_page }}</span
                        >
                        <Link
                            v-if="
                                categories.current_page < categories.last_page
                            "
                            :href="pageUrl(categories.current_page + 1)"
                            preserve-scroll
                            preserve-state
                            class="rounded-lg border border-dh-200 bg-white px-3 py-2 font-medium hover:bg-dh-50"
                            >بعدی</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
