<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    short_description: string | null;
    brand: string | null;
    brand_slug: string | null;
    category: string | null;
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
const filtering = ref(false);
const addingProductId = ref<number | null>(null);
const addedProductId = ref<number | null>(null);

function addToCart(product: Product): void {
    if (
        addingProductId.value !== null ||
        !product.available ||
        product.price === null
    ) {
        return;
    }

    router.post(
        '/cart/items',
        { product_id: product.id, quantity: 1 },
        {
            preserveScroll: true,
            onStart: () => {
                addingProductId.value = product.id;
                addedProductId.value = null;
            },
            onSuccess: () => {
                addedProductId.value = product.id;
            },
            onFinish: () => { addingProductId.value = null; },
        },
    );
}

function submit(): void {
    router.get('/products', form, {
        preserveState: true,
        replace: true,
        onStart: () => { filtering.value = true; },
        onFinish: () => { filtering.value = false; },
    });
}

function clearFilters(): void {
    form.search = '';
    form.category = '';
    form.brand = '';

    router.get(
        '/products',
        {},
        {
            preserveState: true,
            replace: true,
            onStart: () => { filtering.value = true; },
            onFinish: () => { filtering.value = false; },
        },
    );
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

function formatPrice(value: number | null): string {
    return value === null
        ? 'تماس بگیرید'
        : `${value.toLocaleString('fa-IR')} تومان`;
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

    <div dir="rtl" class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10">
        <header class="sticky top-0 z-40 border-b border-dh-100 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <Link href="/" class="flex items-center gap-3" aria-label="داروخونه">
                        <span class="flex size-11 items-center justify-center rounded-2xl bg-dh-50 ring-1 ring-dh-100">
                            <svg viewBox="0 0 48 48" class="size-7 text-dh-600" fill="none" aria-hidden="true">
                                <path d="M13 27.5 27.5 13a7.5 7.5 0 0 1 10.6 10.6L23.6 38.1A7.5 7.5 0 0 1 13 27.5Z" fill="currentColor" opacity=".16"/>
                                <path d="M16.2 31.8 31.8 16.2M19.7 28.3l10 10M28.3 19.7l-10-10" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                                <path d="M15.1 21.5c-3.4-2.4-4.1-6.8-1.8-9.4 2.5-2.8 6.9-2.3 9.3 1.1" stroke="#63b95b" stroke-width="2.6" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-lg font-extrabold text-dh-800">داروخونه</span>
                            <span class="mt-1 block text-[10px] font-medium text-dh-muted">دارو و محصولات بهداشتی</span>
                        </span>
                    </Link>
                    <div class="flex items-center gap-2">
                        <Link href="/" class="hidden rounded-xl px-3 py-2 text-sm font-bold text-dh-700 hover:bg-dh-50 sm:block">خانه</Link>
                        <Link href="/cart" class="rounded-xl border border-dh-100 px-3 py-2 text-sm font-bold text-dh-700 hover:bg-dh-50">سبد خرید</Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <section class="flex flex-col gap-5 rounded-3xl border border-dh-100 bg-white p-6 shadow-[0_12px_35px_rgba(20,108,114,0.06)] sm:p-8 md:flex-row md:items-end md:justify-between">
                <div>
                    <span class="text-xs font-extrabold text-dh-500">کاتالوگ داروخونه</span>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-dh-900 sm:text-4xl">محصولات</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-dh-muted">محصول موردنظر را جستجو کنید یا از دسته‌بندی و برند کمک بگیرید.</p>
                </div>
                <div class="rounded-2xl bg-dh-50 px-4 py-3 text-sm font-bold text-dh-700">
                    {{ pagination.total.toLocaleString('fa-IR') }} محصول
                </div>
            </section>

            <section class="rounded-3xl border border-dh-100 bg-white p-4 shadow-sm sm:p-5">
                <form @submit.prevent="submit" class="grid gap-3 md:grid-cols-[1fr_210px_210px_auto]">
                    <label class="relative block">
                        <span class="sr-only">جستجوی محصول</span>
                        <svg viewBox="0 0 24 24" class="pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2 text-dh-500" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <input
                            v-model="form.search"
                            type="search"
                            enterkeyhint="search"
                            placeholder="نام محصول، SKU یا بارکد..."
                            class="h-12 w-full rounded-2xl border border-dh-100 bg-dh-50/60 pr-12 pl-4 text-sm outline-none transition placeholder:text-dh-muted focus:border-dh-300 focus:bg-white focus:ring-4 focus:ring-dh-100"
                        />
                    </label>

                    <label>
                        <span class="sr-only">دسته‌بندی</span>
                        <select v-model="form.category" class="h-12 w-full rounded-2xl border border-dh-100 bg-white px-4 text-sm outline-none focus:border-dh-300 focus:ring-4 focus:ring-dh-100">
                            <option value="">همه دسته‌ها</option>
                            <option v-for="category in categories" :key="category.id" :value="category.slug">{{ category.name }}</option>
                        </select>
                    </label>

                    <label>
                        <span class="sr-only">برند</span>
                        <select v-model="form.brand" class="h-12 w-full rounded-2xl border border-dh-100 bg-white px-4 text-sm outline-none focus:border-dh-300 focus:ring-4 focus:ring-dh-100">
                            <option value="">همه برندها</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.slug">{{ brand.name }}</option>
                        </select>
                    </label>

                    <div class="flex gap-2">
                        <button type="submit" class="h-12 flex-1 rounded-2xl bg-dh-600 px-5 text-sm font-extrabold text-white transition hover:bg-dh-700 disabled:cursor-not-allowed disabled:opacity-60" :disabled="filtering"><span v-if="filtering" class="inline-flex items-center gap-2"><svg viewBox="0 0 24 24" class="size-4 animate-spin" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" opacity="0.25"/><path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>در حال جستجو…</span><span v-else>جستجو</span></button>
                        <button type="button" class="h-12 rounded-2xl border border-dh-100 px-4 text-sm font-bold text-dh-700 transition hover:bg-dh-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="filtering" @click="clearFilters">حذف فیلتر</button>
                    </div>
                </form>

                <div v-if="form.search || form.category || form.brand" class="mt-4 flex flex-wrap items-center gap-2 text-xs font-bold text-dh-muted">
                    <span>فیلتر فعال:</span>
                    <span v-if="form.search" class="rounded-full bg-dh-50 px-3 py-1.5 text-dh-700">جستجو: {{ form.search }}</span>
                    <span v-if="form.category" class="rounded-full bg-dh-green-50 px-3 py-1.5 text-dh-green-700">دسته‌بندی انتخاب شده</span>
                    <span v-if="form.brand" class="rounded-full bg-dh-50 px-3 py-1.5 text-dh-700">برند انتخاب شده</span>
                </div>
            </section>

            <div v-if="products.length" class="flex flex-wrap items-center justify-between gap-3 text-xs font-bold text-dh-muted">
                <span>{{ pagination.from?.toLocaleString('fa-IR') }} تا {{ pagination.to?.toLocaleString('fa-IR') }} از {{ pagination.total.toLocaleString('fa-IR') }} محصول</span>
                <span v-if="form.search || form.category || form.brand" class="rounded-full bg-dh-50 px-3 py-1.5 text-dh-700">نتایج فیلترشده</span>
            </div>

            <section v-if="products.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <article
                    v-for="product in products"
                    :key="product.id"
                    class="group overflow-hidden rounded-3xl border border-dh-100 bg-white transition duration-200 hover:-translate-y-1 hover:border-dh-200 hover:shadow-[0_18px_40px_rgba(20,108,114,0.09)]"
                >
                    <Link :href="`/products/${product.slug}`" class="block">
                        <div class="relative aspect-square overflow-hidden bg-dh-50/60">
                            <span v-if="product.compare_at_price && product.compare_at_price > (product.price ?? 0)" class="absolute right-3 top-3 rounded-full bg-dh-green-500 px-2.5 py-1 text-[11px] font-extrabold text-white">تخفیف</span>
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.name"
                                loading="lazy"
                                class="h-full w-full object-contain p-6 transition duration-300 group-hover:scale-[1.03]"
                            />
                            <div v-else class="flex h-full items-center justify-center text-sm font-medium text-dh-muted">بدون تصویر</div>
                        </div>

                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span v-if="product.brand" class="font-bold text-dh-600">{{ product.brand }}</span>
                                <span v-if="product.category" class="text-dh-muted">{{ product.category }}</span>
                            </div>
                            <h2 class="line-clamp-2 min-h-12 text-sm font-extrabold leading-6 text-dh-900">{{ product.name }}</h2>
                            <p v-if="product.short_description" class="line-clamp-2 text-xs leading-6 text-dh-muted">{{ product.short_description }}</p>
                        </div>
                    </Link>

                    <div class="border-t border-dh-50 p-4">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <div class="font-extrabold text-dh-900">{{ formatPrice(product.price) }}</div>
                                <div v-if="product.compare_at_price && product.compare_at_price > (product.price ?? 0)" class="mt-1 text-xs text-dh-muted line-through">{{ formatPrice(product.compare_at_price) }}</div>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="product.available ? 'bg-dh-green-50 text-dh-green-700' : 'bg-dh-100 text-dh-muted'">
                                {{ product.available ? 'موجود' : 'ناموجود' }}
                            </span>
                        </div>
                        <button
                            v-if="product.available && product.price !== null"
                            type="button"
                            class="mt-4 w-full rounded-xl bg-dh-600 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-dh-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="addingProductId !== null"
                            :aria-busy="addingProductId === product.id"
                            @click="addToCart(product)"
                        >
                            <span v-if="addingProductId === product.id" class="inline-flex items-center gap-2"><svg viewBox="0 0 24 24" class="size-4 animate-spin" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" opacity="0.25"/><path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>در حال افزودن…</span><span v-else>افزودن به سبد خرید</span>
                        </button>
                        <div v-if="addedProductId === product.id" class="mt-2 rounded-xl bg-dh-green-50 px-3 py-2 text-center text-xs font-bold text-dh-green-700" role="status">
                            به سبد خرید اضافه شد.
                        </div>
                    </div>
                </article>
            </section>

            <section v-else class="rounded-3xl border border-dashed border-dh-200 bg-white p-12 text-center">
                <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-dh-50 text-dh-600">
                    <svg viewBox="0 0 24 24" class="size-7" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.7"/>
                        <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-xl font-extrabold text-dh-900">محصولی پیدا نشد</h2>
                <p class="mt-2 text-sm leading-7 text-dh-muted">عبارت جستجو یا فیلترهای انتخابی را تغییر دهید.</p>
                <button type="button" class="mt-5 rounded-xl bg-dh-600 px-5 py-3 text-sm font-extrabold text-white hover:bg-dh-700" @click="clearFilters">نمایش همه محصولات</button>
            </section>

            <nav v-if="pagination.last_page > 1" aria-label="صفحات محصولات" class="flex flex-wrap items-center justify-center gap-2 pb-8">
                <Link
                    v-if="pagination.current_page > 1"
                    :href="pageUrl(pagination.current_page - 1)"
                    preserve-scroll
                    aria-label="صفحه قبلی"
                    class="rounded-xl bg-white px-4 py-2 text-sm font-bold text-dh-700 ring-1 ring-dh-100 hover:bg-dh-50"
                >
                    قبلی
                </Link>

                <Link
                    v-for="page in pagination.last_page"
                    :key="page"
                    :href="pageUrl(page)"
                    preserve-scroll
                    :aria-current="page === pagination.current_page ? 'page' : undefined"
                    :aria-label="`صفحه ${page.toLocaleString('fa-IR')}`"
                    class="min-w-10 rounded-xl px-3 py-2 text-center text-sm font-bold"
                    :class="page === pagination.current_page ? 'bg-dh-600 text-white' : 'bg-white text-dh-700 ring-1 ring-dh-100 hover:bg-dh-50'"
                >
                    {{ page.toLocaleString('fa-IR') }}
                </Link>

                <Link
                    v-if="pagination.current_page < pagination.last_page"
                    :href="pageUrl(pagination.current_page + 1)"
                    preserve-scroll
                    aria-label="صفحه بعدی"
                    class="rounded-xl bg-white px-4 py-2 text-sm font-bold text-dh-700 ring-1 ring-dh-100 hover:bg-dh-50"
                >
                    بعدی
                </Link>
            </nav>
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-dh-100 bg-white/95 px-3 py-2 backdrop-blur lg:hidden" aria-label="ناوبری موبایل"><div class="mx-auto grid max-w-md grid-cols-3 gap-2 text-center text-[11px] font-bold text-dh-muted"><Link href="/" class="rounded-xl px-2 py-2">خانه</Link><Link href="/products" class="rounded-xl bg-dh-50 px-2 py-2 text-dh-700">فروشگاه</Link><Link href="/cart" class="rounded-xl px-2 py-2">سبد خرید</Link></div></nav>
    </div>
</template>
