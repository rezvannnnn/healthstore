<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image: string | null;
    featured_image_alt: string | null;
    category: { id: number; name: string; slug: string } | null;
    published_at: string | null;
}

interface Seo {
    title: string;
    description: string;
    canonical: string;
}

defineProps<{
    seo: Seo;
    categories: Category[];
    articles: Article[];
    pagination: { current_page: number; last_page: number; total: number };
    filters: { search: string };
}>();

function pageUrl(page: number): string {
    const params = new URLSearchParams();

    if (page > 1) {
        params.set('page', String(page));
    }

    const currentSearch = new URLSearchParams(window.location.search).get(
        'search',
    );
    if (currentSearch) {
        params.set('search', currentSearch);
    }

    const query = params.toString();
    return query ? `/blog?${query}` : '/blog';
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
    <div class="mx-auto max-w-7xl space-y-8 p-6" dir="rtl">
        <header>
            <h1 class="text-3xl font-bold">مجله سلامت</h1>
            <p class="mt-2 text-gray-500">
                مطالب آموزشی و کاربردی درباره سلامت و محصولات بهداشتی
            </p>
        </header>
        <div v-if="categories.length" class="flex flex-wrap items-center gap-2">
            <Link
                href="/blog/categories"
                class="rounded-full border px-3 py-1.5 text-sm font-medium hover:bg-gray-50"
            >
                همه دسته‌ها
            </Link>
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="'/blog/category/' + category.slug"
                class="rounded-full border px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50"
            >
                {{ category.name }}
            </Link>
        </div>
        <form method="get" action="/blog" class="flex gap-3">
            <input
                name="search"
                :value="filters.search"
                class="min-w-0 flex-1 rounded-lg border p-3"
                placeholder="جستجو در مقالات"
                aria-label="جستجو در مقالات"
            /><button class="rounded-lg border px-5">جستجو</button>
        </form>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="article in articles"
                :key="article.id"
                class="overflow-hidden rounded-2xl border bg-white shadow-sm"
            >
                <img
                    v-if="article.featured_image"
                    :src="article.featured_image"
                    :alt="article.featured_image_alt || article.title"
                    loading="lazy"
                    decoding="async"
                    class="h-48 w-full object-cover"
                />
                <div class="space-y-3 p-5">
                    <div class="text-xs text-gray-500">
                        {{ article.category?.name || 'سلامت' }}
                    </div>
                    <h2 class="text-xl font-semibold">{{ article.title }}</h2>
                    <p
                        v-if="article.excerpt"
                        class="line-clamp-3 text-sm leading-7 text-gray-600"
                    >
                        {{ article.excerpt }}
                    </p>
                    <Link
                        :href="`/blog/${article.slug}`"
                        class="inline-block text-sm font-semibold underline"
                        >ادامه مطلب</Link
                    >
                </div>
            </article>
        </div>
        <div
            v-if="articles.length === 0"
            class="rounded-xl border bg-white p-12 text-center text-gray-500"
        >
            مقاله‌ای پیدا نشد.
        </div>

        <nav
            v-if="pagination.last_page > 1"
            aria-label="صفحات مجله"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <Link
                v-if="pagination.current_page > 1"
                :href="pageUrl(pagination.current_page - 1)"
                preserve-scroll
                aria-label="صفحه قبلی"
                class="rounded-lg border bg-white px-4 py-2 text-sm hover:bg-gray-50"
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
                :aria-label="`صفحه ${page.toLocaleString('fa-IR')}`"
                class="min-w-10 rounded-lg px-3 py-2 text-center text-sm"
                :class="
                    page === pagination.current_page
                        ? 'bg-indigo-600 text-white'
                        : 'border bg-white text-gray-700 hover:bg-gray-50'
                "
            >
                {{ page.toLocaleString('fa-IR') }}
            </Link>
            <Link
                v-if="pagination.current_page < pagination.last_page"
                :href="pageUrl(pagination.current_page + 1)"
                preserve-scroll
                aria-label="صفحه بعدی"
                class="rounded-lg border bg-white px-4 py-2 text-sm hover:bg-gray-50"
            >
                بعدی
            </Link>
        </nav>
    </div>
</template>
