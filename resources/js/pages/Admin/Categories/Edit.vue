<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

type Parent = { id: number; name: string };
type Category = {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    is_active: boolean;
    sort_order: number;
};
const props = defineProps<{ category: Category; parents: Parent[] }>();
const form = useForm({
    name: props.category.name,
    slug: props.category.slug,
    parent_id: props.category.parent_id,
    description: props.category.description || '',
    image: props.category.image || '',
    is_active: props.category.is_active,
    sort_order: props.category.sort_order,
});
const submit = () => form.put(`/admin/categories/${props.category.id}`);
</script>

<template>
    <Head title="ویرایش دسته‌بندی" />
    <div dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">مدیریت / دسته‌بندی‌ها</p>
                    <h1 class="text-3xl font-bold">ویرایش دسته‌بندی</h1>
                </div>
                <Link
                    href="/admin/categories"
                    class="rounded-lg border bg-white px-4 py-2 text-sm"
                    >بازگشت</Link
                >
            </div>
            <form
                @submit.prevent="submit"
                class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
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
                        v-model="form.image"
                        class="w-full rounded-lg border px-3 py-2"
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
                    class="rounded-lg bg-gray-900 px-5 py-2.5 font-medium text-white disabled:opacity-50"
                >
                    ذخیره تغییرات
                </button>
            </form>
        </div>
    </div>
</template>
