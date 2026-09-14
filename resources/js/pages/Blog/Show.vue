<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Article {
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    featured_image: string | null;
    featured_image_alt: string | null;
    seo_title: string | null;
    seo_description: string | null;
    canonical_url: string | null;
    category: { id: number; name: string; slug: string } | null;
    author: { id: number; name: string } | null;
    published_at: string | null;
}

defineProps<{ article: Article }>();
</script>

<template>
    <Head>
        <title>{{ article.seo_title || article.title }}</title>
        <meta
            v-if="article.seo_description || article.excerpt"
            head-key="description"
            name="description"
            :content="article.seo_description || article.excerpt || undefined"
        />
        <link
            v-if="article.canonical_url"
            head-key="canonical"
            rel="canonical"
            :href="article.canonical_url"
        />
        <meta property="og:type" content="article" />
        <meta
            property="og:title"
            :content="article.seo_title || article.title"
        />
        <meta
            v-if="article.seo_description || article.excerpt"
            property="og:description"
            :content="article.seo_description || article.excerpt || undefined"
        />
        <meta
            v-if="article.canonical_url"
            property="og:url"
            :content="article.canonical_url"
        />
        <meta
            v-if="article.featured_image"
            property="og:image"
            :content="article.featured_image"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            :content="article.seo_title || article.title"
        />
        <meta
            v-if="article.seo_description || article.excerpt"
            name="twitter:description"
            :content="article.seo_description || article.excerpt || undefined"
        />
        <meta
            v-if="article.featured_image"
            name="twitter:image"
            :content="article.featured_image"
        />
    </Head>
    <div class="mx-auto max-w-4xl space-y-6 p-6" dir="rtl">
        <Link href="/blog" class="text-sm underline"
            >← بازگشت به مجله سلامت</Link
        >
        <article class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <img
                v-if="article.featured_image"
                :src="article.featured_image"
                :alt="article.featured_image_alt || article.title"
                class="max-h-[28rem] w-full object-cover"
            />
            <div class="space-y-6 p-6 md:p-10">
                <div class="text-sm text-gray-500">
                    {{ article.category?.name || 'سلامت' }}
                </div>
                <h1 class="text-3xl leading-tight font-bold md:text-4xl">
                    {{ article.title }}
                </h1>
                <p
                    v-if="article.excerpt"
                    class="text-lg leading-8 text-gray-600"
                >
                    {{ article.excerpt }}
                </p>
                <div class="text-xs text-gray-500">
                    {{
                        article.author?.name
                            ? `نویسنده: ${article.author.name} · `
                            : ''
                    }}{{
                        article.published_at
                            ? new Date(article.published_at).toLocaleDateString(
                                  'fa-IR',
                              )
                            : ''
                    }}
                </div>
                <div
                    class="text-base leading-9 whitespace-pre-wrap text-gray-800"
                >
                    {{ article.content }}
                </div>
            </div>
        </article>
    </div>
</template>
