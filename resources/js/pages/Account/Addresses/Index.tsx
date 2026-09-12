import { Head } from '@inertiajs/react';

type Address = {
    id: number;
    user_id: number;
    title: string | null;
    recipient_name: string;
    phone: string;
    province: string | null;
    city: string | null;
    address: string;
    postal_code: string | null;
    is_default: boolean;
    created_at: string;
    updated_at: string;
};

type Props = {
    addresses: Address[];
};

export default function Index({ addresses }: Props) {
    return (
        <>
            <Head title="آدرس‌های من" />

            <div>
                <h1>آدرس‌های من</h1>

                {addresses.length === 0 ? (
                    <p>هنوز آدرسی ثبت نشده است.</p>
                ) : (
                    <div>
                        {addresses.map((address) => (
                            <div key={address.id}>
                                <h2>
                                    {address.title || 'آدرس'}
                                    {address.is_default ? ' (پیش‌فرض)' : ''}
                                </h2>

                                <p>
                                    گیرنده: {address.recipient_name}
                                </p>

                                <p>
                                    تلفن: {address.phone}
                                </p>

                                <p>
                                    {address.province || ''}
                                    {address.province && address.city
                                        ? '، '
                                        : ''}
                                    {address.city || ''}
                                </p>

                                <p>{address.address}</p>

                                {address.postal_code && (
                                    <p>
                                        کد پستی: {address.postal_code}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}