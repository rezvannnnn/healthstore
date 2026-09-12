<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const step = ref<'verify' | 'details'>('verify');
const phoneForm = useForm({
    phone: '',
    code: '',
});
const detailsForm = useForm({
    name: '',
    phone: '',
    email: '',
});

function sendCode(): void {
    phoneForm.post('/register/send-otp', {
        preserveState: true,
        onSuccess: () => {
            step.value = 'verify';
        },
    });
}

function verifyCode(): void {
    phoneForm.post('/register/verify-otp', {
        preserveState: true,
        onSuccess: () => {
            detailsForm.phone = phoneForm.phone;
            step.value = 'details';
        },
    });
}

function register(): void {
    detailsForm.post('/register');
}
</script>

<template>
    <Head title="ثبت‌نام" />

    <main
        dir="rtl"
        class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-10 dark:bg-gray-950"
    >
        <section
            class="w-full max-w-md rounded-3xl bg-white p-7 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
        >
            <a href="/" class="text-sm font-medium text-indigo-600"
                >فروشگاه سلامت</a
            >
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">
                ساخت حساب کاربری
            </h1>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                با شماره موبایل خود ثبت‌نام کنید و بعد اطلاعات حساب را تکمیل
                کنید.
            </p>

            <form
                v-if="step === 'verify'"
                class="mt-7 space-y-4"
                @submit.prevent="phoneForm.code ? verifyCode() : sendCode()"
            >
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    شماره موبایل
                    <input
                        v-model="phoneForm.phone"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="tel"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        placeholder="09121234567"
                        required
                    />
                </label>
                <div v-if="phoneForm.errors.phone" class="text-sm text-red-500">
                    {{ phoneForm.errors.phone }}
                </div>

                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    کد تأیید
                    <input
                        v-model="phoneForm.code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-center text-lg tracking-[0.4em] outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        placeholder="پس از دریافت کد وارد کنید"
                    />
                </label>
                <div v-if="phoneForm.errors.code" class="text-sm text-red-500">
                    {{ phoneForm.errors.code }}
                </div>

                <button
                    type="submit"
                    :disabled="phoneForm.processing"
                    class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                >
                    {{
                        phoneForm.code ? 'تأیید شماره موبایل' : 'ارسال کد تأیید'
                    }}
                </button>
            </form>

            <form v-else class="mt-7 space-y-4" @submit.prevent="register">
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    نام و نام خانوادگی
                    <input
                        v-model="detailsForm.name"
                        type="text"
                        autocomplete="name"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        required
                    />
                </label>
                <div
                    v-if="detailsForm.errors.name"
                    class="text-sm text-red-500"
                >
                    {{ detailsForm.errors.name }}
                </div>

                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    شماره موبایل
                    <input
                        v-model="detailsForm.phone"
                        type="tel"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-gray-100 px-4 py-3 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        readonly
                    />
                </label>
                <div
                    v-if="detailsForm.errors.phone"
                    class="text-sm text-red-500"
                >
                    {{ detailsForm.errors.phone }}
                </div>

                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    ایمیل <span class="text-xs text-gray-400">(اختیاری)</span>
                    <input
                        v-model="detailsForm.email"
                        type="email"
                        autocomplete="email"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    />
                </label>
                <div
                    v-if="detailsForm.errors.email"
                    class="text-sm text-red-500"
                >
                    {{ detailsForm.errors.email }}
                </div>

                <button
                    type="submit"
                    :disabled="detailsForm.processing"
                    class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                >
                    ایجاد حساب
                </button>
            </form>

            <p class="mt-7 text-center text-sm text-gray-500">
                قبلاً ثبت‌نام کرده‌اید؟
                <a
                    href="/login"
                    class="font-semibold text-indigo-600 hover:underline"
                    >وارد شوید</a
                >
            </p>
        </section>
    </main>
</template>
