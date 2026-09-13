<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

type Item = { id: number; product_name: string; product_sku: string | null; quantity: number; unit_price: number; total_amount: number };
type Payment = { id: number; amount: number; gateway: string | null; status: string; authority: string | null; transaction_id: string | null; reference_number: string | null; paid_at: string | null };
type Order = { id: number; order_number: string; status: string; payment_status: string; subtotal: number; discount_amount: number; shipping_amount: number; total_amount: number; currency: string; recipient_name: string | null; recipient_phone: string | null; province: string | null; city: string | null; shipping_address: string | null; postal_code: string | null; customer_note: string | null; admin_note: string | null; created_at: string; user: { name: string | null; phone: string | null } | null; items: Item[]; payments: Payment[] };

const props = defineProps<{ order: Order; success?: string; error?: string }>();
const formatAmount = (amount: number) => new Intl.NumberFormat('fa-IR').format(amount);
const statusLabel = (value: string) => ({ pending: 'در انتظار', paid: 'پرداخت شده', processing: 'در حال پردازش', shipped: 'ارسال شده', delivered: 'تحویل شده', cancelled: 'لغو شده' }[value] || value);
const paymentLabel = (value: string) => ({ pending: 'در انتظار', paid: 'موفق', failed: 'ناموفق', refunded: 'برگشت خورده' }[value] || value);
const nextStatus = (value: string) => ({ pending: 'paid', paid: 'processing', processing: 'shipped', shipped: 'delivered' }[value] || null);
const nextStatusLabel = (value: string) => ({ paid: 'تأیید پرداخت', processing: 'شروع پردازش', shipped: 'ثبت ارسال', delivered: 'ثبت تحویل' }[value] || value);
const changeStatus = (status: string) => router.post(`/admin/orders/${props.order.id}/status`, { status });
</script>

<template>
    <Head :title="`سفارش ${order.order_number}`" />
    <div dir="rtl" class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="mb-8 flex items-center justify-between gap-4">
                <div><p class="text-sm text-gray-500">HealthStore / سفارش‌ها</p><h1 class="text-2xl font-bold">{{ order.order_number }}</h1></div>
                <Link href="/admin/orders" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-100">بازگشت</Link>
            </div>
            <div v-if="success" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ success }}</div>
            <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="mb-5 flex items-center justify-between"><h2 class="font-bold">وضعیت سفارش</h2><span class="rounded-full bg-gray-100 px-3 py-1 text-sm">{{ statusLabel(order.status) }}</span></div>
                    <div v-if="nextStatus(order.status)" class="flex flex-wrap gap-2">
                        <button type="button" class="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-800" @click="changeStatus(nextStatus(order.status)!)">{{ nextStatusLabel(nextStatus(order.status)!) }}</button>
                    </div>
                    <p v-else class="text-sm text-gray-500">برای این وضعیت، عملیات بعدی تعریف نشده است.</p>
                </section>
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"><h2 class="mb-4 font-bold">مشتری</h2><p>{{ order.user?.name || order.recipient_name || '—' }}</p><p class="mt-1 text-sm text-gray-500">{{ order.user?.phone || order.recipient_phone || '—' }}</p></section>
            </div>

            <section class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-4 font-bold">اقلام سفارش</h2>
                <div class="overflow-x-auto"><table class="min-w-full text-right text-sm"><thead class="border-b text-gray-500"><tr><th class="px-2 py-3">محصول</th><th class="px-2 py-3">تعداد</th><th class="px-2 py-3">قیمت واحد</th><th class="px-2 py-3">جمع</th></tr></thead><tbody class="divide-y"><tr v-for="item in order.items" :key="item.id"><td class="px-2 py-3"><div class="font-medium">{{ item.product_name }}</div><div class="text-xs text-gray-500">{{ item.product_sku || '—' }}</div></td><td class="px-2 py-3">{{ item.quantity }}</td><td class="px-2 py-3">{{ formatAmount(Number(item.unit_price)) }}</td><td class="px-2 py-3 font-medium">{{ formatAmount(Number(item.total_amount)) }}</td></tr></tbody></table></div>
                <div class="mt-5 border-t pt-4 text-sm"><div class="flex justify-between"><span>جمع کالاها</span><span>{{ formatAmount(Number(order.subtotal)) }}</span></div><div class="mt-2 flex justify-between"><span>تخفیف</span><span>{{ formatAmount(Number(order.discount_amount)) }}</span></div><div class="mt-2 flex justify-between"><span>ارسال</span><span>{{ formatAmount(Number(order.shipping_amount)) }}</span></div><div class="mt-3 flex justify-between text-base font-bold"><span>مبلغ نهایی</span><span>{{ formatAmount(Number(order.total_amount)) }} ریال</span></div></div>
            </section>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"><h2 class="mb-4 font-bold">آدرس ارسال</h2><p>{{ order.recipient_name || '—' }}</p><p class="mt-1 text-sm">{{ order.recipient_phone || '—' }}</p><p class="mt-3 text-sm text-gray-600">{{ order.province || '' }} {{ order.city || '' }}</p><p class="mt-1 text-sm text-gray-600">{{ order.shipping_address || '—' }}</p><p class="mt-1 text-sm text-gray-500">کد پستی: {{ order.postal_code || '—' }}</p></section>
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"><h2 class="mb-4 font-bold">پرداخت‌ها</h2><div v-for="payment in order.payments" :key="payment.id" class="border-b py-3 last:border-0"><div class="flex justify-between"><span>{{ payment.gateway || 'درگاه' }}</span><span>{{ paymentLabel(payment.status) }}</span></div><div class="mt-1 text-sm text-gray-500">{{ formatAmount(Number(payment.amount)) }} ریال</div><div class="mt-1 text-xs text-gray-500">تراکنش: {{ payment.transaction_id || '—' }}</div></div><p v-if="order.payments.length === 0" class="text-sm text-gray-500">پرداختی ثبت نشده است.</p></section>
            </div>
        </div>
    </div>
</template>
