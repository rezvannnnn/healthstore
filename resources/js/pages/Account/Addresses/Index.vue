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

    <main dir="rtl" class="min-h-screen bg-dh-50 px-4 py-6 pb-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-dh-100">
                <Link href="/products" class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-2xl bg-dh-700 text-white">
                        <svg viewBox="0 0 48 48" class="size-7" fill="none" aria-hidden="true">
                            <path d="M11 16h26l-3 17H14l-3-17Z" stroke="currentColor" stroke-width="3" />
                            <path d="M17 16c0-5 3-8 7-8s7 3 7 8" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            <path d="m22 24 3 3 7-7" stroke="#77c8a0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span><strong class="block text-lg font-black text-dh-800">داروخونه</strong><span class="text-xs text-dh-muted">دارو و محصولات بهداشتی</span></span>
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Link href="/account/profile" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">پروفایل</Link>
                    <Link href="/account/orders" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">سفارش‌ها</Link>
                    <Link href="/cart" class="rounded-xl bg-dh-700 px-4 py-2 font-semibold text-white hover:bg-dh-800">سبد خرید</Link>
                </nav>
            </header>
            <header class="mb-6 flex items-center justify-between gap-4 rounded-3xl bg-dh-800 p-6 text-white shadow-sm">
                <div>
                    <h1
                        class="text-2xl font-black text-white"
                    >
                        آدرس‌های من
                    </h1>
                    <p class="mt-1 text-sm text-dh-100">
                        آدرس‌های ارسال سفارش را مدیریت کنید.
                    </p>
                </div>
                <Link
                    href="/account/orders"
                    class="rounded-xl bg-white/10 px-4 py-2 text-sm font-bold text-white hover:bg-white/15"
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
                class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
            >
                <div class="mb-5 flex items-center justify-between">
                    <h2
                        class="text-lg font-semibold text-dh-900"
                    >
                        {{ editingId ? 'ویرایش آدرس' : 'افزودن آدرس جدید' }}
                    </h2>
                    <button
                        v-if="editingId"
                        type="button"
                        class="text-sm text-dh-muted hover:underline"
                        @click="resetForm"
                    >
                        لغو ویرایش
                    </button>
                </div>

                <form
                    class="grid gap-4 md:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <label
                        class="text-sm font-medium text-dh-800"
                    >
                        عنوان
                        <input
                            v-model="form.title"
                            class="mt-1 w-full rounded-xl border-dh-100"
                            placeholder="مثلاً خانه"
                        />
                        <span
                            v-if="form.errors.title"
                            class="mt-1 block text-xs text-red-500"
                        >
                            {{ form.errors.title }}
                        </span>
                    </label>
                    <label
                        class="text-sm font-medium text-dh-800"
                    >
                        نام گیرنده
                        <input
                            v-model="form.recipient_name"
                            required
                            class="mt-1 w-full rounded-xl border-dh-100"
                        />
                        <span
                            v-if="form.errors.recipient_name"
                            class="mt-1 block text-xs text-red-500"
                        >
                            {{ form.errors.recipient_name }}
                        </span>
                    </label>
                    <label
                        class="text-sm font-medium text-dh-800"
                    >
                        شماره موبایل
                        <input
                            v-model="form.phone"
                            required
                            class="mt-1 w-full rounded-xl border-dh-100"
                        />
                        <span
                            v-if="form.errors.phone"
                            class="mt-1 block text-xs text-red-500"
                        >
                            {{ form.errors.phone }}
                        </span>
                    </label>
                    <label
                        class="text-sm font-medium text-dh-800"
                    >
                        استان
                        <input
                            v-model="form.province"
                            class="mt-1 w-full rounded-xl border-dh-100"
                        />
                    </label>
                    <label
                        class="text-sm font-medium text-dh-800"
                    >
                        شهر
                        <input
                            v-model="form.city"
                            class="mt-1 w-full rounded-xl border-dh-100"
                        />
                    </label>
                    <label
                        class="text-sm font-medium text-dh-800"
                    >
                        کد پستی
                        <input
                            v-model="form.postal_code"
                            class="mt-1 w-full rounded-xl border-dh-100"
                        />
                        <span
                            v-if="form.errors.postal_code"
                            class="mt-1 block text-xs text-red-500"
                        >
                            {{ form.errors.postal_code }}
                        </span>
                    </label>
                    <label
                        class="text-sm font-medium text-gray-700 md:col-span-2 dark:text-gray-300"
                    >
                        نشانی کامل
                        <textarea
                            v-model="form.address"
                            required
                            class="mt-1 min-h-28 w-full rounded-xl border-dh-100"
                        />
                        <span
                            v-if="form.errors.address"
                            class="mt-1 block text-xs text-red-500"
                        >
                            {{ form.errors.address }}
                        </span>
                    </label>
                    <label
                        class="flex items-center gap-2 text-sm text-gray-700 md:col-span-2 dark:text-gray-300"
                    >
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            class="rounded"
                        />
                        آدرس پیش‌فرض باشد
                    </label>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-dh-700 px-5 py-3 font-bold text-white hover:bg-dh-800 disabled:opacity-60 md:col-span-2"
                    >
                        {{
                            form.processing
                                ? 'در حال ذخیره...'
                                : editingId
                                  ? 'ذخیره تغییرات'
                                  : 'ثبت آدرس'
                        }}
                    </button>
                </form>
            </section>

            <section class="space-y-4">
                <h2 class="text-lg font-semibold text-dh-900">
                    آدرس‌های ثبت‌شده
                </h2>
                <div v-if="addresses.length" class="grid gap-4">
                    <article
                        v-for="address in addresses"
                        :key="address.id"
                        class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="font-semibold text-dh-900"
                                    >
                                        {{ address.title || 'آدرس' }}
                                    </h3>
                                    <span
                                        v-if="address.is_default"
                                        class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700"
                                    >
                                        پیش‌فرض
                                    </span>
                                </div>
                                <p
                                    class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ address.recipient_name }} ·
                                    {{ address.phone }}
                                </p>
                                <p
                                    class="mt-1 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ address.province }} {{ address.city }}
                                </p>
                                <p
                                    class="mt-2 text-sm leading-6 text-dh-800"
                                >
                                    {{ address.address }}
                                </p>
                                <p
                                    v-if="address.postal_code"
                                    class="mt-1 text-xs text-dh-muted"
                                >
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
                    class="rounded-2xl border border-dashed border-dh-100 p-8 text-center text-dh-muted dark:border-gray-700"
                >
                    هنوز آدرسی ثبت نکرده‌اید.
                </div>
            </section>
        </div>
    
            <nav class="fixed inset-x-4 bottom-4 z-40 mx-auto flex max-w-md items-center justify-around rounded-2xl border border-dh-100 bg-white/95 p-2 shadow-lg backdrop-blur sm:hidden">
                <Link href="/products" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700"><span class="text-base">⌂</span>فروشگاه</Link>
                <Link href="/account/orders" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700"><span class="text-base">◷</span>سفارش‌ها</Link>
                <Link href="/account/addresses" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700"><span class="text-base">⌖</span>آدرس‌ها</Link>
                <Link href="/cart" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-700 hover:bg-dh-50"><span class="text-base">▢</span>سبد</Link>
            </nav></main>
</template>
