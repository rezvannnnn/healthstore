<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
defineProps<{
    enabled: boolean;
    secret: string | null;
    recoveryCodes: string[];
}>();
const form = useForm({ password: '', code: '' });
function submit(action: string) {
    form.post('/admin/security/' + action, {
        onFinish: () => (form.password = ''),
    });
}
</script>
<template>
    <Head title="امنیت حساب مدیریت" />
    <main dir="rtl" class="mx-auto max-w-3xl space-y-5 p-6">
        <Link href="/admin">بازگشت به مدیریت</Link>
        <h1 class="text-2xl font-bold">ورود دومرحله‌ای مدیریت</h1>
        <p>
            وضعیت: {{ enabled ? 'فعال' : 'غیرفعال' }}. از برنامه‌های سازگار با
            TOTP استفاده کنید.
        </p>
        <p v-if="secret">
            کلید را به صورت دستی در برنامه احراز هویت وارد کنید:
            <code dir="ltr">{{ secret }}</code>
        </p>
        <section v-if="recoveryCodes.length">
            <p>
                این کدها فقط یک بار نمایش داده می‌شوند و هر کد یک بار قابل
                استفاده است.
            </p>
            <ul>
                <li v-for="code in recoveryCodes" :key="code" dir="ltr">
                    {{ code }}
                </li>
            </ul>
        </section>
        <form
            class="space-y-4"
            @submit.prevent="
                submit(enabled ? 'disable' : secret ? 'enable' : 'prepare')
            "
        >
            <label class="block"
                >رمز فعلی<input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="block rounded border p-3"
            /></label>
            <label v-if="enabled || secret" class="block"
                >کد احراز هویت یا بازیابی<input
                    v-model="form.code"
                    autocomplete="one-time-code"
                    required
                    class="block rounded border p-3"
            /></label>
            <button
                :disabled="form.processing"
                class="rounded bg-dh-700 px-5 py-3 text-white"
            >
                {{
                    enabled
                        ? 'غیرفعال‌سازی'
                        : secret
                          ? 'تأیید و فعال‌سازی'
                          : 'آماده‌سازی'
                }}
            </button>
        </form>
    </main>
</template>
