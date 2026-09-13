<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

interface Coupon {
    id: number;
    code: string;
    type: 'percent' | 'fixed';
    value: number;
    min_order_amount: number;
    max_discount_amount: number | null;
    usage_limit: number | null;
    usage_limit_per_user: number | null;
    used_count: number;
    starts_at: string | null;
    expires_at: string | null;
    is_active: boolean;
}

interface Props {
    coupons: {
        data: Coupon[];
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
}

const props = defineProps<Props>();
const editingId = ref<number | null>(null);

const form = useForm({
    code: '',
    type: 'percent' as 'percent' | 'fixed',
    value: 10,
    min_order_amount: 0,
    max_discount_amount: null as number | null,
    usage_limit: null as number | null,
    usage_limit_per_user: null as number | null,
    starts_at: '',
    expires_at: '',
    is_active: true,
});

const title = computed(() =>
    editingId.value ? 'ویرایش کد تخفیف' : 'ایجاد کد تخفیف',
);

function resetForm() {
    editingId.value = null;
    form.reset();
    form.type = 'percent';
    form.value = 10;
    form.min_order_amount = 0;
    form.max_discount_amount = null;
    form.usage_limit = null;
    form.usage_limit_per_user = null;
    form.starts_at = '';
    form.expires_at = '';
    form.is_active = true;
    form.clearErrors();
}

function edit(coupon: Coupon) {
    editingId.value = coupon.id;
    form.code = coupon.code;
    form.type = coupon.type;
    form.value = coupon.value;
    form.min_order_amount = coupon.min_order_amount;
    form.max_discount_amount = coupon.max_discount_amount;
    form.usage_limit = coupon.usage_limit;
    form.usage_limit_per_user = coupon.usage_limit_per_user;
    form.starts_at = coupon.starts_at ? coupon.starts_at.slice(0, 16) : '';
    form.expires_at = coupon.expires_at ? coupon.expires_at.slice(0, 16) : '';
    form.is_active = coupon.is_active;
    form.clearErrors();
}

function submit() {
    const options = {
        onSuccess: resetForm,
    };

    if (editingId.value) {
        form.put(`/admin/coupons/${editingId.value}`, options);
    } else {
        form.post('/admin/coupons', options);
    }
}

function remove(coupon: Coupon) {
    if (!window.confirm(`کد «${coupon.code}» حذف شود؟`)) {
        return;
    }

    router.delete(`/admin/coupons/${coupon.id}`);
}

function goToPage(page: number) {
    if (
        page < 1 ||
        page > props.coupons.last_page ||
        page === props.coupons.current_page
    ) {
        return;
    }

    router.get(
        `/admin/coupons?page=${page}`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function formatNumber(value: number) {
    return new Intl.NumberFormat('fa-IR').format(value);
}
</script>

<template>
    <Head title="کدهای تخفیف" />

    <div class="mx-auto max-w-7xl space-y-6 p-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">کدهای تخفیف</h1>
                <p class="mt-1 text-sm text-gray-500">
                    ساخت و مدیریت تخفیف‌های فروشگاه
                </p>
            </div>
            <button
                v-if="editingId"
                class="rounded-lg border px-4 py-2 text-sm"
                @click="resetForm"
            >
                انصراف از ویرایش
            </button>
        </div>

        <form
            class="grid gap-4 rounded-xl border bg-white p-5 shadow-sm md:grid-cols-4"
            @submit.prevent="submit"
        >
            <h2 class="text-lg font-semibold md:col-span-4">{{ title }}</h2>
            <label class="space-y-1"
                ><span>کد</span
                ><input
                    v-model="form.code"
                    class="w-full rounded-lg border p-2"
                    placeholder="WELCOME10"
            /></label>
            <label class="space-y-1"
                ><span>نوع</span
                ><select
                    v-model="form.type"
                    class="w-full rounded-lg border p-2"
                >
                    <option value="percent">درصدی</option>
                    <option value="fixed">مبلغ ثابت</option>
                </select></label
            >
            <label class="space-y-1"
                ><span>مقدار</span
                ><input
                    v-model.number="form.value"
                    type="number"
                    min="0.01"
                    step="0.01"
                    class="w-full rounded-lg border p-2"
            /></label>
            <label class="flex items-center gap-2 pt-7"
                ><input v-model="form.is_active" type="checkbox" /> فعال</label
            >
            <label class="space-y-1"
                ><span>حداقل مبلغ سفارش</span
                ><input
                    v-model.number="form.min_order_amount"
                    type="number"
                    min="0"
                    class="w-full rounded-lg border p-2"
            /></label>
            <label class="space-y-1"
                ><span>سقف مبلغ تخفیف</span
                ><input
                    v-model.number="form.max_discount_amount"
                    type="number"
                    min="0"
                    class="w-full rounded-lg border p-2"
            /></label>
            <label class="space-y-1"
                ><span>سقف استفاده کل</span
                ><input
                    v-model.number="form.usage_limit"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border p-2"
            /></label>
            <label class="space-y-1"
                ><span>سقف استفاده هر کاربر</span
                ><input
                    v-model.number="form.usage_limit_per_user"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border p-2"
            /></label>
            <label class="space-y-1"
                ><span>شروع</span
                ><input
                    v-model="form.starts_at"
                    type="datetime-local"
                    class="w-full rounded-lg border p-2"
            /></label>
            <label class="space-y-1"
                ><span>پایان</span
                ><input
                    v-model="form.expires_at"
                    type="datetime-local"
                    class="w-full rounded-lg border p-2"
            /></label>
            <div class="md:col-span-4">
                <button
                    :disabled="form.processing"
                    class="rounded-lg bg-black px-5 py-2 text-white disabled:opacity-50"
                >
                    {{ editingId ? 'ذخیره تغییرات' : 'ایجاد کد تخفیف' }}
                </button>
            </div>
            <div
                v-if="form.errors.code"
                class="text-sm text-red-600 md:col-span-4"
            >
                {{ form.errors.code }}
            </div>
            <div
                v-if="form.errors.value"
                class="text-sm text-red-600 md:col-span-4"
            >
                {{ form.errors.value }}
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <table class="w-full text-right text-sm">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="p-3">کد</th>
                        <th class="p-3">تخفیف</th>
                        <th class="p-3">استفاده</th>
                        <th class="p-3">اعتبار</th>
                        <th class="p-3">وضعیت</th>
                        <th class="p-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="coupon in props.coupons.data"
                        :key="coupon.id"
                        class="border-b last:border-0"
                    >
                        <td class="p-3 font-semibold">{{ coupon.code }}</td>
                        <td class="p-3">
                            {{
                                coupon.type === 'percent'
                                    ? `${formatNumber(coupon.value)}٪`
                                    : formatNumber(coupon.value)
                            }}
                        </td>
                        <td class="p-3">
                            {{ formatNumber(coupon.used_count) }} /
                            {{
                                coupon.usage_limit
                                    ? formatNumber(coupon.usage_limit)
                                    : '∞'
                            }}
                        </td>
                        <td class="p-3">
                            {{
                                coupon.expires_at
                                    ? new Date(
                                          coupon.expires_at,
                                      ).toLocaleDateString('fa-IR')
                                    : 'بدون پایان'
                            }}
                        </td>
                        <td class="p-3">
                            {{ coupon.is_active ? 'فعال' : 'غیرفعال' }}
                        </td>
                        <td class="flex gap-2 p-3">
                            <button
                                class="rounded border px-3 py-1"
                                @click="edit(coupon)"
                            >
                                ویرایش</button
                            ><button
                                class="rounded border border-red-300 px-3 py-1 text-red-600"
                                @click="remove(coupon)"
                            >
                                حذف
                            </button>
                        </td>
                    </tr>
                    <tr v-if="props.coupons.data.length === 0">
                        <td colspan="6" class="p-8 text-center text-gray-500">
                            هنوز کد تخفیفی ثبت نشده است.
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                v-if="props.coupons.last_page > 1"
                class="flex items-center justify-between gap-4 border-t p-4 text-sm"
            >
                <span class="text-gray-500">
                    نمایش {{ formatNumber(props.coupons.from ?? 0) }} تا
                    {{ formatNumber(props.coupons.to ?? 0) }} از
                    {{ formatNumber(props.coupons.total) }} کد
                </span>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="props.coupons.current_page === 1"
                        class="rounded border px-3 py-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="goToPage(props.coupons.current_page - 1)"
                    >
                        قبلی
                    </button>
                    <span class="min-w-24 text-center">
                        صفحه {{ formatNumber(props.coupons.current_page) }} از
                        {{ formatNumber(props.coupons.last_page) }}
                    </span>
                    <button
                        :disabled="
                            props.coupons.current_page ===
                            props.coupons.last_page
                        "
                        class="rounded border px-3 py-1 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="goToPage(props.coupons.current_page + 1)"
                    >
                        بعدی
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
