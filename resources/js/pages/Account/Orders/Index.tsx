import { Head, Link } from '@inertiajs/react';

type Order = {
    id: number;
    user_id: number;
    order_number: string;
    status: string;
    payment_status: string;
    subtotal: string;
    discount_amount: string;
    shipping_amount: string;
    total_amount: string;
    currency: string;
    created_at: string;
    updated_at: string;
    paid_at: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
    cancelled_at: string | null;
};

type Props = {
    orders: Order[];
};

function statusLabel(status: string): string {
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
            return 'پرداخت لغو شده';

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

function formatDate(date: string): string {
    return new Date(date).toLocaleString('fa-IR');
}

export default function Index({ orders }: Props) {
    return (
        <>
            <Head title="سفارش‌های من" />

            <div>
                <h1>سفارش‌های من</h1>

                {orders.length === 0 ? (
                    <div>
                        <p>هنوز سفارشی ثبت نکرده‌اید.</p>

                        <Link href="/">
                            بازگشت به فروشگاه
                        </Link>
                    </div>
                ) : (
                    <div>
                        {orders.map((order) => (
                            <div key={order.id}>
                                <h2>
                                    سفارش {order.order_number}
                                </h2>

                                <p>
                                    وضعیت سفارش:{' '}
                                    {statusLabel(order.status)}
                                </p>

                                <p>
                                    وضعیت پرداخت:{' '}
                                    {paymentStatusLabel(
                                        order.payment_status
                                    )}
                                </p>

                                <p>
                                    مبلغ:
                                    {' '}
                                    {formatAmount(
                                        order.total_amount,
                                        order.currency
                                    )}
                                </p>

                                <p>
                                    تاریخ ثبت:
                                    {' '}
                                    {formatDate(
                                        order.created_at
                                    )}
                                </p>

                                <Link
                                    href={`/orders/${order.order_number}`}
                                >
                                    مشاهده جزئیات سفارش
                                </Link>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}