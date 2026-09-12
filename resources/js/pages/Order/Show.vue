<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

interface OrderItem {
    id: number
    product_id: number | null
    product_name: string
    product_sku: string | null
    quantity: number
    unit_price: number | string
    discount_amount: number | string
    total_amount: number | string
}

interface Payment {
    id: number
    amount: number | string
    gateway: string | null
    status: string
    authority: string | null
    transaction_id: string | null
    reference_number: string | null
    paid_at: string | null
}

interface Order {
    id: number
    order_number: string
    status: string
    payment_status: string
    subtotal: number | string
    discount_amount: number | string
    shipping_amount: number | string
    total_amount: number | string
    currency: string
    recipient_name: string | null
    recipient_phone: string | null
    province: string | null
    city: string | null
    shipping_address: string | null
    postal_code: string | null
    customer_note: string | null
    confirmed_at: string | null
    paid_at: string | null
    cancelled_at: string | null
    items: OrderItem[]
    payments: Payment[]
}

const props = defineProps<{
    order: Order
    success?: string
    error?: string
    info?: string
}>()

const formatter = new Intl.NumberFormat('fa-IR')

const isStartingPayment = ref(false)

function formatPrice(value: number | string): string {
    return `${formatter.format(Number(value))} تومان`
}

const orderStatusLabel = computed(() => {
    switch (props.order.status) {
        case 'pending':
            return 'در انتظار پرداخت'
        case 'paid':
            return 'پرداخت شده'
        case 'processing':
            return 'در حال پردازش'
        case 'shipped':
            return 'ارسال شده'
        case 'delivered':
            return 'تحویل شده'
        case 'cancelled':
            return 'لغو شده'
        case 'expired':
            return 'منقضی شده'
        default:
            return props.order.status
    }
})

const paymentStatusLabel = computed(() => {
    switch (props.order.payment_status) {
        case 'pending':
            return 'در انتظار پرداخت'
        case 'paid':
            return 'پرداخت موفق'
        case 'failed':
            return 'پرداخت ناموفق'
        case 'refunded':
            return 'مسترد شده'
        case 'cancelled':
            return 'لغو شده'
        default:
            return props.order.payment_status
    }
})

const hasPayableAmount = computed(() => {
    return Number(props.order.total_amount) > 0
})

const canPay = computed(() => {
    return (
        hasPayableAmount.value &&
        props.order.status === 'pending' &&
        props.order.payment_status === 'pending' &&
        !isStartingPayment.value
    )
})

function goBackToCheckout(): void {
    router.get('/checkout')
}

function startPayment(): void {
    if (!canPay.value) {
        return
    }

    isStartingPayment.value = true

    router.post(
        `/orders/${props.order.order_number}/payment`,
        {},
        {
            onFinish: () => {
                isStartingPayment.value = false
            },
        }
    )
}
</script>

<template>
    <div class="order-page" dir="rtl">
        <div class="order-container">
            <header class="order-header">
                <div>
                    <h1>جزئیات سفارش</h1>
                    <p>
                        شماره سفارش:
                        <strong>{{ order.order_number }}</strong>
                    </p>
                </div>

                <div class="header-status">
                    {{ orderStatusLabel }}
                </div>
            </header>

            <div v-if="success" class="message success-message">
                {{ success }}
            </div>

            <div v-if="error" class="message error-message">
                {{ error }}
            </div>

            <div v-if="info" class="message info-message">
                {{ info }}
            </div>

            <section class="card">
                <h2>اطلاعات سفارش</h2>

                <div class="info-grid">
                    <div>
                        <span class="label">شماره سفارش</span>
                        <strong>{{ order.order_number }}</strong>
                    </div>

                    <div>
                        <span class="label">وضعیت سفارش</span>
                        <strong>{{ orderStatusLabel }}</strong>
                    </div>

                    <div>
                        <span class="label">وضعیت پرداخت</span>
                        <strong>{{ paymentStatusLabel }}</strong>
                    </div>

                    <div>
                        <span class="label">واحد پول</span>
                        <strong>{{ order.currency }}</strong>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>اقلام سفارش</h2>

                <div v-if="order.items.length === 0" class="empty-state">
                    هیچ قلمی برای این سفارش ثبت نشده است.
                </div>

                <div
                    v-for="item in order.items"
                    :key="item.id"
                    class="order-item"
                >
                    <div class="item-info">
                        <h3>{{ item.product_name }}</h3>

                        <span v-if="item.product_sku">
                            کد کالا: {{ item.product_sku }}
                        </span>

                        <span>
                            تعداد: {{ item.quantity }}
                        </span>
                    </div>

                    <div class="item-prices">
                        <span>
                            {{ formatPrice(item.unit_price) }}
                        </span>

                        <strong>
                            {{ formatPrice(item.total_amount) }}
                        </strong>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>خلاصه مبلغ</h2>

                <div class="summary">
                    <div class="summary-row">
                        <span>جمع کالاها</span>
                        <strong>
                            {{ formatPrice(order.subtotal) }}
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>تخفیف</span>
                        <strong>
                            {{ formatPrice(order.discount_amount) }}
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>هزینه ارسال</span>
                        <strong>
                            {{ formatPrice(order.shipping_amount) }}
                        </strong>
                    </div>

                    <div class="summary-row total-row">
                        <span>مبلغ قابل پرداخت</span>
                        <strong>
                            {{ formatPrice(order.total_amount) }}
                        </strong>
                    </div>
                </div>
            </section>

            <section
                v-if="
                    order.recipient_name ||
                    order.recipient_phone ||
                    order.shipping_address
                "
                class="card"
            >
                <h2>اطلاعات گیرنده</h2>

                <div class="recipient-info">
                    <div v-if="order.recipient_name">
                        <span class="label">نام گیرنده</span>
                        <strong>{{ order.recipient_name }}</strong>
                    </div>

                    <div v-if="order.recipient_phone">
                        <span class="label">شماره تماس</span>
                        <strong>{{ order.recipient_phone }}</strong>
                    </div>

                    <div v-if="order.province || order.city">
                        <span class="label">موقعیت</span>
                        <strong>
                            {{ order.province }}

                            <span
                                v-if="
                                    order.province &&
                                    order.city
                                "
                            >
                                -
                            </span>

                            {{ order.city }}
                        </strong>
                    </div>

                    <div v-if="order.shipping_address">
                        <span class="label">آدرس</span>
                        <strong>{{ order.shipping_address }}</strong>
                    </div>

                    <div v-if="order.postal_code">
                        <span class="label">کد پستی</span>
                        <strong>{{ order.postal_code }}</strong>
                    </div>
                </div>
            </section>

            <section class="card payment-card">
                <h2>پرداخت</h2>

                <div class="payment-status-box">
                    <span>وضعیت پرداخت</span>
                    <strong>{{ paymentStatusLabel }}</strong>
                </div>

                <div v-if="canPay" class="actions">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="isStartingPayment"
                        @click="startPayment"
                    >
                        {{
                            isStartingPayment
                                ? 'در حال آماده‌سازی پرداخت...'
                                : `پرداخت ${formatPrice(order.total_amount)}`
                        }}
                    </button>
                </div>

                <div
                    v-else-if="order.payment_status === 'paid'"
                    class="paid-message"
                >
                    این سفارش با موفقیت پرداخت شده است.
                </div>

                <div
                    v-else-if="!hasPayableAmount"
                    class="info-message-box"
                >
                    مبلغ این سفارش قابل پرداخت نیست.
                </div>

                <div
                    v-else-if="order.status === 'cancelled'"
                    class="error-message-box"
                >
                    این سفارش لغو شده است.
                </div>
            </section>

            <section class="actions-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="goBackToCheckout"
                >
                    بازگشت به Checkout
                </button>
            </section>
        </div>
    </div>
