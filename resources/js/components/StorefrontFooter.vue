<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
</script>

<template>
    <footer dir="rtl" class="border-t border-dh-100 bg-dh-900 text-white">
        <div
            class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.4fr_1fr] lg:px-8"
        >
            <div>
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10"
                    >
                        <BrandLogo
                            imageClass="h-10 w-10 rounded-xl bg-white p-1 object-contain"
                        />
                    </span>
                    <div>
                        <div class="text-xl font-extrabold">داروخونه</div>
                        <div class="mt-1 text-xs text-dh-200">
                            دارو و محصولات بهداشتی
                        </div>
                    </div>
                </div>
                <p class="mt-5 max-w-xl text-sm leading-7 text-dh-100">
                    یک تجربه ساده و روشن برای پیدا کردن محصولات سلامت و مدیریت
                    سفارش‌های روزمره.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-6 text-sm">
                <div>
                    <h3 class="font-extrabold text-white">دسترسی سریع</h3>
                    <div class="mt-3 space-y-2 text-dh-100">
                        <Link href="/products" class="block hover:text-white"
                            >محصولات</Link
                        >
                        <Link href="/categories" class="block hover:text-white"
                            >دسته‌بندی‌ها</Link
                        >
                        <Link href="/brands" class="block hover:text-white"
                            >برندها</Link
                        >
                    </div>
                </div>
                <div>
                    <h3 class="font-extrabold text-white">مطالب</h3>
                    <div class="mt-3 space-y-2 text-dh-100">
                        <Link href="/blog" class="block hover:text-white"
                            >مجله سلامت</Link
                        >
                        <Link href="/login" class="block hover:text-white"
                            >حساب کاربری</Link
                        >
                        <Link href="/cart" class="block hover:text-white"
                            >سبد خرید</Link
                        >
                    </div>
                </div>
            </div>
        </div>
        <div
            class="mt-8 flex flex-col gap-5 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="font-extrabold text-white">ما را دنبال کنید</div>
                <div class="mt-3 flex gap-3">
                    <a
                        href="https://instagram.com/Darukhooneh.ir"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="اینستاگرام داروخونه"
                        class="flex size-10 items-center justify-center rounded-xl bg-white/10 text-dh-100 hover:bg-white/20 hover:text-white"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="size-5"
                            fill="none"
                            aria-hidden="true"
                        >
                            <rect
                                x="3.5"
                                y="3.5"
                                width="17"
                                height="17"
                                rx="4.5"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <circle
                                cx="12"
                                cy="12"
                                r="4"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <circle
                                cx="17.5"
                                cy="6.7"
                                r="1"
                                fill="currentColor"
                            />
                        </svg>
                    </a>
                    <a
                        href="https://t.me/Darukhooneh_ir"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="تلگرام داروخونه"
                        class="flex size-10 items-center justify-center rounded-xl bg-white/10 text-dh-100 hover:bg-white/20 hover:text-white"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="size-5"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                d="M21.4 4.6 18.2 19c-.2 1-1 1.3-1.8.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.8-7.9c.4-.4-.1-.6-.6-.2L6 12.9 1.4 11.4c-1-.3-1-1 .2-1.4L19.6 3c.9-.3 2-.2 1.8 1.6Z"
                            />
                        </svg>
                    </a>
                </div>
            </div>
            <p class="text-xs leading-6 text-dh-200 sm:max-w-2xl sm:text-left">
                تمامی حقوق این وب‌سایت متعلق به داروخونه است و هرگونه کپی‌برداری
                از محتوا، طراحی یا مطالب آن بدون اجازه کتبی مجاز نیست.
            </p>
        </div>
    </footer>
</template>
