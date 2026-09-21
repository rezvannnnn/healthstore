<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface User {
    name?: string | null;
}

interface PageProps {
    auth?: {
        user?: User | null;
    };
}

const page = usePage<PageProps>();
const search = ref('');

const user = computed(() => page.props.auth?.user ?? null);

const navigation = [
    { label: 'خانه', href: '/' },
    { label: 'محصولات', href: '/products' },
    { label: 'دسته‌بندی‌ها', href: '/categories' },
    { label: 'برندها', href: '/brands' },
    { label: 'مجله سلامت', href: '/blog' },
];

function submitSearch(): void {
    const value = search.value.trim();

    window.location.href = value
        ? `/products?search=${encodeURIComponent(value)}`
        : '/products';
}
</script>

<template>
    <div
        dir="rtl"
        class="min-h-screen bg-[#f6fafb] text-slate-900 antialiased"
    >
        <div class="border-b border-[#dce9ec] bg-white">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 text-xs font-medium text-slate-600 sm:px-6 lg:px-8"
            >
                <span>خرید آسان محصولات دارویی و بهداشتی</span>
                <span class="hidden sm:inline"
                    >اطلاعات محصول را قبل از خرید ببینید</span
                >
            </div>
        </div>

        <header
            class="sticky top-0 z-40 border-b border-[#dce9ec]/90 bg-white/95 shadow-[0_4px_20px_rgba(4,84,123,0.06)] backdrop-blur"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 py-4">
                    <Link
                        href="/"
                        class="flex shrink-0 items-center gap-3 rounded-2xl outline-none focus-visible:ring-2 focus-visible:ring-[#04547b] focus-visible:ring-offset-2"
                        aria-label="داروخونه"
                    >
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#04547b] via-[#087f9d] to-[#21a7a5] text-white shadow-[0_8px_22px_rgba(4,84,123,0.18)]"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 48 48"
                                class="h-7 w-7"
                                fill="none"
                            >
                                <path
                                    d="M10 24.5C10 17.6 15.6 12 22.5 12H34c2.2 0 4 1.8 4 4v1.5c0 1.1-.9 2-2 2h-2.5"
                                    stroke="currentColor"
                                    stroke-width="3.2"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M11 26c0 6.9 5.6 12.5 12.5 12.5H34c2.2 0 4-1.8 4-4V33c0-1.1-.9-2-2-2h-2.5"
                                    stroke="currentColor"
                                    stroke-width="3.2"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M28.5 11.5c1.2-2.2 3.4-3.4 6.3-3.1-1.1 2.7-2.8 4.3-5 5.1"
                                    stroke="#7bc043"
                                    stroke-width="2.8"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M24 20v8M20 24h8"
                                    stroke="currentColor"
                                    stroke-width="3.2"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                        <span class="leading-tight">
                            <span
                                class="block text-[1.7rem] font-black tracking-tight text-[#04547b]"
                            >
                                داروخونه
                            </span>
                            <span
                                class="block text-[0.64rem] font-medium tracking-[0.18em] text-slate-400"
                            >
                                دارو و محصولات بهداشتی
                            </span>
                        </span>
                    </Link>

                    <form
                        class="hidden min-w-0 flex-1 md:block"
                        @submit.prevent="submitSearch"
                    >
                        <label class="sr-only" for="storefront-search"
                            >جستجوی محصول</label
                        >
                        <div
                            class="relative mx-auto max-w-2xl overflow-hidden rounded-2xl border border-[#cfe0e5] bg-[#f7fbfc] transition focus-within:border-[#0c93a4] focus-within:bg-white focus-within:ring-4 focus-within:ring-[#0c93a4]/10"
                        >
                            <svg
                                class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#0a8d9f]"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="6.8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />
                                <path
                                    d="m16 16 4.5 4.5"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <input
                                id="storefront-search"
                                v-model="search"
                                type="search"
                                placeholder="نام محصول، برند یا بارکد را جستجو کنید..."
                                class="w-full bg-transparent py-3.5 pr-12 pl-28 text-sm text-slate-800 outline-none placeholder:text-slate-400"
                            />
                            <button
                                type="submit"
                                class="absolute left-1 top-1/2 -translate-y-1/2 rounded-xl bg-[#04547b] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#034664]"
                            >
                                جستجو
                            </button>
                        </div>
                    </form>

                    <nav class="hidden items-center gap-1 lg:flex">
                        <Link
                            v-if="user"
                            href="/account/profile"
                            class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#eef8f8] hover:text-[#04547b]"
                        >
                            {{ user.name || 'حساب کاربری' }}
                        </Link>
                        <template v-else>
                            <Link
                                href="/login"
                                class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#eef8f8] hover:text-[#04547b]"
                            >
                                ورود
                            </Link>
                            <Link
                                href="/register"
                                class="rounded-xl bg-[#04547b] px-3.5 py-2 text-sm font-bold text-white transition hover:bg-[#034664]"
                            >
                                ثبت‌نام
                            </Link>
                        </template>
                        <Link
                            href="/cart"
                            class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#d8e7eb] bg-white text-[#04547b] transition hover:border-[#b6d7dc] hover:bg-[#f4fbfb]"
                            aria-label="سبد خرید"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                class="h-5 w-5"
                                fill="none"
                            >
                                <path
                                    d="M5 6h2l1.4 9.2a2 2 0 0 0 2 1.7h6.8a2 2 0 0 0 2-1.6L20 9H8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle
                                    cx="10.2"
                                    cy="19.2"
                                    r="1.1"
                                    fill="currentColor"
                                />
                                <circle
                                    cx="17.2"
                                    cy="19.2"
                                    r="1.1"
                                    fill="currentColor"
                                />
                            </svg>
                        </Link>
                    </nav>
                </div>

                <div class="pb-4 md:hidden">
                    <form @submit.prevent="submitSearch">
                        <label class="sr-only" for="storefront-search-mobile"
                            >جستجوی محصول</label
                        >
                        <div
                            class="relative overflow-hidden rounded-2xl border border-[#cfe0e5] bg-[#f7fbfc] focus-within:border-[#0c93a4] focus-within:ring-4 focus-within:ring-[#0c93a4]/10"
                        >
                            <svg
                                class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#0a8d9f]"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="6.8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />
                                <path
                                    d="m16 16 4.5 4.5"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <input
                                id="storefront-search-mobile"
                                v-model="search"
                                type="search"
                                placeholder="جستجوی محصول، برند یا بارکد..."
                                class="w-full bg-transparent py-3.5 pr-12 pl-24 text-sm outline-none placeholder:text-slate-400"
                            />
                            <button
                                type="submit"
                                class="absolute left-1 top-1/2 -translate-y-1/2 rounded-xl bg-[#04547b] px-3.5 py-2 text-xs font-bold text-white"
                            >
                                جستجو
                            </button>
                        </div>
                    </form>
                </div>

                <nav class="hidden border-t border-[#edf3f4] lg:flex">
                    <div class="flex w-full items-center gap-1 py-2">
                        <Link
                            v-for="item in navigation"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-[#eef8f8] hover:text-[#04547b]"
                        >
                            {{ item.label }}
                        </Link>
                        <span class="mr-auto text-xs text-slate-400"
                            >www.darookhooneh.ir</span
                        >
                    </div>
                </nav>
            </div>
        </header>

        <main class="pb-28 lg:pb-0">
            <slot />
        </main>

        <footer class="border-t border-[#dce9ec] bg-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">
                    <div>
                        <Link href="/" class="inline-flex items-center gap-3">
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#04547b] text-white"
                                aria-hidden="true"
                            >
                                <svg
                                    viewBox="0 0 48 48"
                                    class="h-7 w-7"
                                    fill="none"
                                >
                                    <path
                                        d="M10 24.5C10 17.6 15.6 12 22.5 12H34c2.2 0 4 1.8 4 4v1.5c0 1.1-.9 2-2 2h-2.5"
                                        stroke="currentColor"
                                        stroke-width="3.2"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M11 26c0 6.9 5.6 12.5 12.5 12.5H34c2.2 0 4-1.8 4-4V33c0-1.1-.9-2-2-2h-2.5"
                                        stroke="currentColor"
                                        stroke-width="3.2"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M28.5 11.5c1.2-2.2 3.4-3.4 6.3-3.1-1.1 2.7-2.8 4.3-5 5.1"
                                        stroke="#7bc043"
                                        stroke-width="2.8"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M24 20v8M20 24h8"
                                        stroke="currentColor"
                                        stroke-width="3.2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>
                            <span>
                                <span
                                    class="block text-2xl font-black text-[#04547b]"
                                    >داروخونه</span
                                >
                                <span class="text-xs text-slate-400"
                                    >دارو و محصولات بهداشتی</span
                                >
                            </span>
                        </Link>
                        <p
                            class="mt-5 max-w-md text-sm leading-7 text-slate-500"
                        >
                            یک فروشگاه سلامت ساده و قابل اعتماد برای پیدا کردن،
                            مقایسه و خرید محصولات دارویی، بهداشتی و مراقبتی.
                        </p>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">دسترسی سریع</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-500">
                            <Link
                                href="/products"
                                class="block transition hover:text-[#04547b]"
                                >همه محصولات</Link
                            >
                            <Link
                                href="/categories"
                                class="block transition hover:text-[#04547b]"
                                >دسته‌بندی‌ها</Link
                            >
                            <Link
                                href="/brands"
                                class="block transition hover:text-[#04547b]"
                                >برندها</Link
                            >
                            <Link
                                href="/blog"
                                class="block transition hover:text-[#04547b]"
                                >مجله سلامت</Link
                            >
                        </div>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">راهنمای خرید</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-500">
                            <Link
                                href="/cart"
                                class="block transition hover:text-[#04547b]"
                                >سبد خرید</Link
                            >
                            <Link
                                href="/checkout"
                                class="block transition hover:text-[#04547b]"
                                >تکمیل سفارش</Link
                            >
                            <Link
                                href="/account/orders"
                                class="block transition hover:text-[#04547b]"
                                >پیگیری سفارش</Link
                            >
                            <Link
                                href="/account/profile"
                                class="block transition hover:text-[#04547b]"
                                >حساب کاربری</Link
                            >
                        </div>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">داروخونه</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-500">
                            برای اطلاع از وضعیت سفارش و خدمات حساب کاربری خود را
                            مدیریت کنید.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-10 flex flex-col gap-3 border-t border-[#edf3f4] pt-5 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span>© داروخونه — همه حقوق محفوظ است.</span>
                    <span>www.darookhooneh.ir</span>
                </div>
            </div>
        </footer>

        <nav
            class="fixed inset-x-0 bottom-0 z-50 border-t border-[#dce9ec] bg-white/95 px-3 py-2 shadow-[0_-8px_30px_rgba(4,84,123,0.08)] backdrop-blur lg:hidden"
            aria-label="دسترسی سریع"
        >
            <div class="mx-auto grid max-w-xl grid-cols-5 gap-1">
                <Link
                    href="/"
                    class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 hover:bg-[#eef8f8] hover:text-[#04547b]"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                        <path
                            d="m4 10 8-6 8 6v9a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1v-9Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />
                    </svg>
                    خانه
                </Link>
                <Link
                    href="/categories"
                    class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 hover:bg-[#eef8f8] hover:text-[#04547b]"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                        <rect
                            x="4"
                            y="4"
                            width="6"
                            height="6"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <rect
                            x="14"
                            y="4"
                            width="6"
                            height="6"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <rect
                            x="4"
                            y="14"
                            width="6"
                            height="6"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <rect
                            x="14"
                            y="14"
                            width="6"
                            height="6"
                            rx="1"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                    </svg>
                    دسته‌ها
                </Link>
                <Link
                    href="/products"
                    class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 hover:bg-[#eef8f8] hover:text-[#04547b]"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                        <circle
                            cx="11"
                            cy="11"
                            r="6.5"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <path
                            d="m16 16 4.2 4.2"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                    محصولات
                </Link>
                <Link
                    href="/cart"
                    class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 hover:bg-[#eef8f8] hover:text-[#04547b]"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                        <path
                            d="M5 6h2l1.4 9.2a2 2 0 0 0 2 1.7h6.8a2 2 0 0 0 2-1.6L20 9H8"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle
                            cx="10.2"
                            cy="19.2"
                            r="1.1"
                            fill="currentColor"
                        />
                        <circle
                            cx="17.2"
                            cy="19.2"
                            r="1.1"
                            fill="currentColor"
                        />
                    </svg>
                    سبد
                </Link>
                <Link
                    :href="user ? '/account/profile' : '/login'"
                    class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 hover:bg-[#eef8f8] hover:text-[#04547b]"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                        <circle
                            cx="12"
                            cy="8"
                            r="3.5"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <path
                            d="M5 20c.8-3.2 3.3-5 7-5s6.2 1.8 7 5"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                    حساب
                </Link>
            </div>
        </nav>
    </div>
</template>
