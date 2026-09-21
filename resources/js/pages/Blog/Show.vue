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
const props = defineProps<{ article: Article; relatedArticles: RelatedArticle[] }>();
const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: props.article.title,
    description: props.article.seo_description || props.article.excerpt || undefined,
    url: props.article.canonical_url || `/blog/${props.article.slug}`,
    image: props.article.featured_image ? [props.article.featured_image] : undefined,
    datePublished: props.article.published_at || undefined,
    author: props.article.author ? { '@type': 'Person', name: props.article.author.name } : undefined,
    articleSection: props.article.category?.name || undefined,
}));
</script>

<template>
    <Head>
        <title>{{ article.seo_title || article.title }}</title>
        <meta v-if="article.seo_description || article.excerpt" head-key="description" name="description" :content="article.seo_description || article.excerpt || undefined" />
        <link v-if="article.canonical_url" head-key="canonical" rel="canonical" :href="article.canonical_url" />
        <meta property="og:type" content="article" />
        <meta property="og:title" :content="article.seo_title || article.title" />
        <meta v-if="article.seo_description || article.excerpt" property="og:description" :content="article.seo_description || article.excerpt || undefined" />
        <meta v-if="article.canonical_url" property="og:url" :content="article.canonical_url" />
        <meta v-if="article.featured_image" property="og:image" :content="article.featured_image" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="article.seo_title || article.title" />
        <meta v-if="article.seo_description || article.excerpt" name="twitter:description" :content="article.seo_description || article.excerpt || undefined" />
        <meta v-if="article.featured_image" name="twitter:image" :content="article.featured_image" />
        <script type="application/ld+json">{{ JSON.stringify(structuredData) }}</script>
    </Head>

    <div dir="rtl" class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10">
        <header class="sticky top-0 z-30 border-b border-dh-100/70 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6">
                <Link href="/" class="flex min-w-0 items-center gap-3" aria-label="داروخونه">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-dh-50 text-dh-700">
                        <svg viewBox="0 0 48 48" class="size-8" aria-hidden="true" fill="none"><path d="M10 19h28l-3 16H13l-3-16Z" stroke="currentColor" stroke-width="2.5"/><path d="M15 19c1-5 4-8 9-8s8 3 9 8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M25 13c-3 1-5 4-5 7 4 0 7-2 8-6" stroke="#63b95b" stroke-width="2.5" stroke-linecap="round"/></svg>
                    </span>
                    <span class="min-w-0"><span class="block truncate text-lg font-black text-dh-800">داروخونه</span><span class="hidden text-[10px] font-medium text-dh-muted sm:block">دارو و محصولات بهداشتی</span></span>
                </Link>
                <div class="flex items-center gap-2"><Link href="/blog" class="rounded-xl px-4 py-2 text-sm font-bold text-dh-700 hover:bg-dh-50">مجله</Link><Link href="/cart" class="rounded-xl bg-dh-700 px-4 py-2 text-sm font-bold text-white">سبد خرید</Link></div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-6 md:px-6 md:py-10">
            <nav aria-label="مسیر صفحه" class="mb-6 flex flex-wrap items-center gap-2 text-sm text-dh-muted">
                <Link href="/" class="hover:text-dh-700">خانه</Link><span>/</span><Link href="/blog" class="hover:text-dh-700">مجله سلامت</Link><template v-if="article.category"><span>/</span><Link :href="`/blog/category/${article.category.slug}`" class="hover:text-dh-700">{{ article.category.name }}</Link></template><span>/</span><span class="font-semibold text-dh-800">مقاله</span>
            </nav>

            <article class="overflow-hidden rounded-[2rem] border border-dh-100 bg-white shadow-sm">
                <div v-if="article.featured_image" class="bg-dh-50">
                    <img :src="article.featured_image" :alt="article.featured_image_alt || article.title" class="max-h-[34rem] w-full object-cover" />
                </div>
                <div class="space-y-7 p-6 md:p-10 lg:p-14">
                    <div class="flex flex-wrap items-center gap-2">
                        <Link v-if="article.category" :href="`/blog/category/${article.category.slug}`" class="rounded-full bg-dh-50 px-3 py-1.5 text-xs font-bold text-dh-700">{{ article.category.name }}</Link>
                        <span class="text-xs text-dh-muted">{{ article.published_at ? new Date(article.published_at).toLocaleDateString('fa-IR') : '' }}</span>
                    </div>
                    <header>
                        <h1 class="text-3xl font-black leading-[1.45] text-dh-800 md:text-5xl">{{ article.title }}</h1>
                        <p v-if="article.excerpt" class="mt-5 max-w-3xl text-base leading-8 text-dh-muted md:text-lg">{{ article.excerpt }}</p>
                    </header>
                    <div v-if="article.author?.name" class="flex items-center gap-3 border-y border-dh-100 py-4 text-sm">
                        <span class="flex size-9 items-center justify-center rounded-full bg-dh-50 font-black text-dh-700">{{ article.author.name.slice(0, 1) }}</span>
                        <span class="text-dh-muted">نویسنده:</span><strong class="text-dh-800">{{ article.author.name }}</strong>
                    </div>
                    <div class="prose prose-slate max-w-none text-base leading-9 whitespace-pre-wrap text-dh-800 md:text-lg">{{ article.content }}</div>
                </div>
            </article>

            <section v-if="relatedArticles.length" class="mt-10 space-y-4">
                <div class="flex items-end justify-between gap-4"><div><p class="text-xs font-bold text-dh-600">ادامه مطالعه</p><h2 class="mt-1 text-2xl font-black text-dh-800">مطالب مرتبط</h2></div><Link v-if="article.category" :href="`/blog/category/${article.category.slug}`" class="text-sm font-bold text-dh-700">مشاهده بیشتر</Link></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <Link v-for="related in relatedArticles" :key="related.id" :href="`/blog/${related.slug}`" class="group overflow-hidden rounded-3xl border border-dh-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                        <div class="overflow-hidden bg-dh-50">
                            <img v-if="related.featured_image" :src="related.featured_image" :alt="related.featured_image_alt || related.title" class="h-44 w-full object-cover transition duration-500 group-hover:scale-105" />
                            <div v-else class="h-44 bg-dh-50"></div>
                        </div>
                        <div class="space-y-2 p-5"><div class="text-[11px] text-dh-muted">{{ related.published_at ? new Date(related.published_at).toLocaleDateString('fa-IR') : '' }}</div><h3 class="line-clamp-2 text-lg font-black leading-8 text-dh-800 group-hover:text-dh-700">{{ related.title }}</h3><p v-if="related.excerpt" class="line-clamp-2 text-sm leading-7 text-dh-muted">{{ related.excerpt }}</p></div>
                    </Link>
                </div>
            </section>
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-dh-100 bg-white/95 px-4 py-2 backdrop-blur lg:hidden"><div class="mx-auto grid max-w-md grid-cols-3 gap-2 text-center text-[11px] font-bold"><Link href="/" class="rounded-xl px-2 py-2 text-dh-muted">خانه</Link><Link href="/products" class="rounded-xl px-2 py-2 text-dh-muted">فروشگاه</Link><Link href="/blog" class="rounded-xl bg-dh-50 px-2 py-2 text-dh-700">مجله</Link></div></nav>
    </div>
</template>
