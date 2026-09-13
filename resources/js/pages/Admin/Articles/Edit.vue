<script setup lang="ts">
import Form from './Form.vue';

interface Article {
    id: number;
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

defineProps<{
    article: Article;
    categories: { id: number; name: string }[];
}>();
</script>

<template><Form :article="article" :categories="categories" /></template>
