<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

interface Article {
    id: number;
    title: string;
    slug: string;
    category: string | null;
    author: string | null;
    is_active: boolean;
    is_featured: boolean;
    published_at: string | null;
    created_at: string | null;
}

interface Props {
    articles: Article[];
    pagination: {
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; status: string };
}

const props = defineProps<Props>();

function remove(article: Article) {
    if (!window.confirm(`مقاله «${article.title}» حذف شود؟`)) {
        return;
    }

    router.delete(`/admin/articles/${article.id}`);
}

function goToPage(page: number) {
    if (
        page < 1 ||
        page > props.pagination.last_page ||
        page === props.pagination.current_page
    ) {
        return;
    }

    router.get(
        '/admin/articles',
        {
            search: props.filters.search || undefined,
            status: props.filters.status || undefined,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function formatDate(value: string | null) {
    return value ? new Date(value).toLocaleDateString('fa-IR') : '—';
}
</script>

<template>
    <Head title="مقالات" />
    <div class="mx-auto max-w-7xl space-y-6 p-6" dir="rtl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">مقالات و محتوای سئو</h1>
                <p class="mt-1 text-sm text-dh-muted">
                    مدیریت مقالات وبلاگ، انتشار و اطلاعات سئو
                </p>
            </div>
            <Link
                href="/admin/articles/create"
                class="rounded-lg bg-black px-4 py-2 text-sm text-white"
            >
                مقاله جدید
            </Link>
        </div>

        <form
            method="get"
            action="/admin/articles"
            class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3"
        >
            <input
                name="search"
                :value="props.filters.search"
                class="rounded-lg border p-2"
                placeholder="جستجوی عنوان یا اسلاگ"
            />
            <select
                name="status"
                :value="props.filters.status"
                class="rounded-lg border p-2"
            >
                <option value="all">همه</option>
                <option value="published">منتشرشده</option>
                <option value="draft">پیش‌نویس</option>
            </select>
            <button class="rounded-lg border px-4 py-2">فیلتر</button>
        </form>

        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <table class="w-full text-right text-sm">
                <thead class="border-b bg-dh-50">
                    <tr>
                        <th class="p-3">عنوان</th>
                        <th class="p-3">دسته</th>
                        <th class="p-3">وضعیت</th>
                        <th class="p-3">انتشار</th>
                        <th class="p-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="article in props.articles"
                        :key="article.id"
                        class="border-b last:border-0"
                    >
                        <td class="p-3">
                            <div class="font-semibold">{{ article.title }}</div>
                            <div class="text-xs text-dh-muted">
                                /{{ article.slug }}
                            </div>
                        </td>
                        <td class="p-3">
                            {{ article.category || 'بدون دسته' }}
                        </td>
                        <td class="p-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="
                                    article.is_active &&
                                    article.published_at &&
                                    new Date(article.published_at) <= new Date()
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                                        : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                "
                            >
                                {{
                                    article.is_active &&
                                    article.published_at &&
                                    new Date(article.published_at) <= new Date()
                                        ? 'منتشرشده'
                                        : 'پیش‌نویس'
                                }}
                            </span>
                        </td>
                        <td class="p-3">
                            {{ formatDate(article.published_at) }}
                        </td>
                        <td class="flex gap-2 p-3">
                            <Link
                                :href="`/admin/articles/${article.id}/edit`"
                                class="rounded border px-3 py-1"
                            >
                                ویرایش
                            </Link>
                            <button
                                class="rounded border border-red-300 px-3 py-1 text-red-600"
                                @click="remove(article)"
                            >
                                حذف
                            </button>
                        </td>
                    </tr>
                    <tr v-if="props.articles.length === 0">
                        <td colspan="5" class="p-8 text-center text-dh-muted">
                            هنوز مقاله‌ای ثبت نشده است.
                        </td>
                    </tr>
                </tbody>
            </table>
            <div
                v-if="props.pagination.last_page > 1"
                class="flex items-center justify-between gap-4 border-t p-4 text-sm"
            >
                <span class="text-dh-muted">
                    {{ props.pagination.total }} مقاله
                </span>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="props.pagination.current_page === 1"
                        class="rounded border px-3 py-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="goToPage(props.pagination.current_page - 1)"
                    >
                        قبلی
                    </button>
                    <span class="min-w-24 text-center">
                        صفحه {{ props.pagination.current_page }} از
                        {{ props.pagination.last_page }}
                    </span>
                    <button
                        :disabled="
                            props.pagination.current_page ===
                            props.pagination.last_page
                        "
                        class="rounded border px-3 py-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="goToPage(props.pagination.current_page + 1)"
                    >
                        بعدی
                    </button>
                </div>
            </div>
            <div v-else class="border-t p-4 text-sm text-dh-muted">
                {{ props.pagination.total }} مقاله
            </div>
        </div>
    </div>
</template>
