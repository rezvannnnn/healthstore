<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

type Option = { id: number; name: string };
type Product = {
    id: number;
    name: string;
    slug: string | null;
    sku: string | null;
    barcode: string | null;
    brand_id: number | null;
    category_id: number | null;
    product_type: string | null;
    unit: string | null;
    quantity_per_unit: number | null;
    short_description: string | null;
    description: string | null;
    expiry_date: string | null;
    main_image: string | null;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    price: number | null;
    compare_at_price: number | null;
};

type FormData = {
    name: string;
    slug: string;
    sku: string;
    barcode: string;
    brand_id: number | null;
    category_id: number | null;
    product_type: string;
    unit: string;
    quantity_per_unit: number | null;
    short_description: string;
    description: string;
    expiry_date: string;
    main_image: string;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    price: number | null;
    compare_at_price: number | null;
};

const props = defineProps<{
    product: Product | null;
    brands: Option[];
    categories: Option[];
    submitUrl: string;
    method: 'post' | 'put';
    title: string;
}>();

const form = useForm<FormData>({
    name: props.product?.name ?? '',
    slug: props.product?.slug ?? '',
    sku: props.product?.sku ?? '',
    barcode: props.product?.barcode ?? '',
    brand_id: props.product?.brand_id ?? null,
    category_id: props.product?.category_id ?? null,
    product_type: props.product?.product_type ?? '',
    unit: props.product?.unit ?? '',
    quantity_per_unit: props.product?.quantity_per_unit ?? null,
    short_description: props.product?.short_description ?? '',
    description: props.product?.description ?? '',
    expiry_date: props.product?.expiry_date ?? '',
    main_image: props.product?.main_image ?? '',
    is_active: props.product?.is_active ?? true,
    is_featured: props.product?.is_featured ?? false,
    sort_order: props.product?.sort_order ?? 0,
    price: props.product?.price ?? null,
    compare_at_price: props.product?.compare_at_price ?? null,
});

const submit = () => {
    form.submit(props.method, props.submitUrl);
};
</script>

<template>
    <div
        dir="rtl"
        class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-5xl">
            <div class="mb-6">
                <h1 class="text-2xl font-bold">{{ title }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    اطلاعات پایه و قیمت فروش محصول
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
                >
                    <h2 class="mb-5 text-lg font-semibold">اطلاعات اصلی</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block md:col-span-2">
                            <span class="text-sm font-medium">نام محصول *</span>
                            <input
                                v-model="form.name"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-sm text-red-600"
                                >{{ form.errors.name }}</span
                            >
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">Slug</span>
                            <input
                                v-model="form.slug"
                                dir="ltr"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">SKU</span>
                            <input
                                v-model="form.sku"
                                dir="ltr"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                            <span
                                v-if="form.errors.sku"
                                class="text-sm text-red-600"
                                >{{ form.errors.sku }}</span
                            >
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">بارکد</span>
                            <input
                                v-model="form.barcode"
                                dir="ltr"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">برند</span>
                            <select
                                v-model="form.brand_id"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            >
                                <option :value="null">بدون برند</option>
                                <option
                                    v-for="brand in brands"
                                    :key="brand.id"
                                    :value="brand.id"
                                >
                                    {{ brand.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">دسته‌بندی</span>
                            <select
                                v-model="form.category_id"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            >
                                <option :value="null">بدون دسته‌بندی</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">نوع محصول</span>
                            <input
                                v-model="form.product_type"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">واحد</span>
                            <input
                                v-model="form.unit"
                                class="mt-1 w-full rounded-lg border-gray-300"
                                placeholder="عدد، بسته، میلی‌لیتر و ..."
                            />
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium"
                                >تعداد در واحد</span
                            >
                            <input
                                v-model.number="form.quantity_per_unit"
                                type="number"
                                min="1"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>
                    </div>
                </section>

                <section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
                >
                    <h2 class="mb-5 text-lg font-semibold">قیمت</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium">قیمت فروش *</span>
                            <input
                                v-model.number="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                            <span
                                v-if="form.errors.price"
                                class="text-sm text-red-600"
                                >{{ form.errors.price }}</span
                            >
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium"
                                >قیمت قبل از تخفیف</span
                            >
                            <input
                                v-model.number="form.compare_at_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>
                    </div>
                </section>

                <section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
                >
                    <h2 class="mb-5 text-lg font-semibold">
                        توضیحات و اطلاعات تکمیلی
                    </h2>
                    <div class="space-y-4">
                        <label class="block">
                            <span class="text-sm font-medium">توضیح کوتاه</span>
                            <input
                                v-model="form.short_description"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium"
                                >توضیحات کامل</span
                            >
                            <textarea
                                v-model="form.description"
                                rows="6"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-medium"
                                    >تاریخ انقضا</span
                                >
                                <input
                                    v-model="form.expiry_date"
                                    type="date"
                                    class="mt-1 w-full rounded-lg border-gray-300"
                                />
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium"
                                    >آدرس تصویر اصلی</span
                                >
                                <input
                                    v-model="form.main_image"
                                    dir="ltr"
                                    class="mt-1 w-full rounded-lg border-gray-300"
                                />
                            </label>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
                >
                    <h2 class="mb-5 text-lg font-semibold">وضعیت</h2>
                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300"
                            />
                            <span>محصول فعال باشد</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="rounded border-gray-300"
                            />
                            <span>محصول ویژه باشد</span>
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium">ترتیب نمایش</span>
                            <input
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                class="mt-1 w-full rounded-lg border-gray-300"
                            />
                        </label>
                    </div>
                </section>

                <div class="flex items-center justify-between">
                    <a
                        href="/admin/products"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium hover:bg-gray-100"
                        >انصراف</a
                    >
                    <button
                        :disabled="form.processing"
                        type="submit"
                        class="rounded-lg bg-gray-900 px-6 py-2.5 text-sm font-medium text-white disabled:opacity-50"
                    >
                        {{
                            form.processing ? 'در حال ذخیره...' : 'ذخیره محصول'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
