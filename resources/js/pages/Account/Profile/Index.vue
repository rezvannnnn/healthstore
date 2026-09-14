<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

interface User {
    id: number;
    name: string;
    email?: string | null;
    phone: string;
    phone_verified_at?: string | null;
}

const props = defineProps<{
    user: User;
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email ?? '',
});

function submit(): void {
    form.put('/account/profile', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="پروفایل من" />

    <main dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-2xl space-y-6">
            <header>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    پروفایل من
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    اطلاعات حساب کاربری خود را مدیریت کنید.
                </p>
            </header>

            <section
                class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
            >
                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            نام
                        </label>
                        <input
                            v-model="form.name"
                            required
                            class="w-full rounded-xl border-gray-300"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            ایمیل
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-xl border-gray-300"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            موبایل
                        </label>
                        <input
                            :value="user.phone"
                            disabled
                            class="w-full rounded-xl border-gray-200 bg-gray-100 text-gray-500"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            {{
                                user.phone_verified_at
                                    ? 'شماره موبایل تأیید شده است.'
                                    : 'شماره موبایل هنوز تأیید نشده است.'
                            }}
                        </p>
                    </div>
                    <div
                        v-if="form.hasErrors"
                        class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                        role="alert"
                    >
                        لطفاً خطاهای فرم را بررسی و اصلاح کنید.
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-indigo-600 px-5 py-3 font-medium text-white hover:bg-indigo-700 disabled:opacity-60"
                    >
                        {{
                            form.processing
                                ? 'در حال ذخیره...'
                                : 'ذخیره اطلاعات'
                        }}
                    </button>
                </form>
            </section>

            <nav class="flex flex-wrap gap-4 text-sm">
                <Link
                    href="/account/orders"
                    class="text-indigo-600 hover:underline"
                    >سفارش‌های من</Link
                >
                <Link
                    href="/account/addresses"
                    class="text-indigo-600 hover:underline"
                    >آدرس‌های من</Link
                >
                <Link href="/products" class="text-indigo-600 hover:underline"
                    >بازگشت به فروشگاه</Link
                >
            </nav>
        </div>
    </main>
</template>
