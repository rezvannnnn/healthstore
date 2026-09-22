<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';

interface User {
    id: number;
    name: string;
    email?: string | null;
    phone: string;
    phone_verified_at?: string | null;
}

interface PagePropsWithFlash {
    [key: string]: unknown;
    flash?: {
        success?: string;
        error?: string;
        info?: string;
        status?: string;
    };
}

const props = defineProps<{ user: User }>();
const page = usePage<PagePropsWithFlash>();
const form = useForm({
    name: props.user.name,
    email: props.user.email ?? '',
});

function submit(): void {
    form.put('/account/profile', { preserveScroll: true });
}
</script>

<template>
    <Head title="پروفایل من" />
    <main dir="rtl" class="min-h-screen bg-dh-50 px-4 py-6 pb-24 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-dh-100">
                <Link href="/" class="flex shrink-0 items-center" aria-label="داروخونه">
                    <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Link href="/account/orders" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">سفارش‌ها</Link>
                    <Link href="/account/addresses" class="rounded-xl px-3 py-2 font-semibold text-dh-700 hover:bg-dh-50">آدرس‌ها</Link>
                    <Link href="/cart" class="rounded-xl bg-dh-700 px-4 py-2 font-semibold text-white hover:bg-dh-800">سبد خرید</Link>
                </nav>
            </header>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 sm:p-7">
                    <div class="mb-7">
                        <p class="mb-2 text-sm font-bold text-dh-green-600">حساب کاربری</p>
                        <h1 class="text-2xl font-black text-dh-900">پروفایل من</h1>
                        <p class="mt-2 text-sm text-dh-muted">اطلاعات حساب خود را مدیریت و به‌روز کنید.</p>
                    </div>

                    <div v-if="page.props.flash?.success || page.props.flash?.status" class="mb-5 rounded-2xl border border-dh-green-100 bg-dh-green-50 p-4 text-sm text-dh-green-700" role="status">
                        {{ page.props.flash.success || page.props.flash.status }}
                    </div>
                    <div v-if="page.props.flash?.error" class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                        {{ page.props.flash.error }}
                    </div>
                    <div v-if="page.props.flash?.info" class="mb-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700" role="status">
                        {{ page.props.flash.info }}
                    </div>

                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-dh-800">نام</label>
                            <input v-model="form.name" required class="w-full rounded-2xl border-dh-100 bg-dh-50/40 px-4 py-3.5 text-dh-900 outline-none transition focus:border-dh-400 focus:ring-4 focus:ring-dh-100" />
                            <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-dh-800">ایمیل</label>
                            <input v-model="form.email" type="email" class="w-full rounded-2xl border-dh-100 bg-dh-50/40 px-4 py-3.5 text-dh-900 outline-none transition focus:border-dh-400 focus:ring-4 focus:ring-dh-100" />
                            <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-500">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-dh-800">موبایل</label>
                            <input :value="user.phone" disabled class="w-full rounded-2xl border-dh-100 bg-dh-100/60 px-4 py-3.5 text-dh-muted" />
                            <p class="mt-1.5 text-xs text-dh-muted">
                                {{ user.phone_verified_at ? 'شماره موبایل تأیید شده است.' : 'شماره موبایل هنوز تأیید نشده است.' }}
                            </p>
                        </div>
                        <div v-if="form.hasErrors" class="rounded-2xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
                            لطفاً خطاهای فرم را بررسی و اصلاح کنید.
                        </div>
                        <button type="submit" :disabled="form.processing" class="rounded-2xl bg-dh-700 px-6 py-3.5 font-bold text-white shadow-sm transition hover:bg-dh-800 disabled:opacity-60">
                            {{ form.processing ? 'در حال ذخیره...' : 'ذخیره اطلاعات' }}
                        </button>
                    </form>
                </section>

                <aside class="h-fit rounded-3xl bg-dh-800 p-6 text-white shadow-sm">
                    <p class="text-sm font-bold text-dh-green-100">مدیریت حساب</p>
                    <h2 class="mt-2 text-xl font-black">همه‌چیز یک‌جا</h2>
                    <p class="mt-3 text-sm leading-7 text-dh-100">از این بخش می‌توانید اطلاعات شخصی، سفارش‌ها و آدرس‌های ارسال را مدیریت کنید.</p>
                    <div class="mt-6 space-y-2">
                        <Link href="/account/orders" class="block rounded-2xl bg-white/10 px-4 py-3 text-sm font-bold hover:bg-white/15">مشاهده سفارش‌ها</Link>
                        <Link href="/account/addresses" class="block rounded-2xl bg-white/10 px-4 py-3 text-sm font-bold hover:bg-white/15">مدیریت آدرس‌ها</Link>
                    </div>
                </aside>
            </div>
        </div>
    
            <nav class="fixed inset-x-4 bottom-4 z-40 mx-auto flex max-w-md items-center justify-around rounded-2xl border border-dh-100 bg-white/95 p-2 shadow-lg backdrop-blur sm:hidden">
                <Link href="/products" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700"><span class="text-base">⌂</span>فروشگاه</Link>
                <Link href="/account/orders" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700"><span class="text-base">◷</span>سفارش‌ها</Link>
                <Link href="/account/addresses" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-muted hover:bg-dh-50 hover:text-dh-700"><span class="text-base">⌖</span>آدرس‌ها</Link>
                <Link href="/cart" class="flex flex-col items-center gap-1 rounded-xl px-3 py-2 text-[11px] font-bold text-dh-700 hover:bg-dh-50"><span class="text-base">▢</span>سبد</Link>
            </nav></main>
</template>
