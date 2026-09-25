<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import StorefrontHeader from '@/components/StorefrontHeader.vue';
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
    brand_slug: string | null;
    category: string | null;
    category_slug: string | null;
    images: ProductImage[];
    image: string | null;
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
const addingToCart = ref(false);
const cartAdded = ref(false);
const gallery = computed(() => {
    const images = props.product.images.map((image) => image.path);
    if (props.product.image && !images.includes(props.product.image)) {
        images.unshift(props.product.image);
    }
    return images;
});
const specifications = computed(() =>
    Object.entries(props.product.specifications ?? {}).filter(
        ([key, value]) => key.trim() && value !== null && value !== '',
    ),
);
function formatPrice(value: number | null): string {
    return value === null
        ? 'تماس بگیرید'
        : `${value.toLocaleString('fa-IR')} تومان`;
}
function addToCart(): void {
    if (
        addingToCart.value ||
        props.product.available_quantity < 1 ||
        props.product.price === null
    ) {
        return;
    }

    router.post(
        '/cart/items',
        { product_id: props.product.id, quantity: quantity.value },
        {
            preserveScroll: true,
            onStart: () => {
                addingToCart.value = true;
                cartAdded.value = false;
            },
            onSuccess: () => {
                cartAdded.value = true;
            },
            onFinish: () => {
                addingToCart.value = false;
            },
        },
    );
}
</script>

