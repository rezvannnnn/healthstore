<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import ProductCard from '../components/ProductCard.vue';
import StorefrontLayout from '../components/StorefrontLayout.vue';

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

function addToCart(productId: number): void {
    router.post(
        '/cart/items',
        { product_id: productId, quantity: 1 },
        { preserveScroll: true },
    );
}

const categoryIcons = ['✦', '◌', '⌁', '✧', '＋', '◍', '◇', '✣'];
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

    <StorefrontLayout>
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(33,167,165,0.13),transparent_34%),radial-gradient(circle_at_12%_85%,rgba(123,192,67,0.10),transparent_30%)]"></div>
            <div
                class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:px-8 lg:py-20"
            >
                <div>
                    <div
                        class="mb-5 inline-flex items-center gap-2 rounded-full border border-[#cfe5e4] bg-white px-3 py-1.5 text-xs font-bold text-[#087f88] shadow-sm"
                    >
                        <span class="h-2 w-2 rounded-full bg-[#69b84a]"></span>
                        فروشگاه آنلاین سلامت
                    </div>
                    <h1
                        class="max-w-3xl text-4xl leading-[1.2] font-black tracking-tight text-[#04547b] sm:text-5xl lg:text-6xl"
                    >
                        سلامت را ساده‌تر
                        <span class="text-[#1595a1]">انتخاب کن.</span>
                    </h1>
                    <p
                        class="mt-5 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg"
                    >
                        دارو، محصولات بهداشتی، مکمل و مراقبتی را راحت‌تر پیدا
                        کن؛ اطلاعات محصول را ببین و خریدت را با یک مسیر ساده
                        ادامه بده.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <Link
                            href="/products"
                            class="rounded-2xl bg-[#04547b] px-6 py-3.5 text-sm font-bold text-white shadow-[0_10px_24px_rgba(4,84,123,0.18)] transition hover:-translate-y-0.5 hover:bg-[#034664]"
                        >
                            شروع خرید
                        </Link>
                        <Link
                            href="/categories"
                            class="rounded-2xl border border-[#cfe0e5] bg-white px-6 py-3.5 text-sm font-bold text-[#04547b] transition hover:border-[#a9cfd5] hover:bg-[#f5fbfb]"
                        >
                            دسته‌بندی محصولات
                        </Link>
                    </div>
                    <div
                        class="mt-8 flex flex-wrap gap-x-7 gap-y-3 text-xs font-semibold text-slate-500"
                    >
                        <span>✓ اطلاعات شفاف محصول</span>
                        <span>✓ موجودی و قیمت به‌روز</span>
                        <span>✓ پیگیری سفارش</span>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-md">
                    <div
                        class="absolute -right-5 -top-5 h-24 w-24 rounded-full bg-[#dff3ef]"
                    ></div>
                    <div
                        class="absolute -bottom-7 -left-5 h-32 w-32 rounded-full bg-[#edf7e6]"
                    ></div>
                    <div
                        class="relative overflow-hidden rounded-[2.2rem] border border-white/80 bg-white p-6 shadow-[0_20px_60px_rgba(4,84,123,0.12)]"
                    >
                        <div
                            class="rounded-[1.8rem] bg-[linear-gradient(145deg,#f6fbfb_0%,#edf8f6_58%,#f3f9ed_100%)] p-7"
                        >
                            <div
                                class="mx-auto flex h-36 w-36 items-center justify-center rounded-full bg-white/80 shadow-sm"
                            >
                                <svg
                                    viewBox="0 0 120 120"
                                    class="h-24 w-24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M27 58c0-18 14.5-32.5 32.5-32.5H86c4 0 7.2 3.2 7.2 7.2v2.7c0 2-1.6 3.6-3.6 3.6h-4.9"
                                        stroke="#04547b"
                                        stroke-width="7"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M29 61c0 18 14.5 32.5 32.5 32.5H86c4 0 7.2-3.2 7.2-7.2v-2.7c0-2-1.6-3.6-3.6-3.6h-4.9"
                                        stroke="#0c98a4"
                                        stroke-width="7"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M65 26c3-8 10.5-11.7 20.4-10.5-3.4 8.4-9.4 13.7-17.5 15.8"
                                        stroke="#69b84a"
                                        stroke-width="6"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M58 50v23M46.5 61.5h23"
                                        stroke="#04547b"
                                        stroke-width="7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </div>
                            <div class="mt-7 text-center">
                                <div class="text-2xl font-black text-[#04547b]">
                                    داروخونه
                                </div>
                                <div class="mt-1 text-xs font-medium text-slate-400">
                                    دارو و محصولات بهداشتی
                                </div>
                            </div>
                            <div class="mt-7 grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-2xl border border-white bg-white/75 p-3 text-center"
                                >
                                    <div class="text-sm font-black text-[#0a8d9f]">
                                        سریع
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-400">
                                        پیدا کردن
                                    </div>
                                </div>
                                <div
                                    class="rounded-2xl border border-white bg-white/75 p-3 text-center"
                                >
                                    <div class="text-sm font-black text-[#2e8b57]">
                                        شفاف
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-400">
                                        اطلاعات محصول
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
            <div
                class="grid gap-3 rounded-3xl border border-[#dce9ec] bg-white p-3 shadow-[0_10px_35px_rgba(4,84,123,0.05)] sm:grid-cols-3"
            >
                <div class="rounded-2xl bg-[#f6fbfb] p-4">
                    <div class="font-bold text-[#04547b]">انتخاب راحت</div>
                    <p class="mt-1 text-xs leading-6 text-slate-500">
                        دسته‌بندی و جستجوی سریع برای پیدا کردن محصول موردنظر.
                    </p>
                </div>
                <div class="rounded-2xl bg-[#f7fbf4] p-4">
                    <div class="font-bold text-[#2f8150]">بررسی قبل از خرید</div>
                    <p class="mt-1 text-xs leading-6 text-slate-500">
                        قیمت، موجودی و جزئیات محصول در مسیر خرید بررسی می‌شود.
                    </p>
                </div>
                <div class="rounded-2xl bg-[#f7f9fc] p-4">
                    <div class="font-bold text-slate-700">مجله سلامت</div>
                    <p class="mt-1 text-xs leading-6 text-slate-500">
                        محتوای آموزشی برای انتخاب آگاهانه‌تر محصولات.
                    </p>
                </div>
            </div>
        </section>

        <section v-if="props.categories.length" class="py-10 sm:py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold text-[#0a8d9f]"
                            >دسته‌های محبوب</span
                        >
                        <h2 class="mt-1 text-2xl font-black text-slate-900">
                            از اینجا شروع کن
                        </h2>
                    </div>
                    <Link
                        href="/categories"
                        class="text-sm font-bold text-[#04547b] hover:text-[#0a8d9f]"
                    >
                        همه دسته‌ها
                    </Link>
                </div>

                <div
                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                >
                    <Link
                        v-for="(category, index) in props.categories"
                        :key="category.id"
                        :href="'/categories/' + category.slug"
                        class="group rounded-3xl border border-[#dce9ec] bg-white p-5 shadow-[0_8px_22px_rgba(4,84,123,0.04)] transition hover:-translate-y-1 hover:border-[#bfdde0] hover:shadow-[0_14px_30px_rgba(4,84,123,0.08)]"
                    >
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#edf8f8] text-lg font-black text-[#07899a] transition group-hover:bg-[#dff2f1]"
                        >
                            {{ categoryIcons[index % categoryIcons.length] }}
                        </span>
                        <span
                            class="mt-4 block text-sm font-bold leading-6 text-slate-800"
                        >
                            {{ category.name }}
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <section v-if="props.featuredProducts.length" class="bg-white py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-7 flex items-end justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold text-[#0a8d9f]"
                            >منتخب داروخونه</span
                        >
                        <h2 class="mt-1 text-2xl font-black text-slate-900">
                            محصولات منتخب
                        </h2>
                    </div>
                    <Link
                        href="/products"
                        class="text-sm font-bold text-[#04547b] hover:text-[#0a8d9f]"
                    >
                        مشاهده همه
                    </Link>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <ProductCard
                        v-for="product in props.featuredProducts"
                        :key="product.id"
                        :product="product"
                        @add-to-cart="addToCart"
                    />
                </div>
            </div>
        </section>

        <section class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden rounded-[2rem] border border-[#bfe1df] bg-[linear-gradient(135deg,#eef9f8_0%,#f8fcf1_100%)] p-7 sm:p-10"
                >
                    <div
                        class="grid items-center gap-7 lg:grid-cols-[1fr_auto]"
                    >
                        <div>
                            <span class="text-xs font-bold text-[#2f8150]"
                                >مجله سلامت</span
                            >
                            <h2
                                class="mt-2 text-2xl font-black text-[#04547b] sm:text-3xl"
                            >
                                قبل از خرید، بیشتر بدان.
                            </h2>
                            <p
                                class="mt-3 max-w-2xl text-sm leading-7 text-slate-600"
                            >
                                مطالب آموزشی و کاربردی درباره سلامت، مراقبت،
                                بهداشت و انتخاب بهتر محصولات.
                            </p>
                        </div>
                        <Link
                            href="/blog"
                            class="rounded-2xl bg-white px-6 py-3.5 text-center text-sm font-bold text-[#04547b] shadow-sm ring-1 ring-[#cfe0e5] transition hover:bg-[#f7fcfc]"
                        >
                            ورود به مجله سلامت
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
