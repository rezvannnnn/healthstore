import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

type OrderItem = {
    id: number;
    product_id: number | null;
    product_name: string;
    product_sku: string | null;
    quantity: number;
    unit_price: string;
    discount_amount: string;
    total_amount: string;
};

type Payment = {
    id: number;
    amount: string;
    gateway: string | null;
    status: string;
    authority: string | null;
    transaction_id: string | null;
    reference_number: string | null;
    paid_at: string | null;
};

type Order = {
    id: number;
    order_number: string;
    user_id: number | null;
    customer_type: string;
    status: string;
    payment_status: string;
    subtotal: string;
    discount_amount: string;
    shipping_amount: string;
    total_amount: string;
    currency: string;
    recipient_name: string | null;
    recipient_phone: string | null;
    province: string | null;
    city: string | null;
    shipping_address: string | null;
    postal_code: string | null;
    customer_note: string | null;
    admin_note: string | null;
    confirmed_at: string | null;
    paid_at: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
    cancelled_at: string | null;
    created_at: string;
    updated_at: string;
    items: OrderItem[];
    payments: Payment[];
};

type Props = {
    order: Order;
    success?: string | null;
    error?: string | null;
    info?: string | null;
};

function orderStatusLabel(status: string): string {
    switch (status) {
        case 'pending':
            return 'در انتظار پرداخت';

        case 'paid':
            return 'پرداخت شده';

        case 'processing':
            return 'در حال پردازش';

        case 'shipped':
            return 'ارسال شده';

        case 'delivered':
            return 'تحویل شده';

        case 'cancelled':
            return 'لغو شده';

        default:
            return status;
    }
}

function paymentStatusLabel(status: string): string {
    switch (status) {
        case 'pending':
            return 'در انتظار پرداخت';

        case 'paid':
            return 'پرداخت موفق';

        case 'failed':
            return 'پرداخت ناموفق';

        case 'cancelled':
            return 'لغو شده';

        default:
            return status;
    }
}

function formatAmount(amount: string, currency: string): string {
    const numericAmount = Number(amount);

    if (Number.isNaN(numericAmount)) {
        return `${amount} ${currency}`;
    }

    return `${numericAmount.toLocaleString('fa-IR')} ${currency}`;
}

function formatDate(date: string | null): string {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleString('fa-IR');
}

