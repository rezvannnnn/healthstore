<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    articles_count: number;
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
    categories: Category[];
    pagination: Pagination;
}>();

function pageUrl(page: number): string {
    if (page <= 1) {
        return '/blog/categories';
    }

    return '/blog/categories?page=' + page;
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
        <meta name="twitter:description" :content="seo.description" />    </Head>

    <main dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl space-y-8">
            <nav
                aria-label="مسیر صفحه"
                class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400"
            >
                <Link href="/" class="hover:text-indigo-600">خانه</Link>
                <span>/</span>
                <Link href="/blog" class="hover:text-indigo-600"
                    >مجله سلامت</Link
                >
                <span>/</span>
                <span class="text-gray-900 dark:text-white">دسته‌بندی‌ها</span>
            </nav>

            <header
                class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 md:p-8 dark:bg-gray-900 dark:ring-gray-800"
            >
                <p class="text-sm font-medium text-indigo-600">انتخاب موضوع</p>
                <h1
                    class="mt-2 text-3xl font-bold text-gray-900 md:text-4xl dark:text-white"
                >
                    دسته‌بندی‌های مجله سلامت
                </h1>
                <p
                    class="mt-4 max-w-3xl leading-8 text-gray-600 dark:text-gray-300"
                >
                    موضوعات فعال مجله را مشاهده کنید و مطالب آموزشی هر دسته را
                    جداگانه بخوانید.
                </p>
            </header>

            <section
                v-if="categories.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="'/blog/category/' + category.slug"
                    class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900 dark:ring-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-white"
                    >
                        {{ category.name }}
                    </h2>
                    <p
                        v-if="category.description"
                        class="mt-2 line-clamp-3 text-sm leading-7 text-gray-500 dark:text-gray-400"
                    >
                        {{ category.description }}
                    </p>
                    <p class="mt-4 text-sm font-medium text-gray-500">
                        {{ category.articles_count.toLocaleString('fa-IR') }}
                        مقاله منتشرشده
                    </p>
                </Link>
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-gray-300 p-12 text-center text-gray-500 dark:border-gray-700"
            >
                هنوز دسته‌بندی فعالی برای مجله ثبت نشده است.
            </section>

            <nav
                v-if="pagination.last_page > 1"
                aria-label="صفحات دسته‌بندی‌های مجله"
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
