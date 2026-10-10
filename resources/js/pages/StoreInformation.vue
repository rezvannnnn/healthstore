<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StorefrontHeader from '@/components/StorefrontHeader.vue';
import StorefrontFooter from '@/components/StorefrontFooter.vue';
defineProps<{
    section: string;
    title: string;
    content: string;
    supportPhone: string;
    supportEmail: string;
    storeAddress: string;
}>();
</script>
<template>
    <Head :title="title" />
    <StorefrontHeader />
    <main dir="rtl" class="mx-auto min-h-96 max-w-4xl space-y-5 px-4 py-10">
        <h1 class="text-3xl font-black">{{ title }}</h1>
        <p v-if="content" class="leading-9 whitespace-pre-wrap">
            {{ content }}
        </p>
        <p v-else-if="section !== 'contact'">
            این اطلاعات هنوز توسط مدیریت تکمیل نشده است. پیش از خرید با پشتیبانی
            هماهنگ کنید.
        </p>
        <a v-if="supportPhone" :href="'tel:' + supportPhone" class="block"
            >تلفن: {{ supportPhone }}</a
        >
        <a v-if="supportEmail" :href="'mailto:' + supportEmail" class="block"
            >ایمیل: {{ supportEmail }}</a
        >
        <p v-if="storeAddress">نشانی: {{ storeAddress }}</p>
        <p v-if="!supportPhone && !supportEmail">
            اطلاعات تماس هنوز ثبت نشده است.
        </p>
    </main>
    <StorefrontFooter />
</template>
