<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface StructuredDataProps {
    [key: string]: unknown;
}

const page = usePage();
const json = computed(() => {
    const data = (page.props as Record<string, unknown>).structuredData as
        | StructuredDataProps
        | undefined;

    return data ? JSON.stringify(data).replace(/</g, '\\u003c') : '';
});
</script>

<template>
    <script v-if="json" type="application/ld+json" v-html="json"></script>
</template>
