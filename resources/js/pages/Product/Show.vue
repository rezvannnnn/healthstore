<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface ProductImage {
    id: number;
    path: string;
    alt: string;
}
interface RelatedProduct {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    price: number | null;
}
interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    barcode: string | null;
    product_type: string | null;
    unit: string | null;
    quantity_per_unit: number | null;
    short_description: string | null;
    description: string | null;
    specifications: Record<string, unknown> | null;
    brand: string | null;
    category: string | null;
    category_slug: string | null;
    image: string | null;
    images: ProductImage[];
    price: number | null;
    compare_at_price: number | null;
    available_quantity: number;
    available: boolean;
}
interface Seo {
    title: string;
    description: string | null;
    canonical: string;
}

const props = defineProps<{
    product: Product;
    relatedProducts: RelatedProduct[];
    seo: Seo;
}>();
const selectedImage = ref(
    props.product.images[0]?.path || props.product.image || null,
);
const quantity = ref(1);
const gallery = computed(() => {
    const images = props.product.images.map((image) => image.path);
    if (props.product.image && !images.includes(props.product.image)) {
        images.unshift(props.product.image);
    }
    return images;
});
function formatPrice(value: number | null): string {
    return value === null
        ? 'تماس بگیرید'
        : `${value.toLocaleString('fa-IR')} تومان`;
}
function addToCart(): void {
    router.post(
        '/cart/items',
        { product_id: props.product.id, quantity: quantity.value },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="seo.title">
        <meta
            v-if="seo.description"
            name="description"
            :content="seo.description"
        />
        <link rel="canonical" :href="seo.canonical" />
    </Head>

    <main dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-6xl space-y-8">
            <nav class="text-sm text-gray-500 dark:text-gray-400">
                <Link href="/products" class="hover:text-indigo-600"
                    >محصولات</Link
                ><span class="mx-2">/</span><span>{{ product.name }}</span>
            </nav>
            <section
                class="grid gap-8 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200 md:grid-cols-2 md:p-8 dark:bg-gray-900 dark:ring-gray-800"
            >
                <div class="space-y-4">
                    <div
                        class="aspect-square rounded-2xl bg-gray-100 dark:bg-gray-800"
                    >
                        <img
                            v-if="selectedImage"
                            :src="selectedImage"
                            :alt="product.name"
                            class="h-full w-full object-contain p-8"
                        />
                        <div
                            v-else
                            class="flex h-full items-center justify-center text-gray-400"
                        >
                            بدون تصویر
                        </div>
                    </div>
                    <div
                        v-if="gallery.length > 1"
                        class="grid grid-cols-5 gap-2"
                    >
                        <button
                            v-for="image in gallery"
                            :key="image"
                            type="button"
                            class="aspect-square rounded-xl bg-gray-100 p-1 ring-2 dark:bg-gray-800"
                            :class="
                                selectedImage === image
                                    ? 'ring-indigo-500'
                                    : 'ring-transparent'
                            "
                            @click="selectedImage = image"
                        >
                            <img
                                :src="image"
                                :alt="product.name"
                                class="h-full w-full object-contain"
                            />
                        </button>
                    </div>
                </div>
                <div class="flex flex-col justify-center">
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span
                            v-if="product.brand"
                            class="rounded-full bg-indigo-50 px-3 py-1 font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                            >{{ product.brand }}</span
                        >
                        <Link
                            v-if="product.category_slug"
                            :href="`/products?category=${encodeURIComponent(product.category_slug)}`"
                            class="rounded-full bg-gray-100 px-3 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            >{{ product.category }}</Link
                        >
                    </div>
                    <h1
                        class="mt-4 text-3xl leading-tight font-bold text-gray-900 dark:text-white"
                    >
                        {{ product.name }}
                    </h1>
                    <p
                        v-if="product.short_description"
                        class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-300"
                    >
                        {{ product.short_description }}
                    </p>
                    <div
                        class="mt-8 rounded-2xl bg-gray-50 p-5 dark:bg-gray-950"
                    >
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <div
                                    class="text-2xl font-bold text-gray-900 dark:text-white"
                                >
                                    {{ formatPrice(product.price) }}
                                </div>
                                <div
                                    v-if="
                                        product.compare_at_price &&
                                        product.compare_at_price >
                                            (product.price ?? 0)
                                    "
                                    class="mt-1 text-sm text-gray-400 line-through"
                                >
                                    {{ formatPrice(product.compare_at_price) }}
                                </div>
                            </div>
                            <div
                                v-if="product.available"
                                class="text-sm font-medium text-emerald-600"
                            >
                                {{
                                    product.available_quantity.toLocaleString(
                                        'fa-IR',
                                    )
                                }}
                                عدد موجود
                            </div>
                            <div
                                v-else
                                class="text-sm font-medium text-red-500"
                            >
                                ناموجود
                            </div>
                        </div>
                        <div
                            v-if="product.available && product.price !== null"
                            class="mt-5 flex gap-3"
                        >
                            <div
                                class="flex items-center rounded-xl border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900"
                            >
                                <button
                                    type="button"
                                    class="px-3 py-2 text-lg"
                                    @click="
                                        quantity = Math.max(1, quantity - 1)
                                    "
                                >
                                    −
                                </button>
                                <span class="min-w-10 text-center">{{
                                    quantity
                                }}</span>
                                <button
                                    type="button"
                                    class="px-3 py-2 text-lg"
                                    @click="
                                        quantity = Math.min(
                                            product.available_quantity,
                                            quantity + 1,
                                        )
                                    "
                                >
                                    +
                                </button>
                            </div>
                            <button
                                type="button"
                                class="flex-1 rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700"
                                @click="addToCart"
                            >
                                افزودن به سبد
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="product.description"
                        class="mt-8 leading-8 whitespace-pre-line text-gray-700 dark:text-gray-300"
                    >
                        {{ product.description }}
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
