<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface StructuredDataProps {
    [key: string]: unknown;
}

const page = usePage();
const json = computed(() => {
    const data = (page.props as Record<string, unknown>).structuredData as
        | StructuredDataProps
        | undefined;

    return data
        ? JSON.stringify(data).replace(/</g, '\\u003c')
        : '';
});
</script>

<template>
    <script
        v-if="json"
        type="application/ld+json"
        v-html="json"
    ></script>
</template>
