<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
import { ref } from 'vue';

const step = ref<'phone' | 'code'>('phone');
const form = useForm({
    phone: '',
    code: '',
});

function sendCode(): void {
    router.post(
        '/login/send-otp',
        { phone: form.phone },
        {
            preserveState: true,
            onSuccess: () => {
                step.value = 'code';
            },
        },
    );
}

function login(): void {
    form.post('/login');
}
</script>

<template>
    <Head title="ورود">
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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
import { ref } from 'vue';

const step = ref<'phone' | 'code'>('phone');
const form = useForm({
    phone: '',
    code: '',
});

function sendCode(): void {
    router.post(
        '/login/send-otp',
        { phone: form.phone },
        {
            preserveState: true,
            onSuccess: () => {
                step.value = 'code';
            },
        },
    );
}

function login(): void {
    form.post('/login');
}
</script>

<template>
    <Head title="ورود">
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
                ورود به حساب
            </h1>
            <p class="mt-2 text-sm leading-6 text-dh-muted dark:text-dh-muted">
                برای ورود، کد تأیید به شماره موبایل شما ارسال می‌شود.
            </p>

            <form
                v-if="step === 'phone'"
                class="mt-7 space-y-4"
                @submit.prevent="sendCode"
            >
                <label
                    class="block text-sm font-medium text-dh-ink dark:text-dh-ink"
                >
                    شماره موبایل
                    <input
                        v-model="form.phone"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="tel"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 outline-none focus:border-dh-500 dark:border-dh-200 dark:bg-dh-surface dark:text-dh-ink"
                        placeholder="09121234567"
                        required
                    />
                </label>
                <div v-if="form.errors.phone" class="text-sm text-red-500">
                    {{ form.errors.phone }}
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-dh-700 px-5 py-3 text-sm font-semibold text-white hover:bg-dh-800 disabled:opacity-60"
                >
                    ارسال کد تأیید
                </button>
            </form>

            <form v-else class="mt-7 space-y-4" @submit.prevent="login">
                <label
                    class="block text-sm font-medium text-dh-ink dark:text-dh-ink"
                >
                    کد تأیید
                    <input
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 text-center text-lg tracking-[0.4em] outline-none focus:border-dh-500 dark:border-dh-200 dark:bg-dh-surface dark:text-dh-ink"
                        placeholder="۱۲۳۴۵۶"
                        required
                    />
                </label>
                <div v-if="form.errors.code" class="text-sm text-red-500">
                    {{ form.errors.code }}
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-dh-700 px-5 py-3 text-sm font-semibold text-white hover:bg-dh-800 disabled:opacity-60"
                >
                    ورود
                </button>
                <button
                    type="button"
                    class="w-full rounded-xl border border-dh-200 px-5 py-3 text-sm font-semibold text-dh-ink hover:bg-dh-surface dark:border-dh-200 dark:text-dh-ink dark:hover:bg-dh-50"
                    @click="step = 'phone'"
                >
                    تغییر شماره موبایل
                </button>
            </form>

            <p class="mt-7 text-center text-sm text-dh-muted">
                حساب ندارید؟
                <Link
                    href="/register"
                    class="font-semibold text-dh-700 hover:underline"
                    >ثبت‌نام کنید</Link
                >
            </p>
        </section>
    </main>
</template>
