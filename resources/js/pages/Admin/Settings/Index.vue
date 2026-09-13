<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    settings: Record<string, string>;
}

const props = defineProps<Props>();
const form = useForm({ ...props.settings });

function submit() {
    form.put('/admin/settings');
}
</script>

<template>
    <Head title="تنظیمات فروشگاه" />

    <div class="mx-auto max-w-4xl space-y-6 p-6" dir="rtl">
        <div>
            <h1 class="text-2xl font-bold">تنظیمات فروشگاه</h1>
            <p class="mt-1 text-sm text-gray-500">
                اطلاعات عمومی و تنظیمات پایه فروشگاه
            </p>
        </div>

        <form
            class="space-y-6 rounded-xl border bg-white p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">اطلاعات فروشگاه</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-1">
                        <span>نام فروشگاه</span>
                        <input
                            v-model="form.store_name"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                    <label class="space-y-1">
                        <span>تلفن پشتیبانی</span>
                        <input
                            v-model="form.support_phone"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                    <label class="space-y-1">
                        <span>ایمیل پشتیبانی</span>
                        <input
                            v-model="form.support_email"
                            type="email"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                    <label class="space-y-1">
                        <span>واحد پول</span>
                        <input
                            v-model="form.currency"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                </div>
                <label class="block space-y-1">
                    <span>آدرس فروشگاه</span>
                    <textarea
                        v-model="form.store_address"
                        rows="3"
                        class="w-full rounded-lg border p-2"
                    />
                </label>
            </section>

            <section class="space-y-4 border-t pt-6">
                <h2 class="text-lg font-semibold">سفارش و ارسال</h2>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="space-y-1">
                        <span>هزینه ارسال</span>
                        <input
                            v-model="form.shipping_fee"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                    <label class="space-y-1">
                        <span>ارسال رایگان از مبلغ</span>
                        <input
                            v-model="form.free_shipping_threshold"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                    <label class="space-y-1">
                        <span>حداقل مبلغ سفارش</span>
                        <input
                            v-model="form.min_order_amount"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                </div>
            </section>

            <section class="space-y-4 border-t pt-6">
                <h2 class="text-lg font-semibold">منطقه زمانی</h2>
                <input
                    v-model="form.timezone"
                    class="w-full rounded-lg border p-2 md:max-w-md"
                />
            </section>

            <div class="flex justify-end border-t pt-5">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-black px-6 py-2 text-white disabled:opacity-50"
                >
                    {{ form.processing ? 'در حال ذخیره...' : 'ذخیره تنظیمات' }}
                </button>
            </div>
        </form>
    </div>
</template>
