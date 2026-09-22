<script setup lang="ts">
withDefaults(
    defineProps<{
        class?: string;
        imageClass?: string;
        label?: string;
    }>(),
    {
        class: '',
        imageClass: '',
        label: 'داروخونه',
    },
);
</script>

<template>
    <span :class="['inline-flex shrink-0 items-center', $props.class]">
        <img
            src="/images/darukhooneh-logo.webp"
            :alt="label"
            :class="['h-16 w-16 object-contain', imageClass]"
        />
    </span>
</template>
