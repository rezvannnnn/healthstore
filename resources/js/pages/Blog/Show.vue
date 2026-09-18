<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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

interface RelatedArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image: string | null;
    featured_image_alt: string | null;
    published_at: string | null;
}

const props = defineProps<{
    article: Article;
    relatedArticles: RelatedArticle[];
}>();
const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: props.article.title,
    description:
        props.article.seo_description || props.article.excerpt || undefined,
    url: props.article.canonical_url || `/blog/${props.article.slug}`,
    image: props.article.featured_image
        ? [props.article.featured_image]
        : undefined,
    datePublished: props.article.published_at || undefined,
    author: props.article.author
        ? { '@type': 'Person', name: props.article.author.name }
        : undefined,
    articleSection: props.article.category?.name || undefined,
}));
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
        <script type="application/ld+json">
            {{ JSON.stringify(structuredData) }}
        </script>
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

        <section v-if="relatedArticles.length" class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold">مطالب مرتبط</h2>
                <Link
                    v-if="article.category"
                    :href="`/blog/category/${article.category.slug}`"
                    class="text-sm underline"
                >
                    مطالب بیشتر در {{ article.category.name }}
                </Link>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <Link
                    v-for="related in relatedArticles"
                    :key="related.id"
                    :href="`/blog/${related.slug}`"
                    class="overflow-hidden rounded-2xl border bg-white transition hover:shadow-md"
                >
                    <img
                        v-if="related.featured_image"
                        :src="related.featured_image"
                        :alt="related.featured_image_alt || related.title"
                        class="h-40 w-full object-cover"
                    />
                    <div class="space-y-2 p-4">
                        <h3 class="leading-7 font-bold">{{ related.title }}</h3>
                        <p
                            v-if="related.excerpt"
                            class="line-clamp-2 text-sm leading-6 text-gray-600"
                        >
                            {{ related.excerpt }}
                        </p>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
