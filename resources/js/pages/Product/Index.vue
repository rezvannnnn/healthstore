<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import ProductCard from '../../components/ProductCard.vue';
import StorefrontLayout from '../../components/StorefrontLayout.vue';

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

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Brand {
    id: number;
    name: string;
    slug: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Seo {
    title: string;
    description: string;
    canonical: string;
}

const props = defineProps<{
    seo: Seo;
    products: Product[];
    categories: Category[];
    brands: Brand[];
    pagination: Pagination;
    filters: {
        search: string;
        category: string;
        brand: string;
    };
}>();

const form = reactive({
    search: props.filters.search,
    category: props.filters.category,
    brand: props.filters.brand,
});

function submit(): void {
    router.get('/products', form, {
        preserveState: true,
        replace: true,
    });
}

function pageUrl(page: number): string {
    const params = new URLSearchParams();

    if (page > 1) {
        params.set('page', String(page));
    }

    if (form.search) {
        params.set('search', form.search);
    }

    if (form.category) {
        params.set('category', form.category);
    }

    if (form.brand) {
        params.set('brand', form.brand);
    }

    const query = params.toString();
    return query ? `/products?${query}` : '/products';
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
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" :content="seo.title" />
        <meta name="twitter:description" :content="seo.description" />
    </Head>

    <StorefrontLayout>
        <section class="border-b border-[#dce9ec] bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <span class="text-xs font-bold text-[#0a8d9f]"
                            >فروشگاه داروخونه</span
                        >
                        <h1 class="mt-1 text-3xl font-black text-[#04547b]">
                            محصولات
                        </h1>
                        <p class="mt-2 text-sm leading-7 text-slate-500">
                            محصول موردنظرت را بر اساس نام، برند یا دسته‌بندی
                            پیدا کن.
                        </p>
                    </div>
                    <Link
                        href="/cart"
                        class="inline-flex w-fit items-center rounded-xl border border-[#cfe0e5] bg-white px-4 py-2.5 text-sm font-bold text-[#04547b] transition hover:bg-[#f5fbfb]"
                    >
                        مشاهده سبد خرید
                    </Link>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <form
                @submit.prevent="submit"
                class="rounded-3xl border border-[#dce9ec] bg-white p-4 shadow-[0_10px_35px_rgba(4,84,123,0.06)]"
            >
                <div class="grid gap-3 lg:grid-cols-[1fr_210px_210px_auto]">
                    <div
                        class="relative rounded-2xl border border-[#cfe0e5] bg-[#f7fbfc] focus-within:border-[#0c93a4] focus-within:ring-4 focus-within:ring-[#0c93a4]/10"
                    >
                        <label class="sr-only" for="product-search"
                            >جستجوی محصول</label
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
                            id="product-search"
                            v-model="form.search"
                            type="search"
                            placeholder="نام محصول، برند، SKU یا بارکد..."
                            class="w-full rounded-2xl bg-transparent py-3.5 pr-12 pl-4 text-sm outline-none placeholder:text-slate-400"
                        />
                    </div>
                    <select
                        v-model="form.category"
                        class="rounded-2xl border border-[#cfe0e5] bg-white px-4 py-3.5 text-sm text-slate-700 outline-none focus:border-[#0c93a4] focus:ring-4 focus:ring-[#0c93a4]/10"
                    >
                        <option value="">همه دسته‌ها</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.slug"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <select
                        v-model="form.brand"
                        class="rounded-2xl border border-[#cfe0e5] bg-white px-4 py-3.5 text-sm text-slate-700 outline-none focus:border-[#0c93a4] focus:ring-4 focus:ring-[#0c93a4]/10"
                    >
                        <option value="">همه برندها</option>
                        <option
                            v-for="brand in brands"
                            :key="brand.id"
                            :value="brand.slug"
                        >
                            {{ brand.name }}
                        </option>
                    </select>
                    <button
                        type="submit"
                        class="rounded-2xl bg-[#04547b] px-6 py-3.5 text-sm font-bold text-white transition hover:bg-[#034664]"
                    >
                        جستجوی محصولات
                    </button>
                </div>
            </form>
        </section>

        <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
            <div
                class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-sm text-slate-500">
                    نمایش
                    <span class="font-bold text-slate-700">{{
                        pagination.from?.toLocaleString('fa-IR') || '۰'
                    }}</span>
                    تا
                    <span class="font-bold text-slate-700">{{
                        pagination.to?.toLocaleString('fa-IR') || '۰'
                    }}</span>
                    از
                    <span class="font-bold text-slate-700">{{
                        pagination.total.toLocaleString('fa-IR')
                    }}</span>
                    محصول
                </div>
                <div
                    v-if="form.search || form.category || form.brand"
                    class="text-xs font-semibold text-[#0a8d9f]"
                >
                    فیلتر فعال است
                </div>
            </div>

            <section
                v-if="products.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                    @add-to-cart="addToCart"
                />
            </section>

            <section
                v-else
                class="rounded-[2rem] border border-dashed border-[#bcd5da] bg-white px-6 py-16 text-center"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#eef8f8] text-xl font-black text-[#0a8d9f]"
                >
                    ?
                </div>
                <h2 class="mt-5 text-xl font-black text-slate-800">
                    محصولی پیدا نشد
                </h2>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-7 text-slate-500">
                    عبارت جستجو یا فیلترها را تغییر بده و دوباره امتحان کن.
                </p>
                <Link
                    href="/products"
                    class="mt-5 inline-flex rounded-2xl bg-[#04547b] px-5 py-3 text-sm font-bold text-white"
                >
                    نمایش همه محصولات
                </Link>
            </section>

            <nav
                v-if="pagination.last_page > 1"
                aria-label="صفحات محصولات"
                class="mt-8 flex flex-wrap items-center justify-center gap-2"
            >
                <Link
                    v-if="pagination.current_page > 1"
                    :href="pageUrl(pagination.current_page - 1)"
                    preserve-scroll
                    aria-label="صفحه قبلی"
                    class="rounded-xl border border-[#dce9ec] bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-[#f6fbfb]"
                >
                    قبلی
                </Link>
                <Link
                    v-for="page in pagination.last_page"
                    :key="page"
                    :href="pageUrl(page)"
                    preserve-scroll
                    :aria-current="
                        page === pagination.current_page ? 'page' : undefined
                    "
                    :aria-label="`صفحه ${page.toLocaleString('fa-IR')}`"
                    class="min-w-10 rounded-xl px-3 py-2.5 text-center text-sm font-bold"
                    :class="
                        page === pagination.current_page
                            ? 'bg-[#04547b] text-white'
                            : 'border border-[#dce9ec] bg-white text-slate-600 hover:bg-[#f6fbfb]'
                    "
                >
                    {{ page.toLocaleString('fa-IR') }}
                </Link>
                <Link
                    v-if="pagination.current_page < pagination.last_page"
                    :href="pageUrl(pagination.current_page + 1)"
                    preserve-scroll
                    aria-label="صفحه بعدی"
                    class="rounded-xl border border-[#dce9ec] bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-[#f6fbfb]"
                >
                    بعدی
                </Link>
            </nav>
        </section>
    </StorefrontLayout>
</template>
