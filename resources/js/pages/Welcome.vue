<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    slug: string;
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
    featuredProducts: FeaturedProduct[];
    categories: Category[];
}>();

function formatPrice(value: number | null): string {
    return value === null ? 'تماس بگیرید' : `${value.toLocaleString('fa-IR')} تومان`;
}

function addToCart(product: FeaturedProduct): void {
    if (!product.available || product.price === null) {
        return;
    }
    router.post('/cart/items', { product_id: product.id, quantity: 1 }, { preserveScroll: true });
}
</script>

<template>
    <Head>
        <title>HealthStore | فروشگاه آنلاین محصولات سلامت</title>
        <meta name="description" content="خرید آنلاین محصولات بهداشتی و سلامت با مشاهده محصولات منتخب، دسته‌بندی‌ها، موجودی و مسیر پرداخت یکپارچه." />
        <link rel="canonical" href="/" />
        <meta property="og:title" content="HealthStore | فروشگاه آنلاین محصولات سلامت" />
        <meta property="og:description" content="محصولات سلامت و بهداشتی را جستجو، بررسی و آنلاین خرید کنید." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="/" />
    </Head>

    <div dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-2xl font-bold text-slate-900">Health<span class="text-emerald-600">Store</span></Link>
                <nav class="hidden items-center gap-6 text-sm font-medium sm:flex">
                    <Link href="/" class="hover:text-emerald-600">خانه</Link>
                    <Link href="/products" class="hover:text-emerald-600">محصولات</Link>
                    <Link href="/blog" class="hover:text-emerald-600">مجله سلامت</Link>
                    <Link href="/cart" class="hover:text-emerald-600">سبد خرید</Link>
                </nav>
                <div class="flex items-center gap-2 text-sm font-medium">
                    <Link href="/login" class="rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-100">ورود</Link>
                    <Link href="/register" class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">ثبت‌نام</Link>
                </div>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid items-center gap-10 rounded-3xl bg-slate-900 p-8 text-white shadow-xl lg:grid-cols-[1.15fr_0.85fr] lg:p-12">
                    <div>
                        <p class="mb-4 inline-flex rounded-full bg-emerald-400/15 px-3 py-1 text-sm font-medium text-emerald-300">فروشگاه آنلاین سلامت</p>
                        <h1 class="text-4xl leading-tight font-bold tracking-tight sm:text-5xl">محصولات سلامت را ساده و مطمئن پیدا کنید.</h1>
                        <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">از بین محصولات و دسته‌بندی‌های موجود جستجو کنید، جزئیات و موجودی را ببینید و خریدتان را تا پرداخت آنلاین ادامه دهید.</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link href="/products" class="rounded-xl bg-emerald-500 px-6 py-3 font-semibold text-white hover:bg-emerald-600">مشاهده محصولات</Link>
                            <Link href="/blog" class="rounded-xl border border-white/20 px-6 py-3 font-semibold text-white hover:bg-white/10">مطالب سلامت</Link>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-7">
                        <div class="text-5xl">🛒</div>
                        <h2 class="mt-5 text-2xl font-bold">مسیر خرید یکپارچه</h2>
                        <div class="mt-6 space-y-3 text-sm text-slate-300">
                            <div class="rounded-2xl bg-white/5 p-4">۱. محصول را انتخاب کنید</div>
                            <div class="rounded-2xl bg-white/5 p-4">۲. موجودی و قیمت را بررسی کنید</div>
                            <div class="rounded-2xl bg-white/5 p-4">۳. آدرس و سفارش را تأیید کنید</div>
                            <div class="rounded-2xl bg-white/5 p-4">۴. پرداخت را انجام دهید</div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="props.categories.length" class="border-y border-slate-200 bg-white">
                <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                    <div class="mb-6 flex items-end justify-between gap-4">
                        <div><h2 class="text-2xl font-bold">دسته‌بندی‌ها</h2><p class="mt-1 text-sm text-slate-500">دسترسی سریع به گروه‌های محصولات</p></div>
                        <Link href="/products" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">همه محصولات</Link>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Link v-for="category in props.categories" :key="category.id" :href="`/products?category=${encodeURIComponent(category.slug)}`" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 font-semibold transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50">{{ category.name }}</Link>
                    </div>
                </div>
            </section>

            <section v-if="props.featuredProducts.length" class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div><h2 class="text-2xl font-bold">محصولات منتخب</h2><p class="mt-1 text-sm text-slate-500">محصولات فعال و منتخب فروشگاه</p></div>
                    <Link href="/products" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">مشاهده همه</Link>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article v-for="product in props.featuredProducts" :key="product.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <Link :href="`/products/${product.slug}`" class="block">
                            <div class="aspect-square bg-slate-100">
                                <img v-if="product.image" :src="product.image" :alt="product.name" class="h-full w-full object-contain p-5" />
                                <div v-else class="flex h-full items-center justify-center text-sm text-slate-400">بدون تصویر</div>
                            </div>
                            <div class="p-4">
                                <p v-if="product.brand" class="text-xs text-slate-500">{{ product.brand }}</p>
                                <h3 class="mt-1 line-clamp-2 min-h-12 font-semibold leading-6">{{ product.name }}</h3>
                                <div class="mt-3 flex items-end justify-between gap-2">
                                    <div><div class="font-bold">{{ formatPrice(product.price) }}</div><div v-if="product.compare_at_price && product.compare_at_price > (product.price ?? 0)" class="text-xs text-slate-400 line-through">{{ formatPrice(product.compare_at_price) }}</div></div>
                                    <span :class="product.available ? 'text-emerald-600' : 'text-red-500'" class="text-xs font-medium">{{ product.available ? 'موجود' : 'ناموجود' }}</span>
                                </div>
                            </div>
                        </Link>
                        <div class="px-4 pb-4">
                            <button type="button" :disabled="!product.available || product.price === null" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" @click="addToCart(product)">افزودن به سبد</button>
                        </div>
                    </article>
                </div>
            </section>

            <section v-else class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center"><h2 class="text-xl font-bold">محصولات در حال آماده‌سازی هستند</h2><p class="mt-2 text-sm text-slate-500">برای مشاهده کاتالوگ محصولات به صفحه محصولات بروید.</p><Link href="/products" class="mt-5 inline-flex rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white hover:bg-emerald-700">مشاهده محصولات</Link></div>
            </section>

            <section class="border-y border-slate-200 bg-white">
                <div class="mx-auto grid max-w-6xl gap-4 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:px-8">
                    <div class="rounded-2xl bg-slate-50 p-5"><div class="font-bold">موجودی بررسی می‌شود</div><p class="mt-2 text-sm leading-6 text-slate-600">قبل از ثبت سفارش، موجودی محصولات بررسی می‌شود.</p></div>
                    <div class="rounded-2xl bg-slate-50 p-5"><div class="font-bold">قیمت به‌روز</div><p class="mt-2 text-sm leading-6 text-slate-600">قیمت در مسیر خرید دوباره بررسی می‌شود.</p></div>
                    <div class="rounded-2xl bg-slate-50 p-5"><div class="font-bold">پیگیری سفارش</div><p class="mt-2 text-sm leading-6 text-slate-600">وضعیت سفارش پس از ثبت در حساب کاربری قابل پیگیری است.</p></div>
                </div>
            </section>
        </main>

        <footer class="bg-slate-950 text-slate-400"><div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm sm:px-6 lg:px-8"><div class="font-semibold text-white">HealthStore</div><div>فروشگاه آنلاین محصولات سلامت</div></div></footer>
    </div>
</template>
