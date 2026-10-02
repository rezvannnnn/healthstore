<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

type Parent = { id: number; name: string };
const props = defineProps<{ parents: Parent[] }>();
const form = useForm({
    name: '',
    slug: '',
    parent_id: null as number | null,
    description: '',
    image: '',
    image_file: null as File | null,
    is_active: true,
    sort_order: 0,
});
const handleImageChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.image_file = input.files?.[0] ?? null;
};

const submit = () => form.post('/admin/categories', { forceFormData: true });
</script>

<template>
    <Head title="ایجاد دسته‌بندی" />
    <div dir="rtl" class="min-h-screen bg-dh-50 px-4 py-8 text-dh-900">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-dh-muted">مدیریت / دسته‌بندی‌ها</p>
                    <h1 class="text-3xl font-bold">دسته‌بندی جدید</h1>
                </div>
                <Link
                    href="/admin/categories"
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
                        placeholder="در صورت خالی بودن خودکار ساخته می‌شود"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium"
                        >دسته والد</label
                    ><select
                        v-model="form.parent_id"
                        class="w-full rounded-lg border px-3 py-2"
                    >
                        <option :value="null">دسته اصلی</option>
                        <option
                            v-for="parent in props.parents"
                            :key="parent.id"
                            :value="parent.id"
                        >
                            {{ parent.name }}
                        </option>
                    </select>
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
                    <label class="mb-1 block text-sm font-medium">تصویر</label
                    ><input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="w-full rounded-lg border px-3 py-2"
                        @change="handleImageChange"
                    />
                </div>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2"
                        ><input v-model="form.is_active" type="checkbox" />
                        فعال</label
                    >
                    <div>
                        <label class="ml-2 text-sm">ترتیب</label
                        ><input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="w-24 rounded-lg border px-3 py-2"
                        />
                    </div>
                </div>
                <button
                    :disabled="form.processing"
                    class="rounded-lg bg-dh-700 px-5 py-2.5 font-medium text-white disabled:opacity-50"
                >
                    ذخیره دسته‌بندی
                </button>
            </form>
        </div>
    </div>
</template>