<template>
    <div dir="rtl" class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10">
        <StorefrontHeader active="products" />

        <main
            class="mx-auto max-w-7xl px-4 py-6 pb-28 sm:px-6 sm:py-8 lg:px-8 lg:pb-12"
        >
            <nav
                class="mb-6 flex flex-wrap items-center gap-2 text-sm text-dh-muted"
                aria-label="مسیر صفحه"
            >
                <Link href="/" class="transition hover:text-dh-700">خانه</Link>
                <span>/</span>
                <Link href="/products" class="transition hover:text-dh-700"
                    >محصولات</Link
                >
                <template v-if="product.category_slug">
                    <span>/</span>
                    <Link
                        :href="'/categories/' + product.category_slug"
                        class="transition hover:text-dh-700"
                    >
                        {{ product.category }}
                    </Link>
                </template>
                <span>/</span>
                <span class="line-clamp-1 font-medium text-dh-800">{{
                    product.name
                }}</span>
            </nav>

            <section
                class="overflow-hidden rounded-[2rem] border border-dh-100 bg-white shadow-[0_16px_50px_rgba(20,108,114,0.07)]"
            >
                <div class="grid lg:grid-cols-[1.02fr_0.98fr]">
                    <div
                        class="border-b border-dh-100 p-4 sm:p-6 lg:border-b-0 lg:border-l lg:p-8"
                    >
                        <div
                            class="relative aspect-square overflow-hidden rounded-[1.6rem] bg-gradient-to-br from-dh-50 via-white to-dh-green-50"
                        >
                            <span
                                v-if="
                                    product.compare_at_price &&
                                    product.price !== null &&
                                    product.compare_at_price > product.price
                                "
                                class="absolute top-4 right-4 z-10 rounded-full bg-dh-green-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm"
                            >
                                پیشنهاد ویژه
                            </span>

                            <img
                                v-if="selectedImage"
                                :src="selectedImage"
                                :alt="product.name"
                                class="h-full w-full object-contain p-8 sm:p-12"
                            />
                            <div
                                v-else
                                class="flex h-full flex-col items-center justify-center gap-3 text-dh-muted"
                            >
                                <span
                                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-8 w-8"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <rect
                                            x="3"
                                            y="4"
                                            width="18"
                                            height="16"
                                            rx="2"
                                        />
                                        <path
                                            d="M7 15l3-3 3 3 2-2 3 3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </span>
                                بدون تصویر
                            </div>
                        </div>

                        <div
                            v-if="gallery.length > 1"
                            class="mt-4 grid grid-cols-5 gap-2.5 sm:gap-3"
                        >
                            <button
                                v-for="(image, index) in gallery"
                                :key="image"
                                type="button"
                                class="aspect-square overflow-hidden rounded-2xl border bg-white p-1.5 transition"
                                :class="
                                    selectedImage === image
                                        ? 'border-dh-600 ring-2 ring-dh-100'
                                        : 'border-dh-100 hover:border-dh-300'
                                "
                                :aria-label="'تصویر ' + (index + 1)"
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

                    <div class="flex flex-col p-5 sm:p-7 lg:p-10">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                v-if="product.brand && product.brand_slug"
                                :href="'/brands/' + product.brand_slug"
                                class="rounded-full bg-dh-50 px-3 py-1.5 text-xs font-bold text-dh-700 transition hover:bg-dh-100"
                            >
                                {{ product.brand }}
                            </Link>
                            <span
                                v-if="product.product_type"
                                class="rounded-full bg-dh-green-50 px-3 py-1.5 text-xs font-semibold text-dh-green-700"
                            >
                                {{ product.product_type }}
                            </span>
                        </div>

                        <h1
                            class="mt-5 text-2xl leading-[1.35] font-extrabold text-dh-900 sm:text-3xl lg:text-[2.15rem]"
                        >
                            {{ product.name }}
                        </h1>

                        <p
                            v-if="product.short_description"
                            class="mt-4 text-sm leading-7 text-dh-muted sm:text-base"
                        >
                            {{ product.short_description }}
                        </p>

                        <div class="my-7 h-px bg-dh-100"></div>

                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <div class="text-xs font-medium text-dh-muted">
                                    قیمت مصرف‌کننده
                                </div>
                                <div
                                    class="mt-1 text-2xl font-extrabold text-dh-800 sm:text-3xl"
                                >
                                    {{ formatPrice(product.price) }}
                                </div>
                                <div
                                    v-if="
                                        product.compare_at_price &&
                                        product.price !== null &&
                                        product.compare_at_price > product.price
                                    "
                                    class="mt-1.5 text-sm text-dh-muted line-through"
                                >
                                    {{ formatPrice(product.compare_at_price) }}
                                </div>
                            </div>

                            <div
                                v-if="product.available"
                                class="flex items-center gap-1.5 rounded-full bg-dh-green-50 px-3 py-2 text-xs font-bold text-dh-green-700"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-dh-green-500"
                                ></span>
                                موجود در انبار
                            </div>
                            <div
                                v-else
                                class="rounded-full bg-red-50 px-3 py-2 text-xs font-bold text-red-600"
                            >
                                ناموجود
                            </div>
                        </div>

                        <div
                            v-if="product.available && product.price !== null"
                            class="mt-6 rounded-2xl bg-dh-surface p-3 sm:p-4"
                        >
                            <div class="flex gap-3">
                                <div
                                    class="flex h-12 items-center rounded-xl border border-dh-100 bg-white"
                                >
                                    <button
                                        type="button"
                                        class="flex h-full w-10 items-center justify-center text-xl text-dh-700 transition hover:bg-dh-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        aria-label="کاهش تعداد"
                                        :disabled="addingToCart"
                                        @click="
                                            quantity = Math.max(1, quantity - 1)
                                        "
                                    >
                                        −
                                    </button>
                                    <span
                                        class="w-9 text-center text-sm font-bold"
                                        >{{ quantity }}</span
                                    >
                                    <button
                                        type="button"
                                        class="flex h-full w-10 items-center justify-center text-xl text-dh-700 transition hover:bg-dh-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        aria-label="افزایش تعداد"
                                        :disabled="addingToCart"
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
                                    class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-dh-700 px-5 font-bold text-white shadow-lg shadow-dh-700/15 transition hover:-translate-y-0.5 hover:bg-dh-800 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="
                                        addingToCart ||
                                        product.available_quantity < 1 ||
                                        product.price === null
                                    "
                                    :aria-busy="addingToCart"
                                    @click="addToCart"
                                >
                                    <svg
                                        v-if="addingToCart"
                                        viewBox="0 0 24 24"
                                        class="h-5 w-5 animate-spin"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="9"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            opacity="0.25"
                                        />
                                        <path
                                            d="M21 12a9 9 0 0 1-9 9"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        viewBox="0 0 24 24"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M4 5h2l1.5 10.2a2 2 0 0 0 2 1.8h7.6a2 2 0 0 0 1.9-1.5L21 8H7"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <circle cx="10" cy="20" r="1" />
                                        <circle cx="18" cy="20" r="1" />
                                    </svg>
                                    {{
                                        addingToCart
                                            ? 'در حال افزودن…'
                                            : 'افزودن به سبد خرید'
                                    }}
                                </button>
                            </div>
                            <p
                                class="mt-2 text-center text-[11px] text-dh-muted"
                            >
                                {{
                                    product.available_quantity.toLocaleString(
                                        'fa-IR',
                                    )
                                }}
                                عدد قابل سفارش است.
                            </p>
                            <div
                                v-if="cartAdded"
                                class="mt-3 rounded-xl bg-dh-green-50 px-3 py-2 text-center text-xs font-bold text-dh-green-700"
                                role="status"
                            >
                                محصول با موفقیت به سبد خرید اضافه شد.
                            </div>
                        </div>

                        <div class="mt-6 grid gap-2.5 sm:grid-cols-3">
                            <div
                                class="rounded-2xl border border-dh-100 bg-white p-3"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-5 w-5 text-dh-700"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                >
                                    <path
                                        d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3Z"
                                    />
                                    <path
                                        d="M9 12l2 2 4-4"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                <div class="mt-2 text-xs font-bold text-dh-800">
                                    خرید مطمئن
                                </div>
                                <div
                                    class="mt-1 text-[11px] leading-5 text-dh-muted"
                                >
                                    اطلاعات شفاف محصول
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-dh-100 bg-white p-3"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-5 w-5 text-dh-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                >
                                    <path
                                        d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"
                                        stroke-linejoin="round"
                                    />
                                    <circle cx="7" cy="18" r="1.5" />
                                    <circle cx="18" cy="18" r="1.5" />
                                </svg>
                                <div class="mt-2 text-xs font-bold text-dh-800">
                                    ارسال سفارش
                                </div>
                                <div
                                    class="mt-1 text-[11px] leading-5 text-dh-muted"
                                >
                                    پیگیری ساده و شفاف
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-dh-100 bg-white p-3"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-5 w-5 text-dh-700"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                >
                                    <path
                                        d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"
                                    />
                                    <path
                                        d="M12 7v5l3 2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <div class="mt-2 text-xs font-bold text-dh-800">
                                    پشتیبانی
                                </div>
                                <div
                                    class="mt-1 text-[11px] leading-5 text-dh-muted"
                                >
                                    همراه شما در خرید
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="product.description"
                            class="mt-7 border-t border-dh-100 pt-6"
                        >
                            <h2 class="text-lg font-extrabold text-dh-900">
                                درباره محصول
                            </h2>
                            <p
                                class="mt-3 text-sm leading-8 whitespace-pre-line text-dh-muted"
                            >
                                {{ product.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="specifications.length"
                class="mt-6 rounded-[2rem] border border-dh-100 bg-white p-5 shadow-[0_12px_40px_rgba(20,108,114,0.05)] sm:p-7 lg:p-8"
            >
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold text-dh-green-600"
                            >جزئیات محصول</span
                        >
                        <h2 class="mt-1 text-2xl font-extrabold text-dh-900">
                            مشخصات فنی
                        </h2>
                    </div>
                </div>

                <dl
                    class="mt-6 overflow-hidden rounded-2xl border border-dh-100"
                >
                    <div
                        v-for="[key, value] in specifications"
                        :key="key"
                        class="grid gap-2 border-b border-dh-100 px-4 py-4 last:border-b-0 sm:grid-cols-[220px_1fr] sm:px-5"
                    >
                        <dt class="text-sm font-bold text-dh-muted">
                            {{ key }}
                        </dt>
                        <dd class="text-sm font-medium break-words text-dh-800">
                            {{
                                typeof value === 'object'
                                    ? JSON.stringify(value)
                                    : value
                            }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section
                v-if="
                    product.sku ||
                    product.barcode ||
                    product.unit ||
                    product.quantity_per_unit ||
                    product.product_type
                "
                class="mt-6 rounded-[2rem] border border-dh-100 bg-white p-5 shadow-[0_12px_40px_rgba(20,108,114,0.05)] sm:p-7 lg:p-8"
            >
                <h2 class="text-xl font-extrabold text-dh-900">
                    اطلاعات محصول
                </h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-if="product.sku"
                        class="rounded-2xl bg-dh-surface p-4"
                    >
                        <div class="text-xs text-dh-muted">کد کالا</div>
                        <div class="mt-1 font-bold text-dh-800">
                            {{ product.sku }}
                        </div>
                    </div>
                    <div
                        v-if="product.barcode"
                        class="rounded-2xl bg-dh-surface p-4"
                    >
                        <div class="text-xs text-dh-muted">بارکد</div>
                        <div class="mt-1 font-bold text-dh-800">
                            {{ product.barcode }}
                        </div>
                    </div>
                    <div
                        v-if="product.unit"
                        class="rounded-2xl bg-dh-surface p-4"
                    >
                        <div class="text-xs text-dh-muted">واحد</div>
                        <div class="mt-1 font-bold text-dh-800">
                            {{ product.unit }}
                        </div>
                    </div>
                    <div
                        v-if="product.quantity_per_unit"
                        class="rounded-2xl bg-dh-surface p-4"
                    >
                        <div class="text-xs text-dh-muted">تعداد در واحد</div>
                        <div class="mt-1 font-bold text-dh-800">
                            {{ product.quantity_per_unit }}
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="relatedProducts.length"
                class="mt-6 rounded-[2rem] border border-dh-100 bg-white p-5 shadow-[0_12px_40px_rgba(20,108,114,0.05)] sm:p-7 lg:p-8"
            >
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold text-dh-green-600"
                            >پیشنهاد برای شما</span
                        >
                        <h2 class="mt-1 text-2xl font-extrabold text-dh-900">
                            محصولات مرتبط
                        </h2>
                    </div>
                    <Link
                        v-if="product.category_slug"
                        :href="`/products?category=${encodeURIComponent(product.category_slug)}`"
                        class="hidden rounded-xl bg-dh-50 px-4 py-2 text-sm font-bold text-dh-700 transition hover:bg-dh-100 sm:block"
                    >
                        مشاهده همه
                    </Link>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="related in relatedProducts"
                        :key="related.id"
                        :href="`/products/${related.slug}`"
                        class="group rounded-2xl border border-dh-100 bg-white p-3.5 transition hover:-translate-y-1 hover:border-dh-200 hover:shadow-lg hover:shadow-dh-700/5"
                    >
                        <div
                            class="aspect-square overflow-hidden rounded-xl bg-dh-surface"
                        >
                            <img
                                v-if="related.image"
                                :src="related.image"
                                :alt="related.name"
                                class="h-full w-full object-contain p-4 transition duration-300 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full items-center justify-center text-xs text-dh-muted"
                            >
                                بدون تصویر
                            </div>
                        </div>
                        <h3
                            class="mt-3 line-clamp-2 text-sm leading-6 font-bold text-dh-800 transition group-hover:text-dh-700"
                        >
                            {{ related.name }}
                        </h3>
                        <p class="mt-2 text-sm font-extrabold text-dh-700">
                            {{ formatPrice(related.price) }}
                        </p>
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
