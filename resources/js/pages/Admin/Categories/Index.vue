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

defineProps<{ categories: Category[] }>();
</script>

<template>
    <Head title="مدیریت دسته‌بندی‌ها" />
    <div dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-500">HealthStore / مدیریت</p>
                    <h1 class="text-3xl font-bold">دسته‌بندی‌ها</h1>
                    <p class="mt-2 text-gray-600">مدیریت دسته‌بندی محصولات و زیر‌دسته‌ها</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100">داشبورد</Link>
                    <Link href="/admin/categories/create" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">دسته جدید</Link>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-gray-50 text-gray-600"><tr>
                            <th class="px-4 py-3 font-medium">نام</th><th class="px-4 py-3 font-medium">والد</th><th class="px-4 py-3 font-medium">Slug</th><th class="px-4 py-3 font-medium">محصولات</th><th class="px-4 py-3 font-medium">وضعیت</th><th class="px-4 py-3"></th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
                                <td class="px-4 py-4 font-semibold">{{ category.name }}</td>
                                <td class="px-4 py-4">{{ category.parent?.name || 'دسته اصلی' }}</td>
                                <td class="px-4 py-4 text-gray-500">{{ category.slug }}</td>
                                <td class="px-4 py-4">{{ category.products_count }}</td>
                                <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="category.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">{{ category.is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                                <td class="px-4 py-4"><Link :href="`/admin/categories/${category.id}/edit`" class="font-medium text-blue-600 hover:underline">ویرایش</Link></td>
                            </tr>
                            <tr v-if="categories.length === 0"><td colspan="6" class="px-4 py-12 text-center text-gray-500">دسته‌بندی‌ای ثبت نشده است.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
