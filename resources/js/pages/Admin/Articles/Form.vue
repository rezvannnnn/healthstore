<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Category { id: number; name: string }
interface ArticleData {
    id?: number;
    category_id: number | null;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    featured_image: string | null;
    featured_image_alt: string | null;
    seo_title: string | null;
    seo_description: string | null;
    canonical_url: string | null;
    is_active: boolean;
    is_featured: boolean;
    published_at: string | null;
}

interface Props { article?: ArticleData; categories: Category[] }
const props = defineProps<Props>();
const editing = Boolean(props.article?.id);

const form = useForm({
    category_id: props.article?.category_id ?? null,
    title: props.article?.title ?? '',
    slug: props.article?.slug ?? '',
    excerpt: props.article?.excerpt ?? '',
    content: props.article?.content ?? '',
    featured_image: props.article?.featured_image ?? '',
    featured_image_alt: props.article?.featured_image_alt ?? '',
    seo_title: props.article?.seo_title ?? '',
    seo_description: props.article?.seo_description ?? '',
    canonical_url: props.article?.canonical_url ?? '',
    is_active: props.article?.is_active ?? true,
    is_featured: props.article?.is_featured ?? false,
    published_at: props.article?.published_at ? props.article.published_at.slice(0, 16) : '',
});

function submit() {
    if (props.article?.id) {
        form.put(`/admin/articles/${props.article.id}`);
    } else {
        form.post('/admin/articles');
    }
}
</script>

<template>
    <Head :title="editing ? 'ویرایش مقاله' : 'مقاله جدید'" />
    <div class="mx-auto max-w-5xl space-y-6 p-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div><h1 class="text-2xl font-bold">{{ editing ? 'ویرایش مقاله' : 'مقاله جدید' }}</h1><p class="mt-1 text-sm text-gray-500">محتوای مقاله و تنظیمات سئو</p></div>
            <Link href="/admin/articles" class="rounded-lg border px-4 py-2 text-sm">بازگشت</Link>
        </div>

        <form class="space-y-5 rounded-xl border bg-white p-5 shadow-sm" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1"><span>عنوان</span><input v-model="form.title" class="w-full rounded-lg border p-2" /></label>
                <label class="space-y-1"><span>اسلاگ</span><input v-model="form.slug" class="w-full rounded-lg border p-2" placeholder="health-guide" /></label>
                <label class="space-y-1"><span>دسته</span><select v-model="form.category_id" class="w-full rounded-lg border p-2"><option :value="null">بدون دسته</option><option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
                <label class="space-y-1"><span>تاریخ انتشار</span><input v-model="form.published_at" type="datetime-local" class="w-full rounded-lg border p-2" /></label>
            </div>

            <label class="block space-y-1"><span>خلاصه</span><textarea v-model="form.excerpt" rows="3" class="w-full rounded-lg border p-2" /></label>
            <label class="block space-y-1"><span>متن مقاله</span><textarea v-model="form.content" rows="14" class="w-full rounded-lg border p-2 font-sans" /></label>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1"><span>آدرس تصویر شاخص</span><input v-model="form.featured_image" class="w-full rounded-lg border p-2" /></label>
                <label class="space-y-1"><span>Alt تصویر</span><input v-model="form.featured_image_alt" class="w-full rounded-lg border p-2" /></label>
            </div>

            <div class="border-t pt-5"><h2 class="mb-3 text-lg font-semibold">SEO</h2><div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1"><span>SEO Title</span><input v-model="form.seo_title" class="w-full rounded-lg border p-2" maxlength="255" /></label>
                <label class="space-y-1"><span>Canonical URL</span><input v-model="form.canonical_url" class="w-full rounded-lg border p-2" /></label>
                <label class="space-y-1 md:col-span-2"><span>Meta Description</span><textarea v-model="form.seo_description" rows="3" class="w-full rounded-lg border p-2" maxlength="320" /></label>
            </div></div>

            <div class="flex flex-wrap gap-6 border-t pt-5"><label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" /> فعال</label><label class="flex items-center gap-2"><input v-model="form.is_featured" type="checkbox" /> ویژه</label></div>

            <div v-if="Object.keys(form.errors).length" class="space-y-1 text-sm text-red-600"><div v-for="(message, field) in form.errors" :key="field">{{ message }}</div></div>
            <button :disabled="form.processing" class="rounded-lg bg-black px-5 py-2 text-white disabled:opacity-50">{{ form.processing ? 'در حال ذخیره...' : 'ذخیره مقاله' }}</button>
        </form>
    </div>
</template>
