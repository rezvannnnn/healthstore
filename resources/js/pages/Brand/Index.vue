<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';

interface Brand {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
    products_count: number;
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

defineProps<{
    seo: Seo;
    brands: Brand[];
    pagination: Pagination;
}>();

function pageUrl(page: number): string {
    if (page <= 1) {
        return '/brands';
    }

    return '/brands?page=' + page;
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
        <header class="sticky top-0 z-40 border-b border-dh-100/70 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6">
                <Link href="/" class="flex shrink-0 items-center" aria-label="داروخونه">
                        <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                    </Link>
                <div class="flex items-center gap-2">
                    <Link href="/products" class="rounded-xl bg-dh-50 px-4 py-2 text-sm font-bold text-dh-700 hover:bg-dh-100">محصولات</Link>
                    <Link href="/cart" class="rounded-xl bg-dh-700 px-4 py-2 text-sm font-bold text-white hover:bg-dh-800">سبد خرید</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-8 px-4 py-6 md:px-6 md:py-10">
            <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-2 text-sm text-dh-muted">
                <Link href="/" class="hover:text-dh-700">خانه</Link>
                <span>/</span>
                <span class="font-semibold text-dh-800">برندها</span>
            </nav>

            <section class="rounded-[2rem] bg-dh-800 p-7 text-white shadow-xl shadow-dh-900/10 md:p-10">
                <p class="text-xs font-bold text-dh-100">انتخاب بر اساس برند</p>
                <h1 class="mt-3 text-3xl font-black md:text-5xl">برند مورد علاقه‌تان را پیدا کنید.</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-dh-50/85 md:text-base">
                    برندهای فعال داروخونه را ببینید و محصولات هر برند را جداگانه بررسی کنید.
                </p>
                <div class="mt-6 inline-flex rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-dh-50">
                    {{ pagination.total.toLocaleString('fa-IR') }} برند
                </div>
            </section>

            <section v-if="brands.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Link
                    v-for="brand in brands"
                    :key="brand.id"
                    :href="'/brands/' + brand.slug"
                    class="group overflow-hidden rounded-3xl border border-dh-100 bg-white shadow-sm transition hover:-translate-y-1 hover:border-dh-200 hover:shadow-lg"
                >
                    <div class="flex aspect-[16/9] items-center justify-center bg-dh-50 p-8">
                        <img
                            v-if="brand.logo"
                            :src="brand.logo"
                            :alt="brand.name"
                            loading="lazy"
                            decoding="async"
                            class="max-h-full max-w-full object-contain transition duration-300 group-hover:scale-105"
                        />
                        <span v-else class="text-center text-2xl font-black text-dh-700">{{ brand.name }}</span>
                    </div>
                    <div class="p-5">
                        <h2 class="text-xl font-black text-dh-800 group-hover:text-dh-700">{{ brand.name }}</h2>
                        <p v-if="brand.description" class="mt-2 line-clamp-2 text-sm leading-7 text-dh-muted">
                            {{ brand.description }}
                        </p>
                        <div class="mt-5 flex items-center justify-between border-t border-dh-100 pt-4">
                            <span class="text-xs text-dh-muted">{{ brand.products_count.toLocaleString('fa-IR') }} محصول فعال</span>
                            <span class="text-sm font-black text-dh-700">مشاهده ←</span>
                        </div>
                    </div>
                </Link>
            </section>

            <section v-else class="rounded-3xl border border-dashed border-dh-200 bg-white p-12 text-center text-dh-muted">
                هنوز برند فعالی ثبت نشده است.
            </section>

            <nav
                v-if="pagination.last_page > 1"
                aria-label="صفحات برندها"
                class="flex flex-wrap items-center justify-center gap-2"
            >
                <Link
                    v-if="pagination.current_page > 1"
                    :href="pageUrl(pagination.current_page - 1)"
                    preserve-scroll
                    class="rounded-xl border border-dh-100 bg-white px-4 py-2.5 text-sm font-bold text-dh-700 hover:bg-dh-50"
                >
                    قبلی
                </Link>
                <Link
                    v-for="page in pagination.last_page"
                    :key="page"
                    :href="pageUrl(page)"
                    preserve-scroll
                    :aria-current="page === pagination.current_page ? 'page' : undefined"
                    :aria-label="'صفحه ' + page.toLocaleString('fa-IR')"
                    class="min-w-10 rounded-xl px-3 py-2.5 text-center text-sm font-bold"
                    :class="page === pagination.current_page ? 'bg-dh-700 text-white' : 'border border-dh-100 bg-white text-dh-muted hover:bg-dh-50'"
                >
                    {{ page.toLocaleString('fa-IR') }}
                </Link>
                <Link
                    v-if="pagination.current_page < pagination.last_page"
                    :href="pageUrl(pagination.current_page + 1)"
                    preserve-scroll
                    class="rounded-xl border border-dh-100 bg-white px-4 py-2.5 text-sm font-bold text-dh-700 hover:bg-dh-50"
                >
                    بعدی
                </Link>
            </nav>
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-dh-100 bg-white/95 px-3 py-2 backdrop-blur lg:hidden">
            <div class="mx-auto grid max-w-md grid-cols-3 gap-2 text-center text-[11px] font-bold text-dh-muted">
                <Link href="/" class="rounded-xl px-2 py-2">خانه</Link>
                <Link href="/products" class="rounded-xl px-2 py-2">فروشگاه</Link>
                <Link href="/brands" class="rounded-xl bg-dh-50 px-2 py-2 text-dh-700">برندها</Link>
            </div>
        </nav>
    </div>
</template>
