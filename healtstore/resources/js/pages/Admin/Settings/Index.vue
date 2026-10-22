<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    settings: {
        store_name: string;
        support_phone: string;
        support_email: string;
        store_address: string;
        support_hours: string;
        license_title: string;
        license_url: string;
        shipping_fee: string;
        free_shipping_threshold: string;
        min_order_amount: string;
        currency: string;
        timezone: string;
        shipping_policy: string;
        returns_policy: string;
        privacy_policy: string;
    };
    faqs: { question: string; answer: string }[];
}

const props = defineProps<Props>();
const form = useForm({
    ...props.settings,
    faqs: props.faqs.map((faq) => ({ ...faq })),
});

function submit() {
    form.put('/admin/settings');
}
</script>

<template>
    <Head title="تنظیمات فروشگاه" />

    <div class="mx-auto max-w-4xl space-y-6 p-6" dir="rtl">
        <div>
            <h1 class="text-2xl font-bold">تنظیمات فروشگاه</h1>
            <p class="mt-1 text-sm text-dh-muted">
                اطلاعات عمومی و تنظیمات پایه فروشگاه
            </p>
        </div>

        <form
            class="space-y-6 rounded-xl border bg-white p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <section class="space-y-4">
                <p class="text-sm">
                    تمام مبالغ بر حسب ریال ذخیره و نمایش داده می‌شوند.
                </p>
                <label class="block"
                    >شرایط ارسال<textarea
                        v-model="form.shipping_policy"
                        rows="4"
                        class="block w-full rounded border p-3"
                    />
                </label>
                <label class="block"
                    >شرایط مرجوعی<textarea
                        v-model="form.returns_policy"
                        rows="4"
                        class="block w-full rounded border p-3"
                    />
                </label>
                <label class="block"
                    >حریم خصوصی<textarea
                        v-model="form.privacy_policy"
                        rows="4"
                        class="block w-full rounded border p-3"
                    />
                </label>
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
                            readonly
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
                <h2 class="text-lg font-semibold">پشتیبانی و مجوز</h2>
                <p class="text-sm text-dh-muted">
                    اطلاعات ثبت‌شده در پایین صفحات نمایش داده می‌شوند. فقط عنوان
                    و لینک استعلام مجوز واقعی فروشگاه را وارد کنید.
                </p>
                <label class="block space-y-1"
                    ><span>ساعت پاسخ‌گویی</span
                    ><input
                        v-model="form.support_hours"
                        class="w-full rounded-lg border p-2"
                /></label>
                <label class="block space-y-1"
                    ><span>عنوان مجوز</span
                    ><input
                        v-model="form.license_title"
                        class="w-full rounded-lg border p-2"
                /></label>
                <label class="block space-y-1"
                    ><span>لینک استعلام مجوز (HTTPS)</span
                    ><input
                        v-model="form.license_url"
                        type="url"
                        dir="ltr"
                        class="w-full rounded-lg border p-2"
                /></label>
            </section>
            <section class="space-y-4 border-t pt-6">
                <h2 class="text-lg font-semibold">پرسش‌های متداول</h2>
                <div
                    v-for="(faq, index) in form.faqs"
                    :key="index"
                    class="space-y-3 rounded-xl border p-4"
                >
                    <label class="block"
                        ><span>پرسش</span
                        ><input
                            v-model="faq.question"
                            required
                            maxlength="200"
                            class="w-full rounded-lg border p-2"
                    /></label>
                    <label class="block"
                        ><span>پاسخ</span
                        ><textarea
                            v-model="faq.answer"
                            required
                            maxlength="2000"
                            rows="3"
                            class="w-full rounded-lg border p-2"
                        />
                    </label>
                    <button
                        type="button"
                        class="text-sm text-red-700"
                        @click="form.faqs.splice(index, 1)"
                    >
                        حذف پرسش
                    </button>
                </div>
                <button
                    v-if="form.faqs.length < 20"
                    type="button"
                    class="rounded-lg bg-dh-50 px-4 py-2 text-dh-700"
                    @click="form.faqs.push({ question: '', answer: '' })"
                >
                    افزودن پرسش
                </button>
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
                <div
                    v-if="Object.keys(form.errors).length"
                    role="alert"
                    class="ml-4 text-sm text-red-700"
                >
                    <p v-for="(error, key) in form.errors" :key="key">
                        {{ error }}
                    </p>
                </div>
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
