<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, watchEffect } from 'vue';

interface StructuredDataProps {
    [key: string]: unknown;
}

const page = usePage();

let scriptElement: HTMLScriptElement | null = null;

watchEffect(() => {
    const data = (page.props as Record<string, unknown>).structuredData as
        | StructuredDataProps
        | undefined;

    if (typeof document === 'undefined') {
        return;
    }

    if (!data) {
        scriptElement?.remove();
        scriptElement = null;
        return;
    }

    if (!scriptElement) {
        scriptElement = document.createElement('script');
        scriptElement.type = 'application/ld+json';
        scriptElement.dataset.darukhoonehStructuredData = 'true';
        document.head.appendChild(scriptElement);
    }

    scriptElement.textContent = JSON.stringify(data).replace(/</g, '\\u003c');
});

onBeforeUnmount(() => {
    scriptElement?.remove();
    scriptElement = null;
});
</script>
