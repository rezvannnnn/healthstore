<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type AdminUser = {
    id: number;
    name: string;
    username: string | null;
    title: string | null;
    role: 'admin' | 'staff';
    active: boolean;
    permission_count: number;
    permissions: string[];
    created_at: string | null;
};

type PermissionGroup = {
    label: string;
    permissions: string[];
};

const props = defineProps<{
    users: AdminUser[];
    permissionLabels: Record<string, string>;
    permissionGroups: PermissionGroup[];
}>();

const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    username: '',
    password: '',
    title: '',
    is_admin: false,
    is_active: true,
    permissions: [] as string[],
});

const isEditing = computed(() => editingId.value !== null);

const permissionLabel = (key: string) => props.permissionLabels[key] || key;

const setPreset = (preset: 'accounting' | 'warehouse') => {
    form.permissions =
        preset === 'accounting'
            ? ['orders.view', 'payments.view', 'reports.view']
            : [
                  'products.view',
                  'products.manage',
                  'inventory.view',
                  'inventory.manage',
              ];
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.is_active = true;
    form.is_admin = false;
    form.permissions = [];
};

const editUser = (user: AdminUser) => {
    editingId.value = user.id;
    form.name = user.name;
    form.username = user.username || '';
    form.password = '';
    form.title = user.title || '';
    form.is_admin = user.role === 'admin';
    form.is_active = user.active;
    form.permissions = [...user.permissions];
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submit = () => {
    if (isEditing.value) {
        form.put('/admin/users/' + editingId.value, {
            preserveScroll: true,
            onSuccess: resetForm,
        });
        return;
    }

    form.post('/admin/users', {
        preserveScroll: true,
        onSuccess: resetForm,
    });
};
</script>

<template>
    <Head title="مدیریت کاربران پنل" />

    <div
        dir="rtl"
        class="min-h-screen bg-dh-50 px-4 py-8 text-dh-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-dh-muted">داروخونه | مدیریت</p>
                    <h1 class="text-3xl font-bold">مدیریت کاربران پنل</h1>
                    <p class="mt-2 text-sm text-dh-muted">
                        ساخت کاربر مدیریتی و تعیین دقیق دسترسی‌های مورد نیاز هر
                        واحد.
                    </p>
                </div>
                <div class="flex gap-2">
                    <a
                        href="/admin"
                        class="rounded-lg border border-dh-200 bg-white px-4 py-2 text-sm font-medium hover:bg-dh-50"
                    >
                        پنل
                    </a>
                    <button
                        type="button"
                        class="rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                        @click="router.post('/admin/logout')"
                    >
                        خروج
                    </button>
                </div>
            </div>

            <section
                class="mb-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h2 class="text-xl font-bold">
                            {{ isEditing ? 'ویرایش کاربر' : 'ساخت کاربر جدید' }}
                        </h2>
                        <p class="mt-1 text-sm text-dh-muted">
                            برای کارکنان واحدهای مختلف، فقط دسترسی‌های لازم را
                            فعال کنید.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-dh-200 px-3 py-2 text-xs font-semibold hover:bg-dh-50"
                            @click="setPreset('accounting')"
                        >
                            الگوی حسابداری
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-dh-200 px-3 py-2 text-xs font-semibold hover:bg-dh-50"
                            @click="setPreset('warehouse')"
                        >
                            الگوی انبار
                        </button>
                    </div>
                </div>

                <form class="mt-6 space-y-6" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm font-medium">
                            نام کاربر
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-2 w-full rounded-xl border border-dh-200 px-4 py-3 outline-none focus:border-dh-500"
                                required
                            />
                        </label>
                        <label class="text-sm font-medium">
                            سمت / عنوان
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="مثلاً حسابدار"
                                class="mt-2 w-full rounded-xl border border-dh-200 px-4 py-3 outline-none focus:border-dh-500"
                            />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm font-medium">
                            نام کاربری
                            <input
                                v-model="form.username"
                                type="text"
                                autocomplete="username"
                                placeholder="accountant"
                                class="mt-2 w-full rounded-xl border border-dh-200 px-4 py-3 outline-none focus:border-dh-500"
                                required
                            />
                            <span class="mt-1 block text-xs text-dh-muted">
                                فقط حروف انگلیسی، عدد، نقطه، خط فاصله و زیرخط.
                            </span>
                        </label>
                        <label class="text-sm font-medium">
                            {{
                                isEditing
                                    ? 'رمز عبور جدید (اختیاری)'
                                    : 'رمز عبور'
                            }}
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                class="mt-2 w-full rounded-xl border border-dh-200 px-4 py-3 outline-none focus:border-dh-500"
                                :required="!isEditing"
                            />
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-5">
                        <label
                            class="inline-flex items-center gap-2 text-sm font-semibold"
                        >
                            <input v-model="form.is_admin" type="checkbox" />
                            مدیر با دسترسی کامل
                        </label>
                        <label
                            class="inline-flex items-center gap-2 text-sm font-semibold"
                        >
                            <input v-model="form.is_active" type="checkbox" />
                            حساب فعال باشد
                        </label>
                    </div>

                    <div
                        v-if="!form.is_admin"
                        class="rounded-2xl border border-dh-100 p-4"
                    >
                        <div class="mb-4">
                            <h3 class="font-bold">سطح دسترسی</h3>
                            <p class="mt-1 text-xs text-dh-muted">
                                هر مجوز فقط همان بخش مربوط را باز می‌کند.
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div
                                v-for="group in permissionGroups"
                                :key="group.label"
                                class="rounded-xl bg-dh-50 p-4"
                            >
                                <h4 class="font-semibold">{{ group.label }}</h4>
                                <div class="mt-3 space-y-3">
                                    <label
                                        v-for="permission in group.permissions"
                                        :key="permission"
                                        class="flex items-start gap-2 text-sm"
                                    >
                                        <input
                                            v-model="form.permissions"
                                            :value="permission"
                                            type="checkbox"
                                            class="mt-0.5"
                                        />
                                        <span>{{
                                            permissionLabel(permission)
                                        }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-dh-700 px-5 py-3 text-sm font-semibold text-white hover:bg-dh-800 disabled:opacity-60"
                        >
                            {{ isEditing ? 'ذخیره تغییرات' : 'ساخت کاربر' }}
                        </button>
                        <button
                            v-if="isEditing"
                            type="button"
                            class="rounded-xl border border-dh-200 bg-white px-5 py-3 text-sm font-semibold"
                            @click="resetForm"
                        >
                            انصراف
                        </button>
                    </div>

                    <div
                        v-if="Object.keys(form.errors).length"
                        class="space-y-1 text-sm text-red-600"
                    >
                        <div v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </div>
                    </div>
                </form>
            </section>

            <section
                class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-dh-100"
            >
                <div class="border-b border-dh-100 px-6 py-5">
                    <h2 class="text-lg font-bold">کاربران مدیریتی</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-dh-50 text-dh-700">
                            <tr>
                                <th class="px-4 py-3">کاربر</th>
                                <th class="px-4 py-3">نام کاربری</th>
                                <th class="px-4 py-3">نوع</th>
                                <th class="px-4 py-3">دسترسی</th>
                                <th class="px-4 py-3">وضعیت</th>
                                <th class="px-4 py-3">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dh-100">
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-4 py-4">
                                    <div class="font-semibold">
                                        {{ user.name }}
                                    </div>
                                    <div class="mt-1 text-xs text-dh-muted">
                                        {{ user.title || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-mono">
                                    {{ user.username }}
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        user.role === 'admin'
                                            ? 'مدیر کامل'
                                            : 'کاربر مدیریتی'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        user.role === 'admin'
                                            ? 'همه دسترسی‌ها'
                                            : user.permission_count + ' مجوز'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="
                                            user.active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-600'
                                        "
                                    >
                                        {{ user.active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        class="font-semibold text-dh-700 hover:underline"
                                        @click="editUser(user)"
                                    >
                                        ویرایش
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-12 text-center text-dh-muted"
                                >
                                    هنوز کاربر مدیریتی ایجاد نشده است.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>
