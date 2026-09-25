<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import StorefrontHeader from '@/components/StorefrontHeader.vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    short_description: string | null;
    brand: string | null;
    image: string | null;
    price: number | null;
    compare_at_price: number | null;
    available: boolean;
}

interface CategoryLink {
    id: number;
    name: string;
    slug: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    parent: CategoryLink | null;
    children: CategoryLink[];
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
    category: Category;
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
        return '/categories/' + props.category.slug;
    }

    return '/categories/' + props.category.slug + '?page=' + page;
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
        <meta
            v-if="category.image"
            property="og:image"
            :content="category.image"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seo.title" />
        <meta name="twitter:description" :content="seo.description" />
        <meta
            v-if="category.image"
            name="twitter:image"
            :content="category.image"
        />
    </Head>

    <div dir="rtl" class="min-h-screen bg-dh-surface text-dh-ink">
        <StorefrontHeader active="categories" />
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
                    <Link href="/products" class="hover:text-dh-700">
                        محصولات
                    </Link>
                    <template v-if="category.parent">
                        <span>/</span>
                        <Link
                            :href="'/categories/' + category.parent.slug"
                            class="hover:text-dh-700"
                        >
                            {{ category.parent.name }}
                        </Link>
                    </template>
                    <span>/</span>
                    <span class="text-dh-900">
                        {{ category.name }}
                    </span>
                </nav>

                <header
                    class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-dh-100"
                >
                    <div
                        class="grid gap-6 p-6 md:p-8"
                        :class="
                            category.image
                                ? 'md:grid-cols-[180px_1fr]'
                                : 'md:grid-cols-1'
                        "
                    >
                        <div
                            v-if="category.image"
                            class="aspect-square overflow-hidden rounded-2xl bg-dh-50"
                        >
                            <img
                                :src="category.image"
                                :alt="category.name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div class="self-center">
                            <p class="text-sm font-medium text-dh-700">
                                دسته‌بندی محصولات
                            </p>
                            <h1
                                class="mt-2 text-3xl font-bold text-dh-900 md:text-4xl"
                            >
                                {{ category.name }}
                            </h1>
                            <p
                                v-if="category.description"
                                class="mt-4 max-w-3xl leading-8 text-dh-muted"
                            >
                                {{ category.description }}
                            </p>
                            <p
                                v-else
                                class="mt-4 max-w-3xl leading-8 text-dh-muted"
                            >
                                محصولات فعال این دسته‌بندی را مشاهده و بررسی
                                کنید.
                            </p>
                        </div>
                    </div>
                </header>

                <section
                    v-if="category.children.length"
                    class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-dh-100 md:p-7"
                >
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-dh-900">
                            زیر‌دسته‌ها
                        </h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="child in category.children"
                            :key="child.id"
                            :href="'/categories/' + child.slug"
                            class="rounded-2xl border border-dh-100 bg-dh-surface p-4 font-semibold text-dh-800 text-dh-muted transition hover:-translate-y-0.5 hover:border-dh-300 hover:bg-dh-50 dark:bg-dh-surface dark:hover:bg-white"
                        >
                            {{ child.name }}
                        </Link>
                    </div>
                </section>

                <section>
                    <div
                        class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <h2 class="text-2xl font-bold text-dh-900">
                                محصولات {{ category.name }}
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
                                    <span
                                        v-if="product.brand"
                                        class="text-xs text-dh-700"
                                    >
                                        {{ product.brand }}
                                    </span>
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
                        در این دسته‌بندی هنوز محصول فعالی ثبت نشده است.
                    </div>
                </section>

                <nav
                    v-if="pagination.last_page > 1"
                    aria-label="صفحات دسته‌بندی"
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
