<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Product {
    id: number;
    name: string;
    slug: string;
    brand: string | null;
    image: string | null;
    price: number | null;
    compare_at_price: number | null;
    available: boolean;
}

defineProps<{
    product: Product;
}>();

const emit = defineEmits<{
    addToCart: [productId: number];
}>();

function formatPrice(value: number | null): string {
    return value === null
        ? 'تماس بگیرید'
        : `${value.toLocaleString('fa-IR')} تومان`;
}
</script>

<template>
    <article
        class="group flex h-full flex-col overflow-hidden rounded-[1.4rem] border border-[#dce9ec] bg-white shadow-[0_8px_30px_rgba(4,84,123,0.06)] transition duration-200 hover:-translate-y-1 hover:border-[#bedce0] hover:shadow-[0_16px_36px_rgba(4,84,123,0.11)]"
    >
        <Link :href="`/products/${product.slug}`" class="block">
            <div
                class="relative aspect-square overflow-hidden bg-[linear-gradient(145deg,#f7fbfc_0%,#eef8f8_100%)]"
            >
                <span
                    v-if="product.compare_at_price && product.price !== null && product.compare_at_price > product.price"
                    class="absolute right-3 top-3 z-10 rounded-full bg-[#e7f7f3] px-2.5 py-1 text-[11px] font-bold text-[#157f67]"
                >
                    پیشنهاد ویژه
                </span>
                <img
                    v-if="product.image"
                    :src="product.image"
                    :alt="product.name"
                    loading="lazy"
                    decoding="async"
                    class="h-full w-full object-contain p-6 transition duration-300 group-hover:scale-[1.035]"
                />
                <div
                    v-else
                    class="flex h-full items-center justify-center text-sm text-slate-400"
                >
                    بدون تصویر
                </div>
            </div>
            <div class="space-y-3 p-4">
                <p
                    v-if="product.brand"
                    class="text-xs font-semibold text-[#0a8d9f]"
                >
                    {{ product.brand }}
                </p>
                <h2
                    class="line-clamp-2 min-h-12 text-sm leading-6 font-bold text-slate-800"
                >
                    {{ product.name }}
                </h2>
                <div
                    class="flex min-h-14 items-end justify-between gap-3"
                >
                    <div>
                        <div class="text-base font-black text-[#04547b]">
                            {{ formatPrice(product.price) }}
                        </div>
                        <div
                            v-if="product.compare_at_price && product.price !== null && product.compare_at_price > product.price"
                            class="mt-1 text-xs text-slate-400 line-through"
                        >
                            {{ formatPrice(product.compare_at_price) }}
                        </div>
                    </div>
                    <span
                        class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                        :class="
                            product.available
                                ? 'bg-[#edf8f2] text-[#2e8b57]'
                                : 'bg-slate-100 text-slate-400'
                        "
                    >
                        {{ product.available ? 'موجود' : 'ناموجود' }}
                    </span>
                </div>
            </div>
        </Link>

        <div class="mt-auto px-4 pb-4">
            <button
                type="button"
                :disabled="!product.available || product.price === null"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#04547b] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#034664] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                @click="emit('addToCart', product.id)"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none">
                    <path
                        d="M5 6h2l1.4 9.2a2 2 0 0 0 2 1.7h6.8a2 2 0 0 0 2-1.6L20 9H8"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <circle cx="10.2" cy="19.2" r="1.1" fill="currentColor" />
                    <circle cx="17.2" cy="19.2" r="1.1" fill="currentColor" />
                </svg>
                افزودن به سبد
            </button>
        </div>
    </article>
</template>
