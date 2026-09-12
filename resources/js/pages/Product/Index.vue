<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    short_description: string | null;
    brand: string | null;
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

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    products: Product[];
    categories: Category[];
    pagination: Pagination;
    filters: {
        search: string;
        category: string;
    };
}>();

const form = reactive({
    search: props.filters.search,
    category: props.filters.category,
});

function submit(): void {
    router.get('/products', form, {
        preserveState: true,
        replace: true,
    });
}

function goToPage(page: number): void {
    router.get(
        '/products',
        {
            ...form,
            page,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function formatPrice(value: number | null): string {
    return value === null
        ? 'تماس بگیرید'
        : `${value.toLocaleString('fa-IR')} تومان`;
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
    <Head title="محصولات" />

    <main dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl space-y-8">
            <header class="space-y-4">
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-indigo-600">
                            فروشگاه سلامت
                        </p>
                        <h1
                            class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"
                        >
                            محصولات
                        </h1>
                        <p
                            class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                        >
                            محصولات فعال فروشگاه را جستجو و بررسی کنید.
                        </p>
                    </div>
                    <a
                        href="/cart"
                        class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900"
                    >
                        مشاهده سبد خرید
                    </a>
                </div>

                <form
                    @submit.prevent="submit"
                    class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200 md:grid-cols-[1fr_220px_auto] dark:bg-gray-900 dark:ring-gray-800"
                >
                    <input
                        v-model="form.search"
                        type="search"
                        placeholder="نام محصول، SKU یا بارکد..."
                        class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    />
                    <select
                        v-model="form.category"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
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
                    <button
                        type="submit"
                        class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        جستجو
                    </button>
                </form>
            </header>

            <section
                v-if="products.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <article
                    v-for="product in products"
                    :key="product.id"
                    class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900 dark:ring-gray-800"
                >
                    <a :href="`/products/${product.slug}`" class="block">
                        <div class="aspect-square bg-gray-100 dark:bg-gray-800">
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.name"
                                class="h-full w-full object-contain p-6"
                            />
                            <div
                                v-else
                                class="flex h-full items-center justify-center text-sm text-gray-400"
                            >
                                بدون تصویر
                            </div>
                        </div>
                        <div class="space-y-3 p-4">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    v-if="product.brand"
                                    class="text-xs text-indigo-600"
                                    >{{ product.brand }}</span
                                >
                                <span
                                    v-if="product.category"
                                    class="text-xs text-gray-400"
                                    >{{ product.category }}</span
                                >
                            </div>
                            <h2
                                class="line-clamp-2 font-semibold text-gray-900 dark:text-white"
                            >
                                {{ product.name }}
                            </h2>
                            <p
                                v-if="product.short_description"
                                class="line-clamp-2 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ product.short_description }}
                            </p>
                        </div>
                    </a>
                    <div
                        class="flex items-center justify-between gap-3 border-t border-gray-100 p-4 dark:border-gray-800"
                    >
                        <div>
                            <div
                                class="font-bold text-gray-900 dark:text-white"
                            >
                                {{ formatPrice(product.price) }}
                            </div>
                            <div
                                v-if="
                                    product.compare_at_price &&
                                    product.compare_at_price >
                                        (product.price ?? 0)
                                "
                                class="text-xs text-gray-400 line-through"
                            >
                                {{ formatPrice(product.compare_at_price) }}
                            </div>
                        </div>
                        <button
                            v-if="product.available && product.price !== null"
                            type="button"
                            class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                            @click="addToCart(product.id)"
                        >
                            افزودن به سبد
                        </button>
                        <span v-else class="text-xs font-medium text-red-500"
                            >ناموجود</span
                        >
                    </div>
                </article>
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500 dark:border-gray-700"
            >
                محصولی مطابق جستجوی شما پیدا نشد.
            </section>

            <footer
                v-if="pagination.last_page > 1"
                class="flex flex-wrap items-center justify-center gap-2"
            >
                <button
                    v-for="page in pagination.last_page"
                    :key="page"
                    type="button"
                    class="min-w-10 rounded-xl px-3 py-2 text-sm font-medium"
                    :class="
                        page === pagination.current_page
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-800'
                    "
                    @click="goToPage(page)"
                >
                    {{ page.toLocaleString('fa-IR') }}
                </button>
            </footer>
        </div>
    </main>
</template>
