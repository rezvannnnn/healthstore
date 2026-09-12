<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
type Movement = {
    id: number;
    type: string;
    quantity_delta: number;
    quantity_before: number;
    quantity_after: number;
    note: string | null;
    created_at: string;
    user: { name: string } | null;
};
defineProps<{
    inventory: {
        id: number;
        product: string;
        warehouse: string;
        quantity: number;
    };
    movements: {
        data: Movement[];
        current_page: number;
        last_page: number;
        total: number;
    };
}>();
const formatType = (type: string) => (type === 'increase' ? 'افزایش' : 'کاهش');
</script>
<template>
    <Head title="گردش موجودی" />
    <div dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900">
        <div class="mx-auto max-w-5xl">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">HealthStore / موجودی</p>
                    <h1 class="text-3xl font-bold">گردش موجودی</h1>
                    <p class="mt-2 text-gray-600">
                        {{ inventory.product }} — {{ inventory.warehouse }} —
                        موجودی فعلی: {{ inventory.quantity }}
                    </p>
                </div>
                <Link
                    href="/admin/inventory"
                    class="rounded-lg border bg-white px-4 py-2 text-sm"
                    >بازگشت</Link
                >
            </div>
            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">نوع</th>
                                <th class="px-4 py-3">تغییر</th>
                                <th class="px-4 py-3">قبل</th>
                                <th class="px-4 py-3">بعد</th>
                                <th class="px-4 py-3">یادداشت</th>
                                <th class="px-4 py-3">کاربر</th>
                                <th class="px-4 py-3">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="movement in movements.data"
                                :key="movement.id"
                            >
                                <td class="px-4 py-4">
                                    {{ formatType(movement.type) }}
                                </td>
                                <td class="px-4 py-4 font-semibold">
                                    {{ movement.quantity_delta > 0 ? '+' : ''
                                    }}{{ movement.quantity_delta }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ movement.quantity_before }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ movement.quantity_after }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ movement.note || '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ movement.user?.name || 'سیستم' }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ movement.created_at }}
                                </td>
                            </tr>
                            <tr v-if="movements.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-gray-500"
                                >
                                    گردشی ثبت نشده است.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t px-4 py-4 text-sm text-gray-600">
                    مجموع رکوردها: {{ movements.total }}
                </div>
            </div>
        </div>
    </div>
</template>
