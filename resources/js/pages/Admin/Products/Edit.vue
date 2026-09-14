<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProductForm from '@/components/Admin/ProductForm.vue';

type Product = {
    id: number;
    name: string;
    slug: string | null;
    sku: string | null;
    barcode: string | null;
    brand_id: number | null;
    category_id: number | null;
    product_type: string | null;
    unit: string | null;
    quantity_per_unit: number | null;
    short_description: string | null;
    description: string | null;
    seo_title: string | null;
    seo_description: string | null;
    canonical_url: string | null;
    expiry_date: string | null;
    main_image: string | null;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    price: number | null;
    compare_at_price: number | null;
};

defineProps<{
    product: Product;
    brands: { id: number; name: string }[];
    categories: { id: number; name: string }[];
}>();
</script>

<template>
    <Head :title="`ویرایش ${product.name}`" />
    <ProductForm
        :product="product"
        :brands="brands"
        :categories="categories"
        :submit-url="`/admin/products/${product.id}`"
        method="put"
        title="ویرایش محصول"
    />
</template>
