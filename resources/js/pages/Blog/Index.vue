<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';

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
    if (page > 1) { params.set('page', String(page)); }

    const currentSearch = new URLSearchParams(window.location.search).get('search');
    if (currentSearch) { params.set('search', currentSearch); }

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
        <meta name="twitter:description" :content="seo.description" />
    </Head>

    <div dir="rtl" class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10">
        <header class="sticky top-0 z-30 border-b border-dh-100/70 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6">
                <Link href="/" class="flex shrink-0 items-center" aria-label="داروخونه">
                        <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                    </Link>
                <div class="hidden items-center gap-2 md:flex">
                    <Link href="/products" class="rounded-xl px-4 py-2 text-sm font-semibold text-dh-muted hover:bg-dh-50 hover:text-dh-800">فروشگاه</Link>
                    <Link href="/cart" class="rounded-xl bg-dh-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-dh-800">سبد خرید</Link>
                </div>
                <Link href="/cart" class="rounded-xl border border-dh-100 p-2.5 text-dh-700 md:hidden" aria-label="سبد خرید">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l1 12H5L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg>
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-8 px-4 py-6 md:px-6 md:py-10">
            <section class="overflow-hidden rounded-[2rem] bg-dh-800 p-6 text-white shadow-xl shadow-dh-900/10 md:p-10">
                <div class="relative">
                    <div class="absolute -left-10 -top-20 size-56 rounded-full bg-dh-600/30 blur-3xl"></div>
                    <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div class="max-w-2xl">
                            <Link href="/blog" class="inline-flex rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-dh-50 ring-1 ring-white/15">مجله سلامت</Link>
                            <h1 class="mt-5 text-3xl font-black leading-tight md:text-5xl">دانش سلامت،<br class="sm:hidden" /> ساده و کاربردی</h1>
                            <p class="mt-4 max-w-xl text-sm leading-7 text-dh-50/85 md:text-base">راهنماها و مطالب آموزشی برای انتخاب بهتر محصولات، مراقبت روزانه و سبک زندگی سالم.</p>
                        </div>
                        <div class="hidden rounded-3xl border border-white/10 bg-white/10 p-5 lg:block">
                            <div class="text-3xl font-black">{{ pagination.total.toLocaleString('fa-IR') }}</div>
                            <div class="mt-1 text-sm text-dh-50/75">مطلب منتشرشده</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <form method="get" action="/blog" class="flex gap-2 rounded-2xl border border-dh-100 bg-white p-2 shadow-sm">
                    <label for="blog-search" class="sr-only">جستجو در مقالات</label>
                    <div class="flex min-w-0 flex-1 items-center gap-2 px-3">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-dh-muted" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/></svg>
                        <input id="blog-search" name="search" :value="filters.search" class="w-full border-0 bg-transparent py-2.5 text-sm outline-none placeholder:text-dh-muted focus:ring-0" placeholder="مثلاً: ویتامین D، مراقبت پوست..." />
                    </div>
                    <button class="rounded-xl bg-dh-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-dh-800">جستجو</button>
                </form>
                <Link href="/blog/categories" class="rounded-2xl border border-dh-100 bg-white px-5 py-3 text-center text-sm font-bold text-dh-700 shadow-sm hover:bg-dh-50">مشاهده همه دسته‌ها</Link>
            </section>

            <section v-if="categories.length" class="space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-black">موضوعات مجله</h2>
                    <Link href="/blog/categories" class="text-xs font-bold text-dh-700">همه دسته‌ها</Link>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <Link v-for="category in categories" :key="category.id" :href="'/blog/category/' + category.slug" class="shrink-0 rounded-full border border-dh-100 bg-white px-4 py-2 text-sm font-semibold text-dh-muted shadow-sm hover:border-dh-200 hover:text-dh-800">{{ category.name }}</Link>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold text-dh-600">تازه‌های مجله</p>
                        <h2 class="mt-1 text-2xl font-black">مطالب سلامت</h2>
                    </div>
                    <span class="text-xs text-dh-muted">{{ pagination.total.toLocaleString('fa-IR') }} نتیجه</span>
                </div>

                <div v-if="articles.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="article in articles" :key="article.id" class="group overflow-hidden rounded-3xl border border-dh-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <Link :href="`/blog/${article.slug}`" class="block">
                            <div class="relative overflow-hidden bg-dh-50">
                                <img v-if="article.featured_image" :src="article.featured_image" :alt="article.featured_image_alt || article.title" loading="lazy" decoding="async" class="h-52 w-full object-cover transition duration-500 group-hover:scale-105" />
                                <div v-else class="flex h-52 items-center justify-center bg-dh-50 text-dh-300">
                                    <svg viewBox="0 0 48 48" class="size-14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12h30v24H9z"/><path d="m13 30 7-7 5 5 4-4 6 6"/><circle cx="18" cy="19" r="2.5"/></svg>
                                </div>
                                <span class="absolute right-4 top-4 rounded-full bg-white/95 px-3 py-1 text-[11px] font-bold text-dh-700 shadow-sm">{{ article.category?.name || 'سلامت' }}</span>
                            </div>
                            <div class="space-y-3 p-5">
                                <div class="text-[11px] text-dh-muted">{{ article.published_at ? new Date(article.published_at).toLocaleDateString('fa-IR') : 'مجله سلامت' }}</div>
                                <h3 class="line-clamp-2 text-lg font-black leading-8 text-dh-800 group-hover:text-dh-700">{{ article.title }}</h3>
                                <p v-if="article.excerpt" class="line-clamp-3 text-sm leading-7 text-dh-muted">{{ article.excerpt }}</p>
                                <span class="inline-flex items-center gap-2 pt-1 text-sm font-black text-dh-700">ادامه مطلب <span aria-hidden="true">←</span></span>
                            </div>
                        </Link>
                    </article>
                </div>

                <div v-else class="rounded-3xl border border-dashed border-dh-200 bg-white p-12 text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-dh-50 text-dh-600">
                        <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                    </div>
                    <h2 class="mt-4 font-black">مقاله‌ای پیدا نشد</h2>
                    <p class="mt-2 text-sm text-dh-muted">عبارت جستجو را تغییر دهید یا همه مطالب را ببینید.</p>
                    <Link href="/blog" class="mt-5 inline-flex rounded-xl bg-dh-700 px-5 py-2.5 text-sm font-bold text-white">نمایش همه مطالب</Link>
                </div>
            </section>

            <nav v-if="pagination.last_page > 1" aria-label="صفحات مجله" class="flex flex-wrap items-center justify-center gap-2 pt-2">
                <Link v-if="pagination.current_page > 1" :href="pageUrl(pagination.current_page - 1)" preserve-scroll aria-label="صفحه قبلی" class="rounded-xl border border-dh-100 bg-white px-4 py-2.5 text-sm font-bold text-dh-700">قبلی</Link>
                <Link v-for="page in pagination.last_page" :key="page" :href="pageUrl(page)" preserve-scroll :aria-current="page === pagination.current_page ? 'page' : undefined" :aria-label="`صفحه ${page.toLocaleString('fa-IR')}`" class="min-w-10 rounded-xl px-3 py-2.5 text-center text-sm font-bold" :class="page === pagination.current_page ? 'bg-dh-700 text-white shadow-sm' : 'border border-dh-100 bg-white text-dh-muted hover:bg-dh-50'">{{ page.toLocaleString('fa-IR') }}</Link>
                <Link v-if="pagination.current_page < pagination.last_page" :href="pageUrl(pagination.current_page + 1)" preserve-scroll aria-label="صفحه بعدی" class="rounded-xl border border-dh-100 bg-white px-4 py-2.5 text-sm font-bold text-dh-700">بعدی</Link>
            </nav>
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-dh-100 bg-white/95 px-4 py-2 backdrop-blur lg:hidden">
            <div class="mx-auto grid max-w-md grid-cols-3 gap-2 text-center text-[11px] font-bold">
                <Link href="/" class="rounded-xl px-2 py-2 text-dh-muted">خانه</Link>
                <Link href="/products" class="rounded-xl px-2 py-2 text-dh-muted">فروشگاه</Link>
                <Link href="/blog" class="rounded-xl bg-dh-50 px-2 py-2 text-dh-700">مجله</Link>
            </div>
        </nav>
    </div>
</template>
