<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import BrandLogo from '@/components/BrandLogo.vue';
import CartLink from '@/components/CartLink.vue';

type ActiveSection = 'home' | 'products' | 'categories' | 'brands' | 'blog';

type AuthUser = {
    name?: string | null;
};

type SharedPageProps = {
    auth: {
        user: AuthUser | null;
    };
    cart?: {
        items_count?: number;
    };
};

const props = withDefaults(
    defineProps<{
        active?: ActiveSection;
    }>(),
    {
        active: 'home',
    },
);

const searchQuery = ref('');
const page = usePage<SharedPageProps>();

function searchProducts(): void {
    const query = searchQuery.value.trim();

    router.get('/products', query ? { search: query } : {}, {
        preserveState: true,
        replace: true,
    });
}

function navClass(section: ActiveSection): string {
    return props.active === section
        ? 'relative flex h-full items-center text-dh-800 after:absolute after:right-0 after:bottom-0 after:left-0 after:h-0.5 after:rounded-full after:bg-dh-500'
        : 'relative flex h-full items-center text-dh-700 transition hover:text-dh-500';
}
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-dh-100/80 bg-white/95 backdrop-blur"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-20 items-center gap-4">
                <Link
                    href="/"
                    class="flex shrink-0 items-center"
                    aria-label="داروخونه"
                >
                    <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                </Link>

                <form
                    class="hidden min-w-0 flex-1 md:block"
                    @submit.prevent="searchProducts"
                >
                    <label for="storefront-search" class="sr-only"
                        >جستجوی محصولات</label
                    >
                    <div class="relative">
                        <svg
                            viewBox="0 0 24 24"
                            class="pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2 text-dh-500"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="6.5"
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
                            id="storefront-search"
                            v-model="searchQuery"
                            type="search"
                            placeholder="نام محصول، برند یا بارکد را جستجو کنید..."
                            class="h-12 w-full rounded-2xl border border-dh-100 bg-dh-50/70 pr-12 pl-28 text-sm text-dh-900 transition outline-none placeholder:text-dh-muted focus:border-dh-300 focus:bg-white focus:ring-4 focus:ring-dh-100"
                        />
                        <button
                            type="submit"
                            class="absolute top-1/2 left-1.5 -translate-y-1/2 rounded-xl bg-dh-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-dh-700"
                        >
                            جستجو
                        </button>
                    </div>
                </form>

                <div class="mr-auto flex items-center gap-1">
                    <template v-if="page.props.auth.user">
                        <Link
                            href="/account/profile"
                            class="rounded-xl px-3 py-2 text-sm font-semibold text-dh-700 transition hover:bg-dh-50 sm:block"
                        >
                            {{ page.props.auth.user.name || 'حساب کاربری' }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-dh-700 transition hover:bg-dh-50 sm:block"
                        >
                            ورود
                        </Link>
                        <Link
                            href="/register"
                            class="hidden rounded-xl bg-dh-green-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-dh-green-600 sm:block"
                        >
                            ثبت‌نام
                        </Link>
                    </template>
                    <Link
                        :href="
                            page.props.auth.user ? '/account/profile' : '/login'
                        "
                        class="flex size-11 items-center justify-center rounded-2xl border border-dh-100 bg-white text-dh-700 transition hover:border-dh-300 hover:bg-dh-50 sm:hidden"
                        :aria-label="
                            page.props.auth.user ? 'حساب کاربری' : 'ورود'
                        "
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="size-5"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle
                                cx="12"
                                cy="8"
                                r="3"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="M5.5 19.5c.9-3.1 3.1-4.7 6.5-4.7s5.6 1.6 6.5 4.7"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </Link>
                    <CartLink />
                </div>
            </div>

            <div class="border-t border-dh-50 md:hidden">
                <form class="py-3" @submit.prevent="searchProducts">
                    <label for="mobile-storefront-search" class="sr-only"
                        >جستجوی محصولات</label
                    >
                    <div class="relative">
                        <input
                            id="mobile-storefront-search"
                            v-model="searchQuery"
                            type="search"
                            placeholder="جستجوی محصول یا برند..."
                            class="h-11 w-full rounded-xl border border-dh-100 bg-dh-50/70 px-4 pl-20 text-sm outline-none focus:border-dh-300 focus:bg-white focus:ring-4 focus:ring-dh-100"
                        />
                        <button
                            type="submit"
                            class="absolute top-1/2 left-1 -translate-y-1/2 rounded-lg bg-dh-600 px-3 py-2 text-xs font-bold text-white"
                        >
                            جستجو
                        </button>
                    </div>
                </form>
            </div>

            <nav
                class="hidden h-12 items-center gap-7 text-sm font-semibold md:flex"
                aria-label="ناوبری اصلی"
            >
                <Link href="/" :class="navClass('home')">خانه</Link>
                <Link href="/products" :class="navClass('products')"
                    >محصولات</Link
                >
                <Link href="/categories" :class="navClass('categories')"
                    >دسته‌بندی‌ها</Link
                >
                <Link href="/brands" :class="navClass('brands')">برندها</Link>
                <Link href="/blog" :class="navClass('blog')">مجله سلامت</Link>
            </nav>
        </div>
    </header>

    <nav
        class="fixed inset-x-0 bottom-0 z-50 border-t border-dh-100 bg-white/95 px-3 py-2 shadow-[0_-8px_25px_rgba(20,86,92,0.08)] backdrop-blur md:hidden"
        aria-label="ناوبری موبایل"
    >
        <div
            class="mx-auto grid max-w-md grid-cols-5 gap-1 text-center text-[11px] font-bold"
        >
            <Link
                href="/"
                class="rounded-xl px-2 py-2"
                :class="
                    props.active === 'home'
                        ? 'bg-dh-50 text-dh-700'
                        : 'text-dh-muted'
                "
            >
                خانه
            </Link>
            <Link
                href="/products"
                class="rounded-xl px-2 py-2"
                :class="
                    props.active === 'products'
                        ? 'bg-dh-50 text-dh-700'
                        : 'text-dh-muted'
                "
            >
                فروشگاه
            </Link>
            <Link
                href="/categories"
                class="rounded-xl px-2 py-2"
                :class="
                    props.active === 'categories'
                        ? 'bg-dh-50 text-dh-700'
                        : 'text-dh-muted'
                "
            >
                دسته‌ها
            </Link>
            <Link
                href="/brands"
                class="rounded-xl px-2 py-2"
                :class="
                    props.active === 'brands'
                        ? 'bg-dh-50 text-dh-700'
                        : 'text-dh-muted'
                "
            >
                برندها
            </Link>
            <Link
                href="/cart"
                class="relative rounded-xl px-2 py-2 text-dh-muted"
            >
                سبد
                <span
                    v-if="(page.props.cart?.items_count ?? 0) > 0"
                    class="absolute -top-0.5 right-1/2 flex min-w-4 translate-x-1/2 items-center justify-center rounded-full bg-dh-green-500 px-1 py-0.5 text-[9px] leading-none font-black text-white ring-2 ring-white"
                    aria-hidden="true"
                >
                    {{ page.props.cart?.items_count }}
                </span>
            </Link>
        </div>
    </nav>
</template>
