<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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

const props = defineProps<{
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

    <main dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl space-y-8">
            <nav
                aria-label="مسیر صفحه"
                class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400"
            >
                <Link href="/" class="hover:text-indigo-600">خانه</Link>
                <span>/</span>
                <span class="text-gray-900 dark:text-white">برندها</span>
            </nav>

            <header
                class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 md:p-8 dark:bg-gray-900 dark:ring-gray-800"
            >
                <p class="text-sm font-medium text-indigo-600">
                    انتخاب بر اساس برند
                </p>
                <h1
                    class="mt-2 text-3xl font-bold text-gray-900 dark:text-white md:text-4xl"
                >
                    برندهای فروشگاه
                </h1>
                <p class="mt-4 max-w-3xl leading-8 text-gray-600 dark:text-gray-300">
                    برندهای فعال فروشگاه را مشاهده کنید و محصولات هر برند را
                    جداگانه بررسی کنید.
                </p>
            </header>

            <section
                v-if="brands.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Link
                    v-for="brand in brands"
                    :key="brand.id"
                    :href="'/brands/' + brand.slug"
                    class="group overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900 dark:ring-gray-800"
                >
                    <div
                        class="flex aspect-[16/9] items-center justify-center rounded-2xl bg-gray-100 p-6 dark:bg-gray-800"
                    >
                        <img
                            v-if="brand.logo"
                            :src="brand.logo"
                            :alt="brand.name"
                            loading="lazy"
                            decoding="async"
                            class="max-h-full max-w-full object-contain"
                        />
                        <span v-else class="text-2xl font-bold text-gray-400">
                            {{ brand.name }}
                        </span>
                    </div>

                    <div class="mt-4">
                        <h2
                            class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-white"
                        >
                            {{ brand.name }}
                        </h2>
                        <p
                            v-if="brand.description"
                            class="mt-2 line-clamp-2 text-sm leading-6 text-gray-500 dark:text-gray-400"
                        >
                            {{ brand.description }}
                        </p>
                        <p class="mt-4 text-sm font-medium text-gray-500">
                            {{ brand.products_count.toLocaleString('fa-IR') }}
                            محصول فعال
                        </p>
                    </div>
                </Link>
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500 dark:border-gray-700"
            >
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
                    aria-label="صفحه قبلی"
                    class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-800"
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
                    :aria-label="'صفحه ' + page.toLocaleString('fa-IR')"
                    class="min-w-10 rounded-xl px-3 py-2 text-center text-sm font-medium"
                    :class="
                        page === pagination.current_page
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-800'
                    "
                >
                    {{ page.toLocaleString('fa-IR') }}
                </Link>
                <Link
                    v-if="pagination.current_page < pagination.last_page"
                    :href="pageUrl(pagination.current_page + 1)"
                    preserve-scroll
                    aria-label="صفحه بعدی"
                    class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-800"
                >
                    بعدی
                </Link>
            </nav>
        </div>
    </main>
</template>
