<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Address {
    id: number;
    title?: string | null;
    recipient_name: string;
    phone: string;
    province?: string | null;
    city?: string | null;
    address: string;
    postal_code?: string | null;
    is_default: boolean;
}

const props = defineProps<{ addresses: Address[] }>();

const editingId = ref<number | null>(null);
const form = useForm({
    title: '',
    recipient_name: '',
    phone: '',
    province: '',
    city: '',
    address: '',
    postal_code: '',
    is_default: false,
});

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.is_default = props.addresses.length === 0;
}

function editAddress(address: Address): void {
    editingId.value = address.id;
    form.title = address.title ?? '';
    form.recipient_name = address.recipient_name;
    form.phone = address.phone;
    form.province = address.province ?? '';
    form.city = address.city ?? '';
    form.address = address.address;
    form.postal_code = address.postal_code ?? '';
    form.is_default = address.is_default;
    form.clearErrors();
}

function submit(): void {
    if (editingId.value) {
        form.put(`/account/addresses/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
        return;
    }

    form.post('/account/addresses', {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
}

function removeAddress(id: number): void {
    if (window.confirm('آیا از حذف این آدرس مطمئن هستید؟')) {
        router.delete(`/account/addresses/${id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="آدرس‌های من" />

    <main dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-5xl space-y-8">
            <header class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        آدرس‌های من
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        آدرس‌های ارسال سفارش را مدیریت کنید.
                    </p>
                </div>
                <Link
                    href="/account/orders"
                    class="text-sm font-medium text-indigo-600 hover:underline"
                >
                    سفارش‌ها
                </Link>
            </header>

            <div
                v-if="form.hasErrors"
                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
            >
                لطفاً خطاهای فرم را بررسی و اصلاح کنید.
            </div>

            <section
                class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
            >
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ editingId ? 'ویرایش آدرس' : 'افزودن آدرس جدید' }}
                    </h2>
                    <button
                        v-if="editingId"
                        type="button"
                        class="text-sm text-gray-500 hover:underline"
                        @click="resetForm"
                    >
                        لغو ویرایش
                    </button>
                </div>

                <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        عنوان
                        <input
                            v-model="form.title"
                            class="mt-1 w-full rounded-xl border-gray-300"
                            placeholder="مثلاً خانه"
                        />
                        <span v-if="form.errors.title" class="mt-1 block text-xs text-red-500">
                            {{ form.errors.title }}
                        </span>
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        نام گیرنده
                        <input
                            v-model="form.recipient_name"
                            required
                            class="mt-1 w-full rounded-xl border-gray-300"
                        />
                        <span v-if="form.errors.recipient_name" class="mt-1 block text-xs text-red-500">
                            {{ form.errors.recipient_name }}
                        </span>
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        شماره موبایل
                        <input
                            v-model="form.phone"
                            required
                            class="mt-1 w-full rounded-xl border-gray-300"
                        />
                        <span v-if="form.errors.phone" class="mt-1 block text-xs text-red-500">
                            {{ form.errors.phone }}
                        </span>
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        استان
                        <input v-model="form.province" class="mt-1 w-full rounded-xl border-gray-300" />
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        شهر
                        <input v-model="form.city" class="mt-1 w-full rounded-xl border-gray-300" />
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        کد پستی
                        <input v-model="form.postal_code" class="mt-1 w-full rounded-xl border-gray-300" />
                        <span v-if="form.errors.postal_code" class="mt-1 block text-xs text-red-500">
                            {{ form.errors.postal_code }}
                        </span>
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 md:col-span-2">
                        نشانی کامل
                        <textarea
                            v-model="form.address"
                            required
                            class="mt-1 min-h-28 w-full rounded-xl border-gray-300"
                        />
                        <span v-if="form.errors.address" class="mt-1 block text-xs text-red-500">
                            {{ form.errors.address }}
                        </span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 md:col-span-2 dark:text-gray-300">
                        <input v-model="form.is_default" type="checkbox" class="rounded" />
                        آدرس پیش‌فرض باشد
                    </label>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-indigo-600 px-5 py-3 font-medium text-white hover:bg-indigo-700 disabled:opacity-60 md:col-span-2"
                    >
                        {{ form.processing ? 'در حال ذخیره...' : editingId ? 'ذخیره تغییرات' : 'ثبت آدرس' }}
                    </button>
                </form>
            </section>

            <section class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">آدرس‌های ثبت‌شده</h2>
                <div v-if="addresses.length" class="grid gap-4">
                    <article
                        v-for="address in addresses"
                        :key="address.id"
                        class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ address.title || 'آدرس' }}
                                    </h3>
                                    <span
                                        v-if="address.is_default"
                                        class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700"
                                    >
                                        پیش‌فرض
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ address.recipient_name }} · {{ address.phone }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    {{ address.province }} {{ address.city }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                    {{ address.address }}
                                </p>
                                <p v-if="address.postal_code" class="mt-1 text-xs text-gray-500">
                                    کد پستی: {{ address.postal_code }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg border px-3 py-2 text-sm"
                                    @click="editAddress(address)"
                                >
                                    ویرایش
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600"
                                    @click="removeAddress(address.id)"
                                >
                                    حذف
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-500 dark:border-gray-700"
                >
                    هنوز آدرسی ثبت نکرده‌اید.
                </div>
            </section>
        </div>
    </main>
</template>
