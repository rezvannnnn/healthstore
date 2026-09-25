<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import StorefrontHeader from '@/components/StorefrontHeader.vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    short_description: string | null;
    image: string | null;
    price: number | null;
    compare_at_price: number | null;
    available: boolean;
}

interface Brand {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
}

interface Seo {
    title: string;
    description: string;
    canonical: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    seo: Seo;
    brand: Brand;
    products: Product[];
    pagination: Pagination;
}>();

function formatPrice(value: number | null): string {
    return value === null
        ? 'تماس بگیرید'
        : value.toLocaleString('fa-IR') + ' تومان';
}

function addToCart(productId: number): void {
    router.post(
        '/cart/items',
        {
            product_id: productId,
            quantity: 1,
        },
        {
            preserveScroll: true,
        },
    );
}

function pageUrl(page: number): string {
    if (page <= 1) {
        return '/brands/' + props.brand.slug;
    }

    return '/brands/' + props.brand.slug + '?page=' + page;
}
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" />
        <link rel="canonical" :href="seo.canonical" />
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="seo.title" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:url" :content="seo.canonical" />
        <meta v-if="brand.logo" property="og:image" :content="brand.logo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seo.title" />
        <meta name="twitter:description" :content="seo.description" />
        <meta v-if="brand.logo" name="twitter:image" :content="brand.logo" />
    </Head>

    <div dir="rtl" class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10">
        <StorefrontHeader active="brands" />
        <main
            dir="rtl"
            class="min-h-screen bg-dh-surface px-4 py-8 dark:bg-dh-surface"
        >
            <div class="mx-auto max-w-7xl space-y-8">
                <nav
                    aria-label="مسیر صفحه"
                    class="flex flex-wrap items-center gap-2 text-sm text-dh-muted dark:text-dh-muted"
                >
                    <Link href="/" class="hover:text-dh-700">خانه</Link>
                    <span>/</span>
                    <Link href="/products" class="hover:text-dh-700"
                        >محصولات</Link
                    >
                    <span>/</span>
                    <span class="text-dh-900">{{ brand.name }}</span>
                </nav>

                <header
                    class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-dh-100"
                >
                    <div
                        class="grid gap-6 p-6 md:p-8"
                        :class="
                            brand.logo
                                ? 'md:grid-cols-[180px_1fr]'
                                : 'md:grid-cols-1'
                        "
                    >
                        <div
                            v-if="brand.logo"
                            class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl bg-dh-50 p-6"
                        >
                            <img
                                :src="brand.logo"
                                :alt="brand.name"
                                class="max-h-full max-w-full object-contain"
                            />
                        </div>
                        <div class="self-center">
                            <p class="text-sm font-medium text-dh-700">
                                محصولات برند
                            </p>
                            <h1
                                class="mt-2 text-3xl font-bold text-dh-900 md:text-4xl"
                            >
                                {{ brand.name }}
                            </h1>
                            <p
                                v-if="brand.description"
                                class="mt-4 max-w-3xl leading-8 text-dh-muted"
                            >
                                {{ brand.description }}
                            </p>
                            <p
                                v-else
                                class="mt-4 max-w-3xl leading-8 text-dh-muted"
                            >
                                محصولات فعال این برند را مشاهده و بررسی کنید.
                            </p>
                        </div>
                    </div>
                </header>

                <section>
                    <div
                        class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <h2 class="text-2xl font-bold text-dh-900">
                                محصولات {{ brand.name }}
                            </h2>
                            <p class="mt-1 text-sm text-dh-muted">
                                {{ pagination.total.toLocaleString('fa-IR') }}
                                محصول
                            </p>
                        </div>
                        <Link
                            href="/products"
                            class="text-sm font-medium text-dh-700 hover:text-dh-800"
                        >
                            همه محصولات
                        </Link>
                    </div>

                    <div
                        v-if="products.length"
                        class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <article
                            v-for="product in products"
                            :key="product.id"
                            class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-dh-100 transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <Link
                                :href="'/products/' + product.slug"
                                class="block"
                            >
                                <div class="aspect-square bg-dh-50">
                                    <img
                                        v-if="product.image"
                                        :src="product.image"
                                        :alt="product.name"
                                        class="h-full w-full object-contain p-6"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full items-center justify-center text-sm text-dh-muted"
                                    >
                                        بدون تصویر
                                    </div>
                                </div>
                                <div class="space-y-3 p-4">
                                    <h3
                                        class="line-clamp-2 font-semibold text-dh-900"
                                    >
                                        {{ product.name }}
                                    </h3>
                                    <p
                                        v-if="product.short_description"
                                        class="line-clamp-2 text-sm text-dh-muted dark:text-dh-muted"
                                    >
                                        {{ product.short_description }}
                                    </p>
                                </div>
                            </Link>
                            <div
                                class="flex items-center justify-between gap-3 border-t border-dh-100 p-4"
                            >
                                <div>
                                    <div class="font-bold text-dh-900">
                                        {{ formatPrice(product.price) }}
                                    </div>
                                    <div
                                        v-if="
                                            product.compare_at_price &&
                                            product.compare_at_price >
                                                (product.price ?? 0)
                                        "
                                        class="text-xs text-dh-muted line-through"
                                    >
                                        {{
                                            formatPrice(
                                                product.compare_at_price,
                                            )
                                        }}
                                    </div>
                                </div>
                                <button
                                    v-if="
                                        product.available &&
                                        product.price !== null
                                    "
                                    type="button"
                                    class="rounded-xl bg-dh-700 px-3 py-2 text-xs font-semibold text-white hover:bg-dh-800"
                                    @click="addToCart(product.id)"
                                >
                                    افزودن به سبد
                                </button>
                                <span
                                    v-else
                                    class="text-xs font-medium text-red-500"
                                >
                                    ناموجود
                                </span>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-dh-200 p-12 text-center text-dh-muted"
                    >
                        در این برند هنوز محصول فعالی ثبت نشده است.
                    </div>
                </section>

                <nav
                    v-if="pagination.last_page > 1"
                    aria-label="صفحات برند"
                    class="flex flex-wrap items-center justify-center gap-2"
                >
                    <Link
                        v-if="pagination.current_page > 1"
                        :href="pageUrl(pagination.current_page - 1)"
                        preserve-scroll
                        aria-label="صفحه قبلی"
                        class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-dh-700 text-dh-muted ring-1 ring-dh-100 hover:bg-dh-surface"
                    >
                        قبلی
                    </Link>
                    <Link
                        v-for="page in pagination.last_page"
                        :key="page"
                        :href="pageUrl(page)"
                        preserve-scroll
                        :aria-current="
                            page === pagination.current_page
                                ? 'page'
                                : undefined
                        "
                        :aria-label="'صفحه ' + page.toLocaleString('fa-IR')"
                        class="min-w-10 rounded-xl px-3 py-2 text-center text-sm font-medium"
                        :class="
                            page === pagination.current_page
                                ? 'bg-dh-700 text-white'
                                : 'bg-white text-dh-700 text-dh-muted ring-1 ring-dh-100 hover:bg-dh-surface'
                        "
                    >
                        {{ page.toLocaleString('fa-IR') }}
                    </Link>
                    <Link
                        v-if="pagination.current_page < pagination.last_page"
                        :href="pageUrl(pagination.current_page + 1)"
                        preserve-scroll
                        aria-label="صفحه بعدی"
                        class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-dh-700 text-dh-muted ring-1 ring-dh-100 hover:bg-dh-surface"
                    >
                        بعدی
                    </Link>
                </nav>
            </div>
        </main>
    </div>
</template>
