<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

interface SharedProps {
    cart?: {
        items_count?: number;
    };
}

const page = usePage<SharedProps>();
</script>

<template>
    <Link
        href="/cart"
        class="relative flex size-11 items-center justify-center rounded-2xl border border-dh-100 bg-white text-dh-700 transition hover:border-dh-300 hover:bg-dh-50"
        aria-label="سبد خرید"
    >
        <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
            <path d="M4 5h2l1.5 10.2a2 2 0 0 0 2 1.8h7.6a2 2 0 0 0 2-1.7L20 8H7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="10" cy="20" r="1.25" fill="currentColor" />
            <circle cx="18" cy="20" r="1.25" fill="currentColor" />
        </svg>
        <span
            v-if="(page.props.cart?.items_count ?? 0) > 0"
            class="absolute -right-1.5 -top-1.5 flex min-w-5 items-center justify-center rounded-full bg-dh-green-500 px-1.5 py-0.5 text-[10px] font-black leading-none text-white ring-2 ring-white"
            aria-hidden="true"
        >
            {{ page.props.cart?.items_count }}
        </span>
    </Link>
</template>
