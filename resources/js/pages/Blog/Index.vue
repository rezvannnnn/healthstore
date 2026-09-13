<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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

defineProps<{
    articles: Article[];
    pagination: { current_page: number; last_page: number; total: number };
    filters: { search: string };
}>();
</script>

<template>
    <Head title="مجله سلامت" />
    <div class="mx-auto max-w-7xl space-y-8 p-6" dir="rtl">
        <header><h1 class="text-3xl font-bold">مجله سلامت</h1><p class="mt-2 text-gray-500">مطالب آموزشی و کاربردی درباره سلامت و محصولات بهداشتی</p></header>
        <form method="get" action="/blog" class="flex gap-3"><input name="search" :value="filters.search" class="min-w-0 flex-1 rounded-lg border p-3" placeholder="جستجو در مقالات" /><button class="rounded-lg border px-5">جستجو</button></form>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <article v-for="article in articles" :key="article.id" class="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <img v-if="article.featured_image" :src="article.featured_image" :alt="article.featured_image_alt || article.title" class="h-48 w-full object-cover" />
                <div class="space-y-3 p-5"><div class="text-xs text-gray-500">{{ article.category?.name || 'سلامت' }}</div><h2 class="text-xl font-semibold">{{ article.title }}</h2><p v-if="article.excerpt" class="line-clamp-3 text-sm leading-7 text-gray-600">{{ article.excerpt }}</p><Link :href="`/blog/${article.slug}`" class="inline-block text-sm font-semibold underline">ادامه مطلب</Link></div>
            </article>
        </div>
        <div v-if="articles.length === 0" class="rounded-xl border bg-white p-12 text-center text-gray-500">مقاله‌ای پیدا نشد.</div>
    </div>
</template>
