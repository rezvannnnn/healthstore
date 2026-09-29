<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post('/admin/login');
};
</script>

<template>
    <Head title="ورود مدیریت" />

    <main dir="rtl" class="flex min-h-screen items-center justify-center bg-dh-50 px-4 py-10">
        <section class="w-full max-w-md rounded-3xl bg-white p-8 shadow-sm ring-1 ring-dh-100">
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-dh-700 text-xl font-black text-white">
                    د
                </div>
                <p class="mt-4 text-sm font-semibold text-dh-700">داروخونه</p>
                <h1 class="mt-1 text-2xl font-bold text-dh-900">ورود به پنل مدیریت</h1>
                <p class="mt-2 text-sm leading-6 text-dh-muted">
                    برای دسترسی به مدیریت، نام کاربری و رمز عبور خود را وارد کنید.
                </p>
            </div>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <label class="block text-sm font-medium text-dh-900">
                    نام کاربری
                    <input
                        v-model="form.username"
                        type="text"
                        autocomplete="username"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 outline-none focus:border-dh-500"
                        placeholder="نام کاربری"
                        required
                    />
                </label>

                <div v-if="form.errors.username" class="text-sm text-red-500">
                    {{ form.errors.username }}
                </div>

                <label class="block text-sm font-medium text-dh-900">
                    رمز عبور
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        class="mt-2 w-full rounded-xl border border-dh-200 bg-white px-4 py-3 outline-none focus:border-dh-500"
                        placeholder="رمز عبور"
                        required
                    />
                </label>

                <div v-if="form.errors.password" class="text-sm text-red-500">
                    {{ form.errors.password }}
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-dh-700 px-5 py-3 text-sm font-semibold text-white hover:bg-dh-800 disabled:opacity-60"
                >
                    ورود به پنل
                </button>
            </form>
        </section>
    </main>
</template>