export default function Show({ order, success, error, info }: Props) {
    const [isCancelling, setIsCancelling] = useState(false);

    const canCancel =
        order.status === 'pending' && order.payment_status !== 'paid';

    function cancelOrder() {
        if (!canCancel || isCancelling) {
            return;
        }

        const confirmed = window.confirm('آیا از لغو این سفارش مطمئن هستید؟');

        if (!confirmed) {
            return;
        }

        setIsCancelling(true);

        router.post(
            `/orders/${order.order_number}/cancel`,
            {},
            {
                onFinish: () => {
                    setIsCancelling(false);
                },
            },
        );
    }

    return (
        <>
            <Head title={`سفارش ${order.order_number}`} />

            <div>
                <div>
                    <Link href="/account/orders">بازگشت به سفارش‌های من</Link>
                </div>

                <h1>جزئیات سفارش</h1>

                {success && <div>{success}</div>}

                {error && <div>{error}</div>}

                {info && <div>{info}</div>}

                <section>
                    <h2>اطلاعات سفارش</h2>

                    <p>شماره سفارش: {order.order_number}</p>

                    <p>تاریخ ثبت: {formatDate(order.created_at)}</p>

                    <p>وضعیت سفارش: {orderStatusLabel(order.status)}</p>

                    <p>
                        وضعیت پرداخت: {paymentStatusLabel(order.payment_status)}
                    </p>

                    {order.confirmed_at && (
                        <p>تاریخ تأیید: {formatDate(order.confirmed_at)}</p>
                    )}

                    {order.paid_at && (
                        <p>تاریخ پرداخت: {formatDate(order.paid_at)}</p>
                    )}

                    {order.shipped_at && (
                        <p>تاریخ ارسال: {formatDate(order.shipped_at)}</p>
                    )}

                    {order.delivered_at && (
                        <p>تاریخ تحویل: {formatDate(order.delivered_at)}</p>
                    )}

                    {order.cancelled_at && (
                        <p>تاریخ لغو: {formatDate(order.cancelled_at)}</p>
                    )}
                </section>

                <section>
                    <h2>اقلام سفارش</h2>

                    {order.items.length === 0 ? (
                        <p>آیتمی برای این سفارش ثبت نشده است.</p>
                    ) : (
                        <div>
                            {order.items.map((item) => (
                                <div key={item.id}>
                                    <h3>{item.product_name}</h3>

                                    {item.product_sku && (
                                        <p>کد کالا: {item.product_sku}</p>
                                    )}

                                    <p>تعداد: {item.quantity}</p>

                                    <p>
                                        قیمت واحد:{' '}
                                        {formatAmount(
                                            item.unit_price,
                                            order.currency,
                                        )}
                                    </p>

                                    <p>
                                        مبلغ کل:{' '}
                                        {formatAmount(
                                            item.total_amount,
                                            order.currency,
                                        )}
                                    </p>

                                    {Number(item.discount_amount) > 0 && (
                                        <p>
                                            تخفیف:{' '}
                                            {formatAmount(
                                                item.discount_amount,
                                                order.currency,
                                            )}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section>
                    <h2>اطلاعات گیرنده و ارسال</h2>

                    <p>نام گیرنده: {order.recipient_name || '—'}</p>

                    <p>شماره تماس: {order.recipient_phone || '—'}</p>

                    <p>استان: {order.province || '—'}</p>

                    <p>شهر: {order.city || '—'}</p>

                    <p>آدرس: {order.shipping_address || '—'}</p>

                    <p>کد پستی: {order.postal_code || '—'}</p>

                    {order.customer_note && (
                        <p>توضیحات مشتری: {order.customer_note}</p>
                    )}
                </section>

                <section>
                    <h2>خلاصه مبلغ</h2>

                    <p>
                        مبلغ کالاها:{' '}
                        {formatAmount(order.subtotal, order.currency)}
                    </p>

                    <p>
                        تخفیف:{' '}
                        {formatAmount(order.discount_amount, order.currency)}
                    </p>

                    <p>
                        هزینه ارسال:{' '}
                        {formatAmount(order.shipping_amount, order.currency)}
                    </p>

                    <p>
                        مبلغ نهایی:{' '}
                        <strong>
                            {formatAmount(order.total_amount, order.currency)}
                        </strong>
                    </p>
                </section>

                {order.payments.length > 0 && (
                    <section>
                        <h2>پرداخت‌ها</h2>

                        {order.payments.map((payment) => (
                            <div key={payment.id}>
                                <p>
                                    مبلغ پرداخت:{' '}
                                    {formatAmount(
                                        payment.amount,
                                        order.currency,
                                    )}
                                </p>

                                <p>
                                    وضعیت: {paymentStatusLabel(payment.status)}
                                </p>

                                {payment.gateway && (
                                    <p>درگاه: {payment.gateway}</p>
                                )}

                                {payment.reference_number && (
                                    <p>
                                        شماره مرجع: {payment.reference_number}
                                    </p>
                                )}

                                {payment.transaction_id && (
                                    <p>
                                        شناسه تراکنش: {payment.transaction_id}
                                    </p>
                                )}

                                {payment.paid_at && (
                                    <p>
                                        تاریخ پرداخت:{' '}
                                        {formatDate(payment.paid_at)}
                                    </p>
                                )}
                            </div>
                        ))}
                    </section>
                )}

                {canCancel && (
                    <section>
                        <button
                            type="button"
                            onClick={cancelOrder}
                            disabled={isCancelling}
                        >
                            {isCancelling ? 'در حال لغو سفارش...' : 'لغو سفارش'}
                        </button>
                    </section>
                )}
            </div>
        </>
    );
}
