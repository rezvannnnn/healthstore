<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head>
        <title>HealthStore | فروشگاه آنلاین محصولات سلامت</title>
        <meta
            name="description"
            content="خرید و بررسی آنلاین محصولات سلامت با جستجوی محصول، سبد خرید، بررسی موجودی و مسیر پرداخت یکپارچه."
        />
        <link rel="canonical" href="/" />
        <meta property="og:title" content="HealthStore | فروشگاه آنلاین محصولات سلامت" />
        <meta
            property="og:description"
            content="خرید و بررسی آنلاین محصولات سلامت با یک مسیر ساده و یکپارچه."
        />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="/" />
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="HealthStore | فروشگاه آنلاین محصولات سلامت" />
        <meta
            name="twitter:description"
            content="خرید و بررسی آنلاین محصولات سلامت با یک مسیر ساده و یکپارچه."
        />
    </Head>

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8"
            >
                <Link href="/" class="text-2xl font-bold text-slate-900">
                    Health<span class="text-emerald-600">Store</span>
                </Link>

                <nav
                    class="hidden items-center gap-6 text-sm font-medium sm:flex"
                >
                    <Link href="/" class="transition hover:text-emerald-600">
                        خانه
                    </Link>
                    <Link
                        href="/products"
                        class="transition hover:text-emerald-600"
                    >
                        محصولات
                    </Link>
                    <Link
                        href="/cart"
                        class="transition hover:text-emerald-600"
                    >
                        سبد خرید
                    </Link>
                </nav>

                <div class="flex items-center gap-2 text-sm font-medium">
                    <Link
                        href="/login"
                        class="rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100"
                    >
                        ورود
                    </Link>
                    <Link
                        href="/register"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-white transition hover:bg-emerald-700"
                    >
                        ثبت‌نام
                    </Link>
                </div>
            </div>
        </header>

        <main>
            <section
                class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
            >
                <div
                    class="grid items-center gap-12 lg:grid-cols-[1.15fr_0.85fr]"
                >
                    <div>
                        <p
                            class="mb-4 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800"
                        >
                            فروشگاه آنلاین سلامت
                        </p>
                        <h1
                            class="max-w-3xl text-4xl leading-tight font-bold tracking-tight sm:text-5xl lg:text-6xl"
                        >
                            محصولات مورد نیازتان را ساده و مطمئن خرید کنید.
                        </h1>
                        <p
                            class="mt-6 max-w-2xl text-lg leading-8 text-slate-600"
                        >
                            HealthStore برای جستجو، بررسی و خرید آنلاین محصولات
                            سلامت طراحی شده است؛ با سبد خرید، بررسی موجودی و
                            روند پرداخت در یک مسیر یکپارچه.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link
                                href="/products"
                                class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:bg-emerald-700"
                            >
                                مشاهده محصولات
                            </Link>
                            <Link
                                href="/register"
                                class="rounded-xl border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-800 transition hover:bg-slate-100"
                            >
                                ساخت حساب کاربری
                            </Link>
                        </div>
                    </div>

                    <div
                        class="rounded-3xl bg-slate-900 p-8 text-white shadow-xl sm:p-10"
                    >
                        <div class="text-5xl">🛒</div>
                        <h2 class="mt-6 text-2xl font-bold">
                            خرید از هر جا، با مسیر مشخص
                        </h2>
                        <p class="mt-4 leading-7 text-slate-300">
                            محصولات را انتخاب کنید، تعداد را مشخص کنید، آدرس
                            ارسال را ثبت کنید و سفارش خود را تا مرحله پرداخت
                            دنبال کنید.
                        </p>

                        <div
                            class="mt-8 grid gap-3 sm:grid-cols-3 lg:grid-cols-1"
                        >
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4"
                            >
                                <div class="font-semibold">جستجوی محصول</div>
                                <div class="mt-1 text-sm text-slate-400">
                                    دسترسی سریع به کاتالوگ
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4"
                            >
                                <div class="font-semibold">سبد خرید</div>
                                <div class="mt-1 text-sm text-slate-400">
                                    مدیریت تعداد و قیمت
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4"
                            >
                                <div class="font-semibold">سفارش و پرداخت</div>
                                <div class="mt-1 text-sm text-slate-400">
                                    ادامه مسیر تا تکمیل خرید
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-white">
                <div
                    class="mx-auto grid max-w-6xl gap-4 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:px-8"
                >
                    <div class="rounded-2xl bg-slate-50 p-5">
                        <div class="font-bold">موجودی بررسی می‌شود</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            قبل از ثبت سفارش، وضعیت موجودی محصولات بررسی می‌شود.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-5">
                        <div class="font-bold">قیمت به‌روز</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            تغییرات قیمت در مسیر خرید بررسی و به شما اعلام
                            می‌شود.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-5">
                        <div class="font-bold">پیگیری سفارش</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            وضعیت سفارش پس از ثبت در حساب کاربری قابل پیگیری
                            است.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-slate-950 text-slate-400">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm sm:px-6 lg:px-8"
            >
                <div class="font-semibold text-white">HealthStore</div>
                <div>فروشگاه آنلاین محصولات سلامت</div>
            </div>
        </footer>
    </div>
</template>
