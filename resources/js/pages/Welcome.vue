<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
import CartLink from '@/components/CartLink.vue';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Seo {
    title: string;
    description: string;
    canonical: string;
}

interface FeaturedProduct {
    id: number;
    name: string;
    slug: string;
    brand: string | null;
    image: string | null;
    price: number | null;
    compare_at_price: number | null;
    available: boolean;
}

const props = defineProps<{
    seo: Seo;
    featuredProducts: FeaturedProduct[];
    categories: Category[];
}>();

const searchQuery = ref('');
const addingProductId = ref<number | null>(null);

function formatPrice(value: number | null): string {
    return value === null
        ? 'تماس بگیرید'
        : `${value.toLocaleString('fa-IR')} تومان`;
}

function searchProducts(): void {
    const query = searchQuery.value.trim();

    router.get(
        '/products',
        query ? { search: query } : {},
        {
            preserveState: true,
            replace: true,
        },
    );
}

function addToCart(product: FeaturedProduct): void {
    if (
        !product.available ||
        product.price === null ||
        addingProductId.value !== null
    ) {
        return;
    }

    router.post(
        '/cart/items',
        { product_id: product.id, quantity: 1 },
        {
            preserveScroll: true,
            onStart: () => { addingProductId.value = product.id; },
            onFinish: () => { addingProductId.value = null; },
        },
    );
}
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" />
        <link rel="canonical" :href="seo.canonical" />
        <meta property="og:title" :content="seo.title" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="seo.canonical" />
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" :content="seo.title" />
        <meta name="twitter:description" :content="seo.description" />
    </Head>

    <div dir="rtl" class="min-h-screen bg-dh-surface text-dh-ink">
        <header class="sticky top-0 z-40 border-b border-dh-100/80 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex min-h-20 items-center gap-4">
                    <Link href="/" class="flex shrink-0 items-center" aria-label="داروخونه">
                        <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                    </Link>

                    <form class="hidden min-w-0 flex-1 md:block" @submit.prevent="searchProducts">
                        <label for="site-search" class="sr-only">جستجوی محصولات</label>
                        <div class="relative">
                            <svg viewBox="0 0 24 24" class="pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2 text-dh-500" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                                <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                            <input
                                id="site-search"
                                v-model="searchQuery"
                                type="search"
                                placeholder="نام محصول، برند یا بارکد را جستجو کنید..."
                                class="h-12 w-full rounded-2xl border border-dh-100 bg-dh-50/70 pr-12 pl-28 text-sm text-dh-900 outline-none transition placeholder:text-dh-muted focus:border-dh-300 focus:bg-white focus:ring-4 focus:ring-dh-100"
                            />
                            <button
                                type="submit"
                                class="absolute top-1/2 left-1.5 -translate-y-1/2 rounded-xl bg-dh-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-dh-700"
                            >
                                جستجو
                            </button>
                        </div>
                    </form>

                    <div class="mr-auto flex items-center gap-1">
                        <Link
                            href="/login"
                            class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-dh-700 transition hover:bg-dh-50 sm:block"
                        >
                            ورود
                        </Link>
                        <Link
                            href="/register"
                            class="hidden rounded-xl bg-dh-green-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-dh-green-600 sm:block"
                        >
                            ثبت‌نام
                        </Link>
                        <CartLink />
                    </div>
                </div>

                <div class="border-t border-dh-50 md:hidden">
                    <form class="py-3" @submit.prevent="searchProducts">
                        <label for="mobile-site-search" class="sr-only">جستجوی محصولات</label>
                        <div class="relative">
                            <input
                                id="mobile-site-search"
                                v-model="searchQuery"
                                type="search"
                                placeholder="جستجوی محصول یا برند..."
                                class="h-11 w-full rounded-xl border border-dh-100 bg-dh-50/70 px-4 pl-20 text-sm outline-none focus:border-dh-300 focus:bg-white focus:ring-4 focus:ring-dh-100"
                            />
                            <button type="submit" class="absolute top-1/2 left-1 -translate-y-1/2 rounded-lg bg-dh-600 px-3 py-2 text-xs font-bold text-white">
                                جستجو
                            </button>
                        </div>
                    </form>
                </div>

                <nav class="hidden h-12 items-center gap-7 text-sm font-semibold text-dh-700 md:flex">
                    <Link href="/" class="relative flex h-full items-center text-dh-800 after:absolute after:right-0 after:bottom-0 after:left-0 after:h-0.5 after:rounded-full after:bg-dh-500">خانه</Link>
                    <Link href="/products" class="transition hover:text-dh-500">محصولات</Link>
                    <Link href="/categories" class="transition hover:text-dh-500">دسته‌بندی‌ها</Link>
                    <Link href="/brands" class="transition hover:text-dh-500">برندها</Link>
                    <Link href="/blog" class="transition hover:text-dh-500">مجله سلامت</Link>
                </nav>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden border-b border-dh-100 bg-white">
                <div class="absolute inset-0 opacity-70 [background-image:radial-gradient(circle_at_12%_20%,rgba(29,166,169,0.14),transparent_28%),radial-gradient(circle_at_85%_70%,rgba(99,185,91,0.13),transparent_30%)]"></div>
                <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                    <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                        <div class="max-w-2xl">
                            <span class="inline-flex items-center gap-2 rounded-full border border-dh-100 bg-dh-50 px-3.5 py-2 text-xs font-bold text-dh-700">
                                <span class="size-2 rounded-full bg-dh-green-500"></span>
                                فروشگاه آنلاین سلامت
                            </span>
                            <h1 class="mt-5 text-4xl font-extrabold leading-[1.25] tracking-tight text-dh-900 sm:text-5xl lg:text-6xl">
                                سلامت، ساده‌تر از چیزی که فکر می‌کنید.
                            </h1>
                            <p class="mt-5 max-w-xl text-base leading-8 text-dh-muted sm:text-lg">
                                دارو، مکمل و محصولات بهداشتی را با اطلاعات کامل پیدا کنید و خریدتان را با خیال راحت انجام دهید.
                            </p>
                            <form class="mt-8" @submit.prevent="searchProducts">
                                <div class="flex rounded-2xl border border-dh-200 bg-white p-1.5 shadow-[0_12px_35px_rgba(20,108,114,0.10)]">
                                    <input
                                        v-model="searchQuery"
                                        type="search"
                                        placeholder="مثلاً: ویتامین D، ضدآفتاب، شامپو..."
                                        class="min-w-0 flex-1 bg-transparent px-4 text-sm outline-none sm:text-base"
                                        aria-label="جستجوی سریع محصولات"
                                    />
                                    <button type="submit" class="rounded-xl bg-dh-600 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-dh-700 sm:px-7">
                                        جستجو
                                    </button>
                                </div>
                            </form>
                            <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium text-dh-muted">
                                <span>جستجوهای محبوب:</span>
                                <Link href="/products?search=ویتامین%20D" class="rounded-full bg-dh-50 px-3 py-1.5 text-dh-700 hover:bg-dh-100">ویتامین D</Link>
                                <Link href="/products?search=ضدآفتاب" class="rounded-full bg-dh-50 px-3 py-1.5 text-dh-700 hover:bg-dh-100">ضدآفتاب</Link>
                                <Link href="/products?search=شامپو" class="rounded-full bg-dh-50 px-3 py-1.5 text-dh-700 hover:bg-dh-100">شامپو</Link>
                            </div>
                        </div>

                        <div class="relative mx-auto w-full max-w-xl">
                            <div class="absolute -right-8 top-8 h-44 w-44 rounded-full bg-dh-100 blur-2xl"></div>
                            <div class="absolute -bottom-8 left-0 h-48 w-48 rounded-full bg-dh-green-100 blur-2xl"></div>
                            <div class="relative rounded-[2rem] border border-dh-100 bg-dh-50 p-5 shadow-[0_22px_55px_rgba(20,108,114,0.12)] sm:p-7">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-dh-600">انتخاب روزمره</p>
                                        <h2 class="mt-1 text-xl font-extrabold text-dh-900">برای مراقبت بهتر</h2>
                                    </div>
                                    <div class="flex size-12 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-dh-100">
                                        <svg viewBox="0 0 24 24" class="size-6 text-dh-green-600" fill="none" aria-hidden="true">
                                            <path d="M19 4.5c-5.6 0-10.1 2.7-10.8 7.4-.5 3.3 1.7 6.3 5.1 6.6 4.4.4 6.2-4.5 5.7-14Z" stroke="currentColor" stroke-width="1.6"/>
                                            <path d="M4 19.5c2.1-4.2 5.1-6.6 10.1-8.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-6 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-dh-100">
                                        <div class="flex items-center gap-2 text-dh-600">
                                            <span class="flex size-9 items-center justify-center rounded-xl bg-dh-50">
                                                <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
                                                    <path d="M12 3v18M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                            </span>
                                            <span class="text-xs font-bold">سلامت و مکمل</span>
                                        </div>
                                        <p class="mt-3 text-sm leading-6 text-dh-muted">انتخاب‌های روزمره</p>
                                    </div>
                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-dh-100">
                                        <div class="flex items-center gap-2 text-dh-green-600">
                                            <span class="flex size-9 items-center justify-center rounded-xl bg-dh-green-50">
                                                <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
                                                    <path d="M7 13c-2.8-1.6-3.5-5.2-1.4-7.3C7.8 3.5 11.4 4.2 13 7c1.6-2.8 5.2-3.5 7.3-1.4 2.1 2.1 1.4 5.7-1.4 7.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                    <path d="M12 20c-1.6-5 0-9.6 4.2-13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                </svg>
                                            </span>
                                            <span class="text-xs font-bold">مراقبت شخصی</span>
                                        </div>
                                        <p class="mt-3 text-sm leading-6 text-dh-muted">پوست، مو و بهداشت</p>
                                    </div>
                                </div>
                                <div class="mt-3 rounded-2xl bg-dh-900 px-5 py-4 text-white">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-medium text-dh-200">مسیر خرید</p>
                                            <p class="mt-1 text-sm font-bold">انتخاب → بررسی → سفارش → پیگیری</p>
                                        </div>
                                        <svg viewBox="0 0 24 24" class="size-6 text-dh-200" fill="none" aria-hidden="true">
                                            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="props.categories.length" class="py-12 sm:py-14">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <span class="text-xs font-extrabold text-dh-500">دسترسی سریع</span>
                            <h2 class="mt-1 text-2xl font-extrabold text-dh-900 sm:text-3xl">دسته‌بندی‌های محبوب</h2>
                        </div>
                        <Link href="/categories" class="hidden rounded-xl px-3 py-2 text-sm font-bold text-dh-600 hover:bg-dh-50 sm:block">همه دسته‌بندی‌ها</Link>
                    </div>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="(category, index) in props.categories"
                            :key="category.id"
                            :href="'/categories/' + category.slug"
                            class="group flex items-center gap-4 rounded-2xl border border-dh-100 bg-white p-4 transition duration-200 hover:-translate-y-0.5 hover:border-dh-300 hover:shadow-[0_10px_30px_rgba(20,108,114,0.08)]"
                        >
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl" :class="index % 2 === 0 ? 'bg-dh-50 text-dh-600' : 'bg-dh-green-50 text-dh-green-600'">
                                <svg viewBox="0 0 24 24" class="size-6" fill="none" aria-hidden="true">
                                    <path d="M6 6.5A2.5 2.5 0 0 1 8.5 4H19v11.5A2.5 2.5 0 0 1 16.5 18h-8A2.5 2.5 0 0 1 6 15.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="M9 8h7M9 11h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-extrabold text-dh-800 transition group-hover:text-dh-600">{{ category.name }}</span>
                                <span class="mt-1 block text-xs text-dh-muted">مشاهده محصولات</span>
                            </span>
                            <svg viewBox="0 0 24 24" class="mr-auto size-5 text-dh-300 transition group-hover:-translate-x-0.5 group-hover:text-dh-500" fill="none" aria-hidden="true">
                                <path d="m15 6-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </section>

            <section v-if="props.featuredProducts.length" class="border-y border-dh-100 bg-white py-12 sm:py-14">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <span class="text-xs font-extrabold text-dh-green-600">انتخاب‌شده برای شما</span>
                            <h2 class="mt-1 text-2xl font-extrabold text-dh-900 sm:text-3xl">محصولات منتخب</h2>
                        </div>
                        <Link href="/products" class="hidden rounded-xl px-3 py-2 text-sm font-bold text-dh-600 hover:bg-dh-50 sm:block">مشاهده همه</Link>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <article
                            v-for="product in props.featuredProducts"
                            :key="product.id"
                            class="group overflow-hidden rounded-3xl border border-dh-100 bg-white transition duration-200 hover:-translate-y-1 hover:border-dh-200 hover:shadow-[0_18px_40px_rgba(20,108,114,0.10)]"
                        >
                            <Link :href="`/products/${product.slug}`" class="block">
                                <div class="relative aspect-square overflow-hidden bg-dh-50/60">
                                    <span v-if="product.compare_at_price && product.compare_at_price > (product.price ?? 0)" class="absolute right-3 top-3 z-10 rounded-full bg-dh-green-500 px-2.5 py-1 text-[11px] font-extrabold text-white">
                                        تخفیف
                                    </span>
                                    <img
                                        v-if="product.image"
                                        :src="product.image"
                                        :alt="product.name"
                                        loading="lazy"
                                        class="h-full w-full object-contain p-6 transition duration-300 group-hover:scale-[1.03]"
                                    />
                                    <div v-else class="flex h-full items-center justify-center text-sm font-medium text-dh-muted">بدون تصویر</div>
                                </div>
                                <div class="p-4">
                                    <p v-if="product.brand" class="text-xs font-semibold text-dh-muted">{{ product.brand }}</p>
                                    <h3 class="mt-1 line-clamp-2 min-h-12 text-sm leading-6 font-extrabold text-dh-900">{{ product.name }}</h3>
                                    <div class="mt-4 flex items-end justify-between gap-3">
                                        <div>
                                            <div class="font-extrabold text-dh-900">{{ formatPrice(product.price) }}</div>
                                            <div v-if="product.compare_at_price && product.compare_at_price > (product.price ?? 0)" class="mt-1 text-xs text-dh-muted line-through">{{ formatPrice(product.compare_at_price) }}</div>
                                        </div>
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="product.available ? 'bg-dh-green-50 text-dh-green-700' : 'bg-dh-100 text-dh-muted'">
                                            {{ product.available ? 'موجود' : 'ناموجود' }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                            <div class="px-4 pb-4">
                                <button
                                    type="button"
                                    :disabled="!product.available || product.price === null || addingProductId !== null"
                                    :aria-busy="addingProductId === product.id"
                                    class="w-full rounded-xl bg-dh-600 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-dh-700 disabled:cursor-not-allowed disabled:bg-dh-100 disabled:text-dh-muted"
                                    @click="addToCart(product)"
                                >
                                    <span v-if="addingProductId === product.id" class="inline-flex items-center gap-2">
                                        <svg viewBox="0 0 24 24" class="size-4 animate-spin" fill="none" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" opacity="0.25" />
                                            <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                        در حال افزودن…
                                    </span>
                                    <span v-else>افزودن به سبد خرید</span>
                                </button>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="py-12 sm:py-14">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-dh-100 bg-white p-6">
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-dh-50 text-dh-600">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
                                    <path d="M5 7h14M5 12h14M5 17h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h3 class="mt-4 font-extrabold text-dh-900">اطلاعات روشن و کامل</h3>
                            <p class="mt-2 text-sm leading-7 text-dh-muted">اطلاعات محصول، قیمت و موجودی در مسیر خرید بررسی می‌شود.</p>
                        </div>
                        <div class="rounded-3xl border border-dh-100 bg-white p-6">
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-dh-green-50 text-dh-green-600">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
                                    <path d="M12 3.5 18 6v5.4c0 4-2.4 7.2-6 9.1-3.6-1.9-6-5.1-6-9.1V6l6-2.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <h3 class="mt-4 font-extrabold text-dh-900">خرید با خیال راحت</h3>
                            <p class="mt-2 text-sm leading-7 text-dh-muted">فرآیند سفارش و پرداخت با بررسی‌های لازم انجام می‌شود.</p>
                        </div>
                        <div class="rounded-3xl border border-dh-100 bg-white p-6">
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-dh-50 text-dh-600">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
                                    <path d="M5 12a7 7 0 1 0 14 0 7 7 0 0 0-14 0Z" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M12 8v4l2.7 1.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h3 class="mt-4 font-extrabold text-dh-900">پیگیری سفارش</h3>
                            <p class="mt-2 text-sm leading-7 text-dh-muted">وضعیت سفارش پس از ثبت از داخل حساب کاربری قابل پیگیری است.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-dh-100 bg-dh-900 text-white">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.4fr_1fr] lg:px-8">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10">
                                <svg viewBox="0 0 48 48" class="size-7 text-dh-200" fill="none" aria-hidden="true">
                                    <path d="M13 27.5 27.5 13a7.5 7.5 0 0 1 10.6 10.6L23.6 38.1A7.5 7.5 0 0 1 13 27.5Z" fill="currentColor" opacity=".16"/>
                                    <path d="M16.2 31.8 31.8 16.2M19.7 28.3l10 10M28.3 19.7l-10-10" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                                    <path d="M15.1 21.5c-3.4-2.4-4.1-6.8-1.8-9.4 2.5-2.8 6.9-2.3 9.3 1.1" stroke="#63b95b" stroke-width="2.6" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div>
                                <div class="text-xl font-extrabold">داروخونه</div>
                                <div class="mt-1 text-xs text-dh-200">دارو و محصولات بهداشتی</div>
                            </div>
                        </div>
                        <p class="mt-5 max-w-xl text-sm leading-7 text-dh-100">
                            یک تجربه ساده و روشن برای پیدا کردن محصولات سلامت و مدیریت سفارش‌های روزمره.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-6 text-sm">
                        <div>
                            <h3 class="font-extrabold text-white">دسترسی سریع</h3>
                            <div class="mt-3 space-y-2 text-dh-100">
                                <Link href="/products" class="block hover:text-white">محصولات</Link>
                                <Link href="/categories" class="block hover:text-white">دسته‌بندی‌ها</Link>
                                <Link href="/brands" class="block hover:text-white">برندها</Link>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-white">مطالب</h3>
                            <div class="mt-3 space-y-2 text-dh-100">
                                <Link href="/blog" class="block hover:text-white">مجله سلامت</Link>
                                <Link href="/login" class="block hover:text-white">حساب کاربری</Link>
                                <Link href="/cart" class="block hover:text-white">سبد خرید</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <div class="fixed inset-x-0 bottom-0 z-50 border-t border-dh-100 bg-white/95 px-3 py-2 shadow-[0_-8px_25px_rgba(20,86,92,0.08)] backdrop-blur md:hidden">
            <nav class="mx-auto grid max-w-md grid-cols-4 gap-1 text-center text-[11px] font-bold text-dh-muted" aria-label="ناوبری موبایل">
                <Link href="/" class="rounded-xl bg-dh-50 px-2 py-2 text-dh-700">
                    <svg viewBox="0 0 24 24" class="mx-auto mb-1 size-4" fill="none" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    خانه
                </Link>
                <Link href="/categories" class="rounded-xl px-2 py-2 hover:bg-dh-50">
                    <svg viewBox="0 0 24 24" class="mx-auto mb-1 size-4" fill="none" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.2" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="4" width="7" height="7" rx="1.2" stroke="currentColor" stroke-width="1.7"/><rect x="4" y="13" width="7" height="7" rx="1.2" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="13" width="7" height="7" rx="1.2" stroke="currentColor" stroke-width="1.7"/></svg>
                    دسته‌ها
                </Link>
                <Link href="/products" class="rounded-xl px-2 py-2 hover:bg-dh-50">
                    <svg viewBox="0 0 24 24" class="mx-auto mb-1 size-4" fill="none" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6" stroke="currentColor" stroke-width="1.7"/><path d="m15 15 5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    محصولات
                </Link>
                <Link href="/cart" class="rounded-xl px-2 py-2 hover:bg-dh-50">
                    <svg viewBox="0 0 24 24" class="mx-auto mb-1 size-4" fill="none" aria-hidden="true"><path d="M4 5h2l1.5 10.2a2 2 0 0 0 2 1.8h7.6a2 2 0 0 0 2-1.7L20 8H7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                    سبد خرید
                </Link>
            </nav>
        </div>

        <div class="h-16 md:hidden"></div>
    </div>
</template>
