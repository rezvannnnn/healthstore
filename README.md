# HealthStore

فروشگاه آنلاین محصولات سلامت با Laravel 13، Inertia.js و Vue.

## اجرای محلی

نیازمندی‌ها: PHP 8.3+، Composer، Node.js و npm.

```bash
composer setup
composer dev
```

برای اجرای جداگانه frontend در توسعه:

```bash
npm run dev
```

## بررسی کیفیت قبل از انتشار

```bash
composer ci:check
npm run build
```

`composer ci:check` lint فرمت PHP، فرمت و lint فرانت‌اند، بررسی‌های استاتیک و تست‌های پروژه را اجرا می‌کند.

## استقرار

قبل از production:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
npm ci
npm run build
php artisan optimize
```

در محیط production این مقادیر را بررسی کنید:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
```

`APP_KEY` باید یک مقدار امن و ثابت باشد و هرگز در repository قرار نگیرد. اطلاعات درگاه پرداخت، پیامک و سایر سرویس‌های خارجی نیز فقط از طریق environment configuration تنظیم شوند.

## مسیرهای اصلی

- `/` صفحه اصلی
- `/products` فهرست محصولات
- `/blog` مجله سلامت
- `/cart` سبد خرید کاربران واردشده
- `/checkout` تکمیل سفارش و پرداخت
- `/account/*` ناحیه حساب کاربری
- `/admin/*` پنل مدیریت
- `/sitemap.xml` نقشه سایت

## CI

Workflow اصلی پروژه در `.github/workflows/tests.yml` قرار دارد و روی push و pull request اجرا می‌شود. نتیجه سبز CI شرط پایه برای ادامه تغییرات اصلی پروژه است.
