<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
defineProps<{
    logs: {
        data: {
            id: number;
            actor_name: string | null;
            subject_type: string;
            subject_id: number;
            event: string;
            changed_fields: string;
            created_at: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();
</script>
<template>
    <Head title="تاریخچه تغییرات مدیریت" />
    <main dir="rtl" class="mx-auto max-w-6xl space-y-5 p-6">
        <Link href="/admin">بازگشت به مدیریت</Link>
        <h1 class="text-2xl font-bold">تاریخچه تغییرات</h1>
        <p>
            عامل، زمان و نام فیلدهای تغییرکرده ثبت می‌شوند. رمزها و اطلاعات
            محرمانه در این گزارش نگهداری نمی‌شوند.
        </p>
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>بخش و شناسه</th>
                        <th>عملیات</th>
                        <th>فیلدها</th>
                        <th>زمان</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs.data" :key="log.id" class="border-b">
                        <td class="p-3">
                            {{ log.actor_name || 'حساب حذف‌شده' }}
                        </td>
                        <td>{{ log.subject_type }} #{{ log.subject_id }}</td>
                        <td>{{ log.event }}</td>
                        <td>{{ log.changed_fields }}</td>
                        <td dir="ltr">
                            {{
                                new Date(log.created_at + 'Z').toLocaleString(
                                    'fa-IR',
                                    { timeZone: 'Asia/Tehran' },
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex gap-3">
            <template v-for="(link, index) in logs.links" :key="index"
                ><Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded border px-3 py-2"
                    >{{
                        index === 0
                            ? 'قبلی'
                            : index === logs.links.length - 1
                              ? 'بعدی'
                              : link.label
                    }}</Link
                ></template
            >
        </div>
    </main>
</template>
