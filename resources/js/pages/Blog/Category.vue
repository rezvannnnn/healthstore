<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StorefrontHeader from '@/components/StorefrontHeader.vue';

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image: string | null;
    featured_image_alt: string | null;
    published_at: string | null;
}
interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
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
    category: Category;
    articles: Article[];
    pagination: Pagination;
}>();

function pageUrl(page: number): string {
    return page <= 1
        ? '/blog/category/' + props.category.slug
        : '/blog/category/' + props.category.slug + '?page=' + page;
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

    <div
        dir="rtl"
        class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10"
    >
        <StorefrontHeader active="blog" />

        <main class="mx-auto max-w-7xl space-y-8 px-4 py-6 md:px-6 md:py-10">
            <nav
                aria-label="مسیر صفحه"
                class="flex flex-wrap items-center gap-2 text-sm text-dh-muted"
            >
                <Link href="/" class="hover:text-dh-700">خانه</Link
                ><span>/</span
                ><Link href="/blog" class="hover:text-dh-700">مجله سلامت</Link
                ><span>/</span
                ><span class="font-semibold text-dh-800">{{
                    category.name
                }}</span>
            </nav>

            <section
                class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-dh-100 md:p-9"
            >
                <div
                    class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between"
                >
                    <div class="max-w-3xl">
                        <span
                            class="rounded-full bg-dh-50 px-3 py-1.5 text-xs font-bold text-dh-700"
                            >دسته‌بندی مجله</span
                        >
                        <h1
                            class="mt-4 text-3xl font-black text-dh-800 md:text-4xl"
                        >
                            {{ category.name }}
                        </h1>
                        <p
                            v-if="category.description"
                            class="mt-3 leading-8 text-dh-muted"
                        >
                            {{ category.description }}
                        </p>
                    </div>
                    <Link
                        href="/blog/categories"
                        class="shrink-0 rounded-xl border border-dh-100 px-4 py-2.5 text-sm font-bold text-dh-700 hover:bg-dh-50"
                        >همه دسته‌ها</Link
                    >
                </div>
            </section>

            <section
                v-if="articles.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
            >
                <article
                    v-for="article in articles"
                    :key="article.id"
                    class="group overflow-hidden rounded-3xl border border-dh-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <Link :href="'/blog/' + article.slug" class="block">
                        <div class="overflow-hidden bg-dh-50">
                            <img
                                v-if="article.featured_image"
                                :src="article.featured_image"
                                :alt="
                                    article.featured_image_alt || article.title
                                "
                                loading="lazy"
                                decoding="async"
                                class="h-52 w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-52 items-center justify-center text-dh-300"
                            >
                                <svg
                                    viewBox="0 0 48 48"
                                    class="size-14"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path d="M9 12h30v24H9z" />
                                    <path d="m13 30 7-7 5 5 4-4 6 6" />
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-3 p-5">
                            <div
                                class="flex items-center justify-between gap-3 text-[11px] text-dh-muted"
                            >
                                <span>{{ category.name }}</span
                                ><span>{{
                                    article.published_at
                                        ? new Date(
                                              article.published_at,
                                          ).toLocaleDateString('fa-IR')
                                        : ''
                                }}</span>
                            </div>
                            <h2
                                class="line-clamp-2 text-lg leading-8 font-black text-dh-800 group-hover:text-dh-700"
                            >
                                {{ article.title }}
                            </h2>
                            <p
                                v-if="article.excerpt"
                                class="line-clamp-3 text-sm leading-7 text-dh-muted"
                            >
                                {{ article.excerpt }}
                            </p>
                            <span
                                class="inline-flex gap-2 pt-1 text-sm font-black text-dh-700"
                                >ادامه مطلب ←</span
                            >
                        </div>
                    </Link>
                </article>
            </section>

            <section
                v-else
                class="rounded-3xl border border-dashed border-dh-200 bg-white p-12 text-center text-dh-muted"
            >
                هنوز مقاله‌ای در این دسته منتشر نشده است.
            </section>

            <nav
                v-if="pagination.last_page > 1"
                aria-label="صفحات دسته‌بندی مجله"
                class="flex flex-wrap items-center justify-center gap-2"
            >
                <Link
                    v-if="pagination.current_page > 1"
                    :href="pageUrl(pagination.current_page - 1)"
                    preserve-scroll
                    class="rounded-xl border border-dh-100 bg-white px-4 py-2.5 text-sm font-bold text-dh-700"
                    >قبلی</Link
                >
                <Link
                    v-for="page in pagination.last_page"
                    :key="page"
                    :href="pageUrl(page)"
                    preserve-scroll
                    :aria-current="
                        page === pagination.current_page ? 'page' : undefined
                    "
                    class="min-w-10 rounded-xl px-3 py-2.5 text-center text-sm font-bold"
                    :class="
                        page === pagination.current_page
                            ? 'bg-dh-700 text-white'
                            : 'border border-dh-100 bg-white text-dh-muted hover:bg-dh-50'
                    "
                    >{{ page.toLocaleString('fa-IR') }}</Link
                >
                <Link
                    v-if="pagination.current_page < pagination.last_page"
                    :href="pageUrl(pagination.current_page + 1)"
                    preserve-scroll
                    class="rounded-xl border border-dh-100 bg-white px-4 py-2.5 text-sm font-bold text-dh-700"
                    >بعدی</Link
                >
            </nav>
        </main>


    </div>
</template>
