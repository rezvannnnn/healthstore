<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
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
    <Head title="ثبت‌نام">
        <meta name="robots" content="noindex, nofollow, noarchive" />
    </Head>

    <main
        dir="rtl"
        class="flex min-h-screen items-center justify-center bg-dh-surface px-4 py-10 dark:bg-dh-surface"
    >
        <section
            class="relative w-full max-w-md rounded-3xl bg-white p-7 shadow-sm ring-1 ring-dh-100 dark:bg-white dark:ring-dh-100"
        >
            <Link href="/" class="flex items-center" aria-label="داروخونه">
                <BrandLogo imageClass="h-28 w-28" />
            </Link>t setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
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
    <Head title="ثبت‌نام">
        <meta name="robots" content="noindex, nofollow, noarchive" />
    </Head>

    <main
        dir="rtl"
        class="flex min-h-screen items-center justify-center bg-dh-surface px-4 py-10 dark:bg-dh-surface"
    >
        <section
            class="relative w-full max-w-md rounded-3xl bg-white p-7 shadow-sm ring-1 ring-dh-100 dark:bg-white dark:ring-dh-100"
        >
            <Link href="/" class="text-sm font-medium text-dh-700"
                ><span class="font-black text-dh-800">داروخونه</span></Link
            >
            <h1 class="mt-4 text-2xl font-bold text-dh-ink dark:text-dh-ink">
                ساخت حساب کاربری
            </h1>
            <p class="mt-2 text-sm leading-6 text-dh-muted dark:text-dh-muted">
                با شماره موبایل خود ثبت‌نام کنید و بعد اطلاعات حساب را تکمیل
                کنید.
            </p>

            <form
                v-if="step === 'verify'"
                class="mt-7 space-y-4"
                @submit.prevent="phoneForm.code ? verifyCode() : sendCode()"
            >
                <label
                    class="block text-sm font-medium text-dh-ink dark:text-dh-ink"
                >
                    شماره موبایل
                    <input
                        v-model="phoneForm.phone"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="tel"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 outline-none focus:border-dh-500 dark:border-dh-200 dark:bg-dh-surface dark:text-dh-ink"
                        placeholder="09121234567"
                        required
                    />
                </label>
                <div v-if="phoneForm.errors.phone" class="text-sm text-red-500">
                    {{ phoneForm.errors.phone }}
                </div>

                <label
                    class="block text-sm font-medium text-dh-ink dark:text-dh-ink"
                >
                    کد تأیید
                    <input
                        v-model="phoneForm.code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 text-center text-lg tracking-[0.4em] outline-none focus:border-dh-500 dark:border-dh-200 dark:bg-dh-surface dark:text-dh-ink"
                        placeholder="پس از دریافت کد وارد کنید"
                    />
                </label>
                <div v-if="phoneForm.errors.code" class="text-sm text-red-500">
                    {{ phoneForm.errors.code }}
                </div>

                <button
                    type="submit"
                    :disabled="phoneForm.processing"
                    class="w-full rounded-xl bg-dh-700 px-5 py-3 text-sm font-semibold text-white hover:bg-dh-800 disabled:opacity-60"
                >
                    {{
                        phoneForm.code ? 'تأیید شماره موبایل' : 'ارسال کد تأیید'
                    }}
                </button>
            </form>

            <form v-else class="mt-7 space-y-4" @submit.prevent="register">
                <label
                    class="block text-sm font-medium text-dh-ink dark:text-dh-ink"
                >
                    نام و نام خانوادگی
                    <input
                        v-model="detailsForm.name"
                        type="text"
                        autocomplete="name"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 outline-none focus:border-dh-500 dark:border-dh-200 dark:bg-dh-surface dark:text-dh-ink"
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
                    class="block text-sm font-medium text-dh-ink dark:text-dh-ink"
                >
                    شماره موبایل
                    <input
                        v-model="detailsForm.phone"
                        type="tel"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-dh-50 px-4 py-3 text-dh-muted dark:border-dh-200 dark:bg-dh-50 dark:text-dh-muted"
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
                    class="block text-sm font-medium text-dh-ink dark:text-dh-ink"
                >
                    ایمیل <span class="text-xs text-dh-muted">(اختیاری)</span>
                    <input
                        v-model="detailsForm.email"
                        type="email"
                        autocomplete="email"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 outline-none focus:border-dh-500 dark:border-dh-200 dark:bg-dh-surface dark:text-dh-ink"
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
                    class="w-full rounded-xl bg-dh-700 px-5 py-3 text-sm font-semibold text-white hover:bg-dh-800 disabled:opacity-60"
                >
                    ایجاد حساب
                </button>
            </form>

            <p class="mt-7 text-center text-sm text-dh-muted">
                قبلاً ثبت‌نام کرده‌اید؟
                <Link
                    href="/login"
                    class="font-semibold text-dh-700 hover:underline"
                    >وارد شوید</Link
                >
            </p>
        </section>
    </main>
</template>
