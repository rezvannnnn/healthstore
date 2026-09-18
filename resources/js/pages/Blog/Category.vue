<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image: string | null;
    featured_image_alt: string | null;
    published_at: string | null;
}
interface Category { id: number; name: string; slug: string; description: string | null; }
interface Seo { title: string; description: string; canonical: string; }
interface Pagination { current_page: number; last_page: number; total: number; }

const props = defineProps<{
    seo: Seo;
    category: Category;
    articles: Article[];
    pagination: Pagination;
}>();

function pageUrl(page: number): string {
    return page <= 1 ? '/blog/category/' + props.category.slug : '/blog/category/' + props.category.slug + '?page=' + page;
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

    <main dir="rtl" class="mx-auto max-w-7xl space-y-8 p-6">
        <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
            <Link href="/">خانه</Link><span>/</span><Link href="/blog">مجله سلامت</Link><span>/</span>
            <span class="text-gray-900">{{ category.name }}</span>
        </nav>

        <header class="rounded-2xl border bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-indigo-600">دسته‌بندی مجله</p>
            <h1 class="mt-2 text-3xl font-bold">{{ category.name }}</h1>
            <p v-if="category.description" class="mt-3 max-w-3xl leading-8 text-gray-600">{{ category.description }}</p>
        </header>

        <section v-if="articles.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <article v-for="article in articles" :key="article.id" class="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <img v-if="article.featured_image" :src="article.featured_image" :alt="article.featured_image_alt || article.title" loading="lazy" decoding="async" class="h-48 w-full object-cover" />
                <div class="space-y-3 p-5">
                    <h2 class="text-xl font-semibold">{{ article.title }}</h2>
                    <p v-if="article.excerpt" class="line-clamp-3 text-sm leading-7 text-gray-600">{{ article.excerpt }}</p>
                    <Link :href="'/blog/' + article.slug" class="inline-block text-sm font-semibold underline">ادامه مطلب</Link>
                </div>
            </article>
        </section>

        <section v-else class="rounded-xl border bg-white p-12 text-center text-gray-500">
            هنوز مقاله‌ای در این دسته منتشر نشده است.
        </section>

        <nav v-if="pagination.last_page > 1" aria-label="صفحات دسته‌بندی مجله" class="flex flex-wrap items-center justify-center gap-2">
            <Link v-if="pagination.current_page > 1" :href="pageUrl(pagination.current_page - 1)" preserve-scroll class="rounded-lg border bg-white px-4 py-2 text-sm">قبلی</Link>
            <Link v-for="page in pagination.last_page" :key="page" :href="pageUrl(page)" preserve-scroll
                :aria-current="page === pagination.current_page ? 'page' : undefined"
                class="min-w-10 rounded-lg px-3 py-2 text-center text-sm"
                :class="page === pagination.current_page ? 'bg-indigo-600 text-white' : 'border bg-white text-gray-700'">
                {{ page.toLocaleString('fa-IR') }}
            </Link>
            <Link v-if="pagination.current_page < pagination.last_page" :href="pageUrl(pagination.current_page + 1)" preserve-scroll class="rounded-lg border bg-white px-4 py-2 text-sm">بعدی</Link>
        </nav>
    </main>
</template>
