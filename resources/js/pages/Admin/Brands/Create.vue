<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
const form = useForm({
    name: '',
    slug: '',
    description: '',
    logo: '',
    is_active: true,
});
const submit = () => form.post('/admin/brands');
</script>

<template>
    <Head title="ایجاد برند" />
    <div dir="rtl" class="min-h-screen bg-dh-50 px-4 py-8 text-dh-900">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-dh-muted">مدیریت / برندها</p>
                    <h1 class="text-3xl font-bold">برند جدید</h1>
                </div>
                <Link
                    href="/admin/brands"
                    class="rounded-lg border bg-white px-4 py-2 text-sm"
                    >بازگشت</Link
                >
            </div>
            <form
                @submit.prevent="submit"
                class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
            >
                <div>
                    <label class="mb-1 block text-sm font-medium">نام</label
                    ><input
                        v-model="form.name"
                        class="w-full rounded-lg border px-3 py-2"
                        required
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Slug</label
                    ><input
                        v-model="form.slug"
                        class="w-full rounded-lg border px-3 py-2"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">توضیحات</label
                    ><textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full rounded-lg border px-3 py-2"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">لوگو</label
                    ><input
                        v-model="form.logo"
                        class="w-full rounded-lg border px-3 py-2"
                        placeholder="مسیر یا URL لوگو"
                    />
                </div>
                <label class="flex items-center gap-2"
                    ><input v-model="form.is_active" type="checkbox" />
                    فعال</label
                >
                <button
                    :disabled="form.processing"
                    class="rounded-lg bg-dh-700 px-5 py-2.5 font-medium text-white disabled:opacity-50"
                >
                    ذخیره برند
                </button>
            </form>
        </div>
    </div>
</template>
