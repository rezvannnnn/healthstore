<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface CartItem {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: number;
    line_total: number;
    product: {
        name: string;
        slug: string;
        brand: string | null;
        image: string | null;
    };
}

const props = defineProps<{
    cart: {
        id: number;
        items: CartItem[];
        subtotal: number;
    };
}>();

const busyItem = ref<number | null>(null);

function formatPrice(value: number): string {
    return `${value.toLocaleString('fa-IR')} تومان`;
}

function updateQuantity(item: CartItem, quantity: number): void {
    if (quantity < 1) {
        removeItem(item);
        return;
    }

    busyItem.value = item.id;
    router.put(
        `/cart/${props.cart.id}/items/${item.id}`,
        {
            quantity,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                busyItem.value = null;
            },
        },
    );
}

function removeItem(item: CartItem): void {
    busyItem.value = item.id;
    router.delete(`/cart/${props.cart.id}/items/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            busyItem.value = null;
        },
    });
}
</script>

<template>
    <Head title="سبد خرید" />

    <main dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-6xl space-y-6">
            <header class="flex items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-3xl font-bold text-gray-900 dark:text-white"
                    >
                        سبد خرید
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        محصولات انتخاب‌شده خود را بررسی کنید.
                    </p>
                </div>
                <Link
                    href="/products"
                    class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-800"
                    >ادامه خرید</Link
                >
            </header>

            <section
                v-if="cart.items.length"
                class="grid gap-6 lg:grid-cols-[1fr_340px]"
            >
                <div class="space-y-3">
                    <article
                        v-for="item in cart.items"
                        :key="item.id"
                        class="flex gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
                    >
                        <Link
                            :href="`/products/${item.product.slug}`"
                            class="h-28 w-28 shrink-0 rounded-xl bg-gray-100 dark:bg-gray-800"
                        >
                            <img
                                v-if="item.product.image"
                                :src="item.product.image"
                                :alt="item.product.name"
                                class="h-full w-full object-contain p-2"
                            />
                        </Link>
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="`/products/${item.product.slug}`"
                                class="font-semibold text-gray-900 hover:text-indigo-600 dark:text-white"
                                >{{ item.product.name }}</Link
                            >
                            <div
                                v-if="item.product.brand"
                                class="mt-1 text-xs text-indigo-600"
                            >
                                {{ item.product.brand }}
                            </div>
                            <div class="mt-3 text-sm text-gray-500">
                                {{ formatPrice(item.unit_price) }} برای هر واحد
                            </div>
                            <div
                                class="mt-3 flex flex-wrap items-center justify-between gap-3"
                            >
                                <div
                                    class="flex items-center rounded-xl border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-950"
                                >
                                    <button
                                        type="button"
                                        class="px-3 py-2"
                                        :disabled="busyItem === item.id"
                                        @click="
                                            updateQuantity(
                                                item,
                                                item.quantity - 1,
                                            )
                                        "
                                    >
                                        −
                                    </button>
                                    <span
                                        class="min-w-8 text-center text-sm font-semibold"
                                        >{{
                                            item.quantity.toLocaleString(
                                                'fa-IR',
                                            )
                                        }}</span
                                    >
                                    <button
                                        type="button"
                                        class="px-3 py-2"
                                        :disabled="busyItem === item.id"
                                        @click="
                                            updateQuantity(
                                                item,
                                                item.quantity + 1,
                                            )
                                        "
                                    >
                                        +
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-red-500 hover:text-red-600"
                                    :disabled="busyItem === item.id"
                                    @click="removeItem(item)"
                                >
                                    حذف
                                </button>
                            </div>
                        </div>
                        <div
                            class="shrink-0 text-left font-bold text-gray-900 dark:text-white"
                        >
                            {{ formatPrice(item.line_total) }}
                        </div>
                    </article>
                </div>

                <aside
                    class="h-fit rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
                >
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        خلاصه سفارش
                    </h2>
                    <div
                        class="mt-5 flex items-center justify-between text-sm text-gray-500"
                    >
                        <span>جمع محصولات</span>
                        <span>{{ formatPrice(cart.subtotal) }}</span>
                    </div>
                    <div
                        class="my-4 border-t border-gray-100 dark:border-gray-800"
                    ></div>
                    <div class="flex items-center justify-between">
                        <span
                            class="font-semibold text-gray-900 dark:text-white"
                            >مبلغ قابل پرداخت</span
                        >
                        <span class="text-lg font-bold text-indigo-600">{{
                            formatPrice(cart.subtotal)
                        }}</span>
                    </div>
                    <Link
                        href="/checkout"
                        class="mt-6 block rounded-xl bg-indigo-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700"
                        >ادامه و انتخاب آدرس</Link
                    >
                </aside>
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700"
            >
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    سبد خرید شما خالی است
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                    از فروشگاه محصول موردنظر خود را انتخاب کنید.
                </p>
                <Link
                    href="/products"
                    class="mt-5 inline-block rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700"
                    >مشاهده محصولات</Link
                >
            </section>
        </div>
    </main>
</template>
