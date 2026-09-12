<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const step = ref<'phone' | 'code'>('phone');
const form = useForm({
    phone: '',
    code: '',
});

function sendCode(): void {
    router.post('/login/send-otp', { phone: form.phone }, {
        preserveState: true,
        onSuccess: () => {
            step.value = 'code';
        },
    });
}

function login(): void {
    form.post('/login');
}
</script>

<template>
    <Head title="ورود" />

    <main dir="rtl" class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-10 dark:bg-gray-950">
        <section class="w-full max-w-md rounded-3xl bg-white p-7 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <a href="/" class="text-sm font-medium text-indigo-600">فروشگاه سلامت</a>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">ورود به حساب</h1>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">برای ورود، کد تأیید به شماره موبایل شما ارسال می‌شود.</p>

            <form v-if="step === 'phone'" class="mt-7 space-y-4" @submit.prevent="sendCode">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    شماره موبایل
                    <input v-model="form.phone" type="tel" inputmode="numeric" autocomplete="tel" class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white" placeholder="09121234567" required />
                </label>
                <div v-if="form.errors.phone" class="text-sm text-red-500">{{ form.errors.phone }}</div>
                <button type="submit" :disabled="form.processing" class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">ارسال کد تأیید</button>
            </form>

            <form v-else class="mt-7 space-y-4" @submit.prevent="login">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    کد تأیید
                    <input v-model="form.code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-center text-lg tracking-[0.4em] outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white" placeholder="۱۲۳۴۵۶" required />
                </label>
                <div v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</div>
                <button type="submit" :disabled="form.processing" class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">ورود</button>
                <button type="button" class="w-full rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="step = 'phone'">تغییر شماره موبایل</button>
            </form>

            <p class="mt-7 text-center text-sm text-gray-500">
                حساب ندارید؟
                <a href="/register" class="font-semibold text-indigo-600 hover:underline">ثبت‌نام کنید</a>
            </p>
        </section>
    </main>
</template>
