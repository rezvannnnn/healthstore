<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
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
defineProps<{ seo: Seo; categories: Category[]; pagination: Pagination }>();
function pageUrl(page: number): string {
    return page <= 1 ? '/blog/categories' : '/blog/categories?page=' + page;
}
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title
        ><meta name="description" :content="seo.description" /><link
            rel="canonical"
            :href="seo.canonical"
        />
        <meta property="og:type" content="website" /><meta
            property="og:title"
            :content="seo.title"
        /><meta property="og:description" :content="seo.description" /><meta
            property="og:url"
            :content="seo.canonical"
        />
        <meta name="twitter:card" content="summary" /><meta
            name="twitter:title"
            :content="seo.title"
        /><meta name="twitter:description" :content="seo.description" />
    </Head>

    <div
        dir="rtl"
        class="min-h-screen bg-dh-surface pb-24 text-dh-ink lg:pb-10"
    >
        <header
            class="sticky top-0 z-30 border-b border-dh-100/70 bg-white/95 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6"
            >
                <Link
                    href="/"
                    class="flex shrink-0 items-center"
                    aria-label="داروخونه"
                >
                    <BrandLogo imageClass="h-[4.25rem] w-[4.25rem]" />
                </Link>
                <div class="flex items-center gap-2">
                    <Link
                        href="/blog"
                        class="rounded-xl bg-dh-50 px-4 py-2 text-sm font-bold text-dh-700"
                        >مجله</Link
                    ><Link
                        href="/cart"
                        class="rounded-xl bg-dh-700 px-4 py-2 text-sm font-bold text-white"
                        >سبد خرید</Link
                    >
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-8 px-4 py-6 md:px-6 md:py-10">
            <nav
                aria-label="مسیر صفحه"
                class="flex flex-wrap items-center gap-2 text-sm text-dh-muted"
            >
                <Link href="/" class="hover:text-dh-700">خانه</Link
                ><span>/</span
                ><Link href="/blog" class="hover:text-dh-700">مجله سلامت</Link
                ><span>/</span
                ><span class="font-semibold text-dh-800">دسته‌بندی‌ها</span>
            </nav>
            <section
                class="rounded-[2rem] bg-dh-800 p-7 text-white shadow-xl shadow-dh-900/10 md:p-10"
            >
                <p class="text-xs font-bold text-dh-100">مجله سلامت داروخونه</p>
                <h1 class="mt-3 text-3xl font-black md:text-5xl">
                    موضوع مورد علاقه‌تان را پیدا کنید.
                </h1>
                <p
                    class="mt-4 max-w-2xl text-sm leading-7 text-dh-50/85 md:text-base"
                >
                    از مراقبت پوست و مو تا تغذیه و سلامت عمومی؛ مطالب را بر اساس
                    موضوع دنبال کنید.
                </p>
            </section>

            <section
                v-if="categories.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Link
                    v-for="(category, index) in categories"
                    :key="category.id"
                    :href="'/blog/category/' + category.slug"
                    class="group rounded-3xl border border-dh-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-dh-50 text-sm font-black text-dh-700"
                        >{{ (index + 1).toLocaleString('fa-IR') }}</span
                    >
                    <h2
                        class="mt-5 text-xl font-black text-dh-800 group-hover:text-dh-700"
                    >
                        {{ category.name }}
                    </h2>
                    <p
                        v-if="category.description"
                        class="mt-2 line-clamp-3 text-sm leading-7 text-dh-muted"
                    >
                        {{ category.description }}
                    </p>
                    <div
                        class="mt-5 flex items-center justify-between gap-3 border-t border-dh-100 pt-4"
                    >
                        <span class="text-xs text-dh-muted"
                            >{{
                                category.articles_count.toLocaleString('fa-IR')
                            }}
                            مقاله</span
                        ><span class="text-sm font-black text-dh-700"
                            >مشاهده ←</span
                        >
                    </div>
                </Link>
            </section>
            <section
                v-else
                class="rounded-3xl border border-dashed border-dh-200 bg-white p-12 text-center text-dh-muted"
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
                    :aria-label="'صفحه ' + page.toLocaleString('fa-IR')"
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
        <nav
            class="fixed inset-x-0 bottom-0 z-40 border-t border-dh-100 bg-white/95 px-4 py-2 backdrop-blur lg:hidden"
        >
            <div
                class="mx-auto grid max-w-md grid-cols-3 gap-2 text-center text-[11px] font-bold"
            >
                <Link href="/" class="rounded-xl px-2 py-2 text-dh-muted"
                    >خانه</Link
                ><Link
                    href="/products"
                    class="rounded-xl px-2 py-2 text-dh-muted"
                    >فروشگاه</Link
                ><Link
                    href="/blog/categories"
                    class="rounded-xl bg-dh-50 px-2 py-2 text-dh-700"
                    >مجله</Link
                >
            </div>
        </nav>
    </div>
</template>