</template>

<style scoped>
.order-page {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 40px 20px;
}

.order-container {
    width: min(100%, 1000px);
    margin: 0 auto;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.order-header h1 {
    margin: 0 0 8px;
    font-size: 30px;
}

.order-header p {
    margin: 0;
    color: #666;
}

.header-status {
    padding: 10px 16px;
    border-radius: 999px;
    background: #eee;
    font-weight: 700;
    white-space: nowrap;
}

.card {
    margin-bottom: 20px;
    padding: 24px;
    border-radius: 16px;
    background: #fff;
}

.card h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.info-grid > div,
.recipient-info > div {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.label {
    color: #777;
    font-size: 14px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 18px 0;
    border-bottom: 1px solid #eee;
}

.order-item:last-child {
    border-bottom: 0;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.item-info h3 {
    margin: 0;
}

.item-info span {
    color: #777;
    font-size: 14px;
}

.item-prices {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    white-space: nowrap;
}

.item-prices span {
    color: #777;
}

.summary {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
}

.total-row {
    padding-top: 16px;
    border-top: 1px solid #eee;
    font-size: 20px;
}

.recipient-info {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.payment-card {
    border: 1px solid #eee;
}

.payment-status-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 16px;
    border-radius: 12px;
    background: #f7f7f7;
}

.actions {
    margin-top: 20px;
}

.actions-footer {
    display: flex;
    justify-content: flex-start;
    margin-top: 10px;
}

.btn {
    border: 0;
    border-radius: 10px;
    padding: 13px 22px;
    font-size: 15px;
    cursor: pointer;
}

.btn:disabled {
    cursor: wait;
    opacity: 0.7;
}

.btn-primary {
    background: #111;
    color: #fff;
}

.btn-secondary {
    background: #e9ecef;
    color: #222;
}

.message {
    margin-bottom: 20px;
    padding: 14px 18px;
    border-radius: 12px;
}

.success-message {
    background: #edf7ed;
    border: 1px solid #c9e6c9;
}

.error-message {
    background: #fdeeee;
    border: 1px solid #efcaca;
}

.info-message {
    background: #eef5fd;
    border: 1px solid #cbdcf2;
}

.paid-message,
.info-message-box,
.error-message-box {
    margin-top: 20px;
    padding: 14px 16px;
    border-radius: 12px;
}

.paid-message {
    background: #edf7ed;
}

.info-message-box {
    background: #f2f2f2;
}

.error-message-box {
    background: #fdeeee;
}

.empty-state {
    padding: 25px 0;
    text-align: center;
    color: #777;
}

@media (max-width: 700px) {
    .order-header,
    .order-item,
    .summary-row,
    .payment-status-box {
        align-items: flex-start;
        flex-direction: column;
    }

    .info-grid,
    .recipient-info {
        grid-template-columns: 1fr;
    }

    .item-prices {
        align-items: flex-start;
    }

    .actions-footer,
    .actions {
        width: 100%;
    }

    .btn {
        width: 100%;
    }
}
</style>