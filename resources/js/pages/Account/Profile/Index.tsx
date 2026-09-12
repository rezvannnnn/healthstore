import { Head } from '@inertiajs/react';

type User = {
    id: number;
    name: string;
    email: string | null;
    phone: string;
    phone_verified_at: string | null;
};

type Props = {
    user: User;
};

export default function Index({ user }: Props) {
    return (
        <>
            <Head title="پروفایل من" />

            <div>
                <h1>پروفایل من</h1>

                <div>
                    <p>نام: {user.name}</p>

                    <p>ایمیل: {user.email || 'ثبت نشده است'}</p>

                    <p>شماره موبایل: {user.phone}</p>

                    <p>
                        وضعیت موبایل:{' '}
                        {user.phone_verified_at ? 'تأیید شده' : 'تأیید نشده'}
                    </p>
                </div>
            </div>
        </>
    );
}
