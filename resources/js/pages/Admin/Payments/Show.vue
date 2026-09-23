<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Payment = {
    id: number;
    order_id: number;
    amount: string | number;
    gateway: string | null;
    status: string;
    authority: string | null;
    transaction_id: string | null;
    reference_number: string | null;
    card_last_four: string | null;
    paid_at: string | null;
    refunded_at: string | null;
    created_at: string | null;
    gateway_response: unknown;
    order?: {
        order_number: string;
        user?: { name: string | null; phone: string | null } | null;
    } | null;
};
const props = defineProps<{ payment: Payment }>();
const formatAmount = (amount: string | number) =>
    new Intl.NumberFormat('fa-IR').format(Number(amount));
const statusLabel = (value: string) =>
    ({
        pending: 'در انتظار',
        paid: 'موفق',
        failed: 'ناموفق',
        cancelled: 'لغو شده',
        refunded: 'برگشت خورده',
    })[value] || value;
const responseText = (value: unknown) =>
    typeof value === 'string'
        ? value
        : value
          ? JSON.stringify(value, null, 2)
          : '—';
</script>

<template>
    <Head title="جزئیات پرداخت" />
    <div
        dir="rtl"
        class="min-h-screen bg-dh-50 px-4 py-8 text-dh-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-4xl">
            <div class="mb-8 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-dh-muted">HealthStore / پرداخت</p>
                    <h1 class="text-3xl font-bold">
                        جزئیات پرداخت #{{ props.payment.id }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="`/admin/orders/${payment.order_id}`"
                        class="rounded-lg border border-dh-200 bg-white px-4 py-2 text-sm font-medium hover:bg-dh-50"
                        >سفارش</Link
                    ><Link
                        href="/admin/payments"
                        class="rounded-lg border border-dh-200 bg-white px-4 py-2 text-sm font-medium hover:bg-dh-50"
                        >پرداخت‌ها</Link
                    >
                </div>
            </div>
            <div class="grid gap-6 md:grid-cols-2">
                <section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
                >
                    <h2 class="mb-5 text-lg font-bold">اطلاعات پرداخت</h2>
                    <dl class="space-y-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">مبلغ</dt>
                            <dd class="font-semibold">
                                {{ formatAmount(payment.amount) }} ریال
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">وضعیت</dt>
                            <dd><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="{ 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200': payment.status === 'paid', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200': payment.status === 'pending', 'bg-red-50 text-red-700 ring-1 ring-red-200': payment.status === 'failed' || payment.status === 'cancelled', 'bg-violet-50 text-violet-700 ring-1 ring-violet-200': payment.status === 'refunded' }">{{ statusLabel(payment.status) }}</span></dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">درگاه</dt>
                            <dd>{{ payment.gateway || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">Authority</dt>
                            <dd class="max-w-[65%] text-left break-all">
                                {{ payment.authority || '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">شماره تراکنش</dt>
                            <dd>{{ payment.transaction_id || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">شماره مرجع</dt>
                            <dd>{{ payment.reference_number || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">۴ رقم کارت</dt>
                            <dd>{{ payment.card_last_four || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">زمان پرداخت</dt>
                            <dd>
                                {{
                                    payment.paid_at
                                        ? new Date(
                                              payment.paid_at,
                                          ).toLocaleString('fa-IR')
                                        : '—'
                                }}
                            </dd>
                        </div>
                    </dl>
                </section>
                <section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
                >
                    <h2 class="mb-5 text-lg font-bold">سفارش و مشتری</h2>
                    <dl class="space-y-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">شماره سفارش</dt>
                            <dd class="font-semibold">
                                {{
                                    payment.order?.order_number ||
                                    `#${payment.order_id}`
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">مشتری</dt>
                            <dd>{{ payment.order?.user?.name || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-dh-muted">موبایل</dt>
                            <dd>{{ payment.order?.user?.phone || '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
            <section
                class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-dh-100"
            >
                <h2 class="mb-4 text-lg font-bold">پاسخ درگاه</h2>
                <pre
                    class="max-h-96 overflow-auto rounded-lg bg-dh-50 p-4 text-left text-xs whitespace-pre-wrap"
                    >{{ responseText(payment.gateway_response) }}</pre>
            </section>
        </div>
    </div>
</template>
