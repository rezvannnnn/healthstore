<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
const page = usePage();
const box = ref<HTMLElement | null>(null);
const errors = computed(() =>
    Object.values(page.props.errors ?? {}).filter(Boolean),
);
const flash = computed(
    () => (page.props.flash ?? {}) as Record<string, string | null>,
);
watch(errors, async (values) => {
    if (values.length) {
        await nextTick();
        box.value?.focus();
    }
});
</script>
<template>
    <section
        v-if="errors.length || Object.values(flash).some(Boolean)"
        ref="box"
        tabindex="-1"
        aria-live="polite"
        class="mx-auto max-w-6xl px-4 py-2"
        dir="rtl"
    >
        <div
            v-if="errors.length"
            role="alert"
            class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"
        >
            <p class="font-bold">لطفاً موارد زیر را بررسی کنید:</p>
            <ul>
                <li v-for="(error, index) in errors" :key="index">
                    {{ error }}
                </li>
            </ul>
        </div>
        <template v-for="(message, kind) in flash" :key="kind"
            ><p
                v-if="message"
                class="my-2 rounded-xl border bg-white p-3 text-dh-800"
            >
                {{ message }}
            </p></template
        >
    </section>
</template>
