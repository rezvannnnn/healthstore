<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProductForm from '@/components/Admin/ProductForm.vue';

defineProps<{
    product: null;
    brands: { id: number; name: string }[];
    categories: { id: number; name: string }[];
}>();
</script>

<template>
    <Head title="ایجاد محصول" />
    <ProductForm
        :product="product"
        :brands="brands"
        :categories="categories"
        submit-url="/admin/products"
        method="post"
        title="ایجاد محصول جدید"
    />
</template>
