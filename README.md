# داروخونه (Darukhooneh.ir)

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
APP_URL=https://darukhooneh.ir
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

`APP_KEY` باید یک مقدار امن و ثابت باشد و هرگز در repository قرار نگیرد. اطلاعات درگاه پرداخت، پیامک و سایر سرویس‌های خارجی نیز فقط از طریق environment configuration تنظیم شوند.

اگر برنامه پشت reverse proxy یا load balancer اجرا می‌شود، تنظیم HTTPS و forwarded headers سرور را نیز بررسی کنید تا Laravel درخواست‌های HTTPS را به‌درستی تشخیص دهد.

## Scheduler و Queue

برای اجرای cleanup رزروهای منقضی‌شده، scheduler لاراول باید روی سرور فعال باشد. یک cron entry را با کاربر اجرای برنامه تنظیم کنید:

```cron
* * * * * cd /path/to/healthstore && php artisan schedule:run >> /dev/null 2>&1
```

این scheduler دستورات release رزرو موجودی و رزرو کوپن منقضی‌شده را هر دقیقه اجرا می‌کند.

در صورت استفاده از `QUEUE_CONNECTION=database`، یک worker دائمی نیز باید روی سرور اجرا شود:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

برای production بهتر است worker با Supervisor یا systemd مدیریت و پس از deploy با `php artisan queue:restart` راه‌اندازی مجدد شود.

## وضعیت سرویس‌های خارجی

در حال حاضر پیاده‌سازی `FakeSmsProvider` برای محیط توسعه/تست استفاده می‌شود. پیش از production باید provider واقعی پیامک و تنظیمات امن آن در environment configuration جایگزین و پیکربندی شود.

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
