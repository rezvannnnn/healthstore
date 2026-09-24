<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
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
interface PagePropsWithFlash {
    [key: string]: unknown;
    flash?: {
        success?: string;
        error?: string;
        info?: string;
        status?: string;
    };
}
const props = defineProps<{
    cart: { id: number; items: CartItem[]; subtotal: number };
}>();
const page = usePage<PagePropsWithFlash>();
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
        { quantity },
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
    <div
        dir="rtl"
        class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10"
    >
        <header
            class="sticky top-0 z-30 border-b border-dh-100/70 bg-white/95 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6"
            >
                <Link
                    href="/"
                    class="flex shrink-0 items-center"
                    aria-label="داروخونه"
                >
                    <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                </Link>
                <Link
                    href="/products"
                    class="rounded-xl border border-dh-100 px-4 py-2.5 text-sm font-bold text-dh-700 hover:bg-dh-50"
                    >ادامه خرید</Link
                >
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-7 px-4 py-6 md:px-6 md:py-10">
            <header>
                <p class="text-xs font-bold text-dh-600">سفارش شما</p>
                <h1 class="mt-1 text-3xl font-black text-dh-800 md:text-4xl">
                    سبد خرید
                </h1>
                <p class="mt-2 text-sm text-dh-muted">
                    محصولات انتخاب‌شده را بررسی کنید و برای ثبت سفارش ادامه
                    دهید.
                </p>
            </header>

            <div
                v-if="page.props.flash?.success || page.props.flash?.status"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700"
                role="status"
            >
                {{ page.props.flash.success || page.props.flash.status }}
            </div>
            <div
                v-if="page.props.flash?.error"
                class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700"
                role="alert"
            >
                {{ page.props.flash.error }}
            </div>
            <div
                v-if="page.props.flash?.info"
                class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-700"
                role="status"
            >
                {{ page.props.flash.info }}
            </div>

            <section
                v-if="cart.items.length"
                class="grid gap-6 lg:grid-cols-[1fr_350px]"
            >
                <div class="space-y-3">
                    <article
                        v-for="item in cart.items"
                        :key="item.id"
                        class="relative rounded-3xl border border-dh-100 bg-white p-4 shadow-sm transition sm:p-5"
                        :class="busyItem === item.id ? 'opacity-80' : ''"
                        :aria-busy="busyItem === item.id"
                    >
                        <div class="flex gap-4">
                            <Link
                                :href="`/products/${item.product.slug}`"
                                :aria-label="`مشاهده ${item.product.name}`"
                                class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-dh-50 sm:size-28"
                            >
                                <img
                                    v-if="item.product.image"
                                    :src="item.product.image"
                                    :alt="item.product.name"
                                    class="h-full w-full object-contain p-2"
                                />
                                <svg
                                    v-else
                                    viewBox="0 0 48 48"
                                    class="size-10 text-dh-300"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <rect
                                        x="10"
                                        y="8"
                                        width="28"
                                        height="32"
                                        rx="4"
                                    />
                                    <path d="m15 31 7-8 5 5 4-4 3 4" />
                                </svg>
                            </Link>
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="`/products/${item.product.slug}`"
                                    class="line-clamp-2 text-base leading-7 font-black text-dh-800 hover:text-dh-700"
                                    >{{ item.product.name }}</Link
                                >
                                <div
                                    v-if="item.product.brand"
                                    class="mt-1 text-xs font-semibold text-dh-600"
                                >
                                    {{ item.product.brand }}
                                </div>
                                <div class="mt-3 text-xs text-dh-muted">
                                    {{ formatPrice(item.unit_price) }} برای هر
                                    واحد
                                </div>
                                <div
                                    class="mt-4 flex flex-wrap items-center justify-between gap-3"
                                >
                                    <div
                                        class="flex items-center rounded-xl border border-dh-100 bg-dh-surface"
                                        :aria-label="`تغییر تعداد ${item.product.name}`"
                                    >
                                        <button
                                            type="button"
                                            class="px-3 py-2 text-lg text-dh-700 disabled:opacity-40"
                                            :disabled="busyItem !== null"
                                            :aria-label="`کاهش تعداد ${item.product.name}`"
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
                                            class="min-w-9 text-center text-sm font-black"
                                            aria-live="polite"
                                            >{{
                                                item.quantity.toLocaleString(
                                                    'fa-IR',
                                                )
                                            }}</span
                                        >
                                        <button
                                            type="button"
                                            class="px-3 py-2 text-lg text-dh-700 disabled:opacity-40"
                                            :disabled="busyItem !== null"
                                            :aria-label="`افزایش تعداد ${item.product.name}`"
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
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-red-500 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="busyItem !== null"
                                        :aria-label="`حذف ${item.product.name} از سبد خرید`"
                                        @click="removeItem(item)"
                                    >
                                        <svg
                                            v-if="busyItem === item.id"
                                            viewBox="0 0 24 24"
                                            class="size-3.5 animate-spin"
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
                                            /></svg
                                        ><span>{{
                                            busyItem === item.id
                                                ? 'در حال بروزرسانی…'
                                                : 'حذف محصول'
                                        }}</span>
                                    </button>
                                </div>
                            </div>
                            <div
                                class="hidden shrink-0 text-left text-sm font-black text-dh-800 sm:block"
                            >
                                {{ formatPrice(item.line_total) }}
                            </div>
                        </div>
                        <div
                            class="mt-4 border-t border-dh-100 pt-3 text-left text-sm font-black text-dh-800 sm:hidden"
                        >
                            {{ formatPrice(item.line_total) }}
                        </div>
                    </article>
                </div>

                <aside
                    class="h-fit rounded-3xl border border-dh-100 bg-white p-6 shadow-sm lg:sticky lg:top-24"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-xl bg-dh-50 text-dh-700"
                            ><svg
                                viewBox="0 0 24 24"
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M6 8h12l1 12H5L6 8Z" />
                                <path d="M9 8a3 3 0 0 1 6 0" /></svg
                        ></span>
                        <h2 class="font-black text-dh-800">خلاصه سفارش</h2>
                    </div>
                    <div
                        class="mt-6 flex items-center justify-between text-sm text-dh-muted"
                    >
                        <span>جمع محصولات</span
                        ><span>{{ formatPrice(cart.subtotal) }}</span>
                    </div>
                    <div class="my-5 border-t border-dh-100"></div>
                    <div class="flex items-end justify-between gap-4">
                        <span class="text-sm font-bold text-dh-800"
                            >مبلغ قابل پرداخت</span
                        ><span class="text-xl font-black text-dh-700">{{
                            formatPrice(cart.subtotal)
                        }}</span>
                    </div>
                    <div
                        v-if="busyItem !== null"
                        class="mt-5 rounded-xl bg-dh-50 px-3 py-2 text-center text-xs font-bold text-dh-700"
                        role="status"
                    >
                        در حال بروزرسانی سبد خرید…
                    </div>
                    <Link
                        href="/checkout"
                        class="mt-6 block rounded-2xl bg-dh-700 px-5 py-3.5 text-center text-sm font-black text-white shadow-sm transition hover:bg-dh-800"
                        :class="
                            busyItem !== null
                                ? 'pointer-events-none opacity-50'
                                : ''
                        "
                        :aria-disabled="busyItem !== null"
                        @click="busyItem !== null && $event.preventDefault()"
                        >ادامه و انتخاب آدرس</Link
                    >
                    <div
                        class="mt-4 flex items-center gap-2 text-xs leading-6 text-dh-muted"
                    >
                        <span
                            class="size-2 shrink-0 rounded-full bg-dh-green-500"
                        ></span
                        >قبل از پرداخت، جزئیات سفارش را یک بار دیگر بررسی کنید.
                    </div>
                </aside>
            </section>

            <section
                v-else
                class="rounded-[2rem] border border-dashed border-dh-200 bg-white p-10 text-center shadow-sm md:p-16"
            >
                <div
                    class="mx-auto flex size-16 items-center justify-center rounded-3xl bg-dh-50 text-dh-600"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-8"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <path d="M6 8h12l1 12H5L6 8Z" />
                        <path d="M9 8a3 3 0 0 1 6 0" />
                    </svg>
                </div>
                <h2 class="mt-5 text-xl font-black text-dh-800">
                    سبد خرید شما خالی است
                </h2>
                <p class="mt-2 text-sm text-dh-muted">
                    از فروشگاه محصول موردنظر خود را انتخاب کنید.
                </p>
                <Link
                    href="/products"
                    class="mt-6 inline-flex rounded-2xl bg-dh-700 px-6 py-3 text-sm font-black text-white hover:bg-dh-800"
                    >مشاهده محصولات</Link
                >
            </section>
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-40 border-t border-dh-100 bg-white/95 px-3 py-2 shadow-[0_-8px_25px_rgba(20,86,92,0.08)] backdrop-blur lg:hidden"
            aria-label="ناوبری موبایل"
        >
            <div
                class="mx-auto grid max-w-md grid-cols-3 gap-2 text-center text-[11px] font-bold"
            >
                <Link href="/" class="rounded-xl px-2 py-2 text-dh-muted"
                    ><svg
                        viewBox="0 0 24 24"
                        class="mx-auto mb-1 size-4"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        /></svg
                    >خانه</Link
                ><Link
                    href="/products"
                    class="rounded-xl px-2 py-2 text-dh-muted"
                    ><svg
                        viewBox="0 0 24 24"
                        class="mx-auto mb-1 size-4"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M6 7h12l1 11H5L6 7Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M9 7a3 3 0 0 1 6 0"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        /></svg
                    >فروشگاه</Link
                ><Link
                    href="/cart"
                    class="rounded-xl bg-dh-50 px-2 py-2 text-dh-700"
                    ><svg
                        viewBox="0 0 24 24"
                        class="mx-auto mb-1 size-4"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M4 5h2l1.5 10.2a2 2 0 0 0 2 1.8h7.6a2 2 0 0 0 2-1.7L20 8H7"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle cx="10" cy="20" r="1" fill="currentColor" />
                        <circle
                            cx="18"
                            cy="20"
                            r="1"
                            fill="currentColor"
                        /></svg
                    >سبد خرید</Link
                >
            </div>
        </nav>
    </div>
</template>
