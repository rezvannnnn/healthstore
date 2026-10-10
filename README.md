# داروخونه (Darukhooneh.ir)

فروشگاه آنلاین محصولات سلامت با Laravel 13، Inertia.js و Vue.

## اجرای محلی

نیازمندی‌های نسخهٔ آزمایش‌شده: PHP 8.4 با افزونه‌های GD، mbstring و PDO SQLite، Composer 2، Node.js 22.12+ و npm.

```bash
composer setup
composer dev
```

برای ساخت مدیر اولیه در محیط local، ابتدا مقادیر زیر را در فایل `.env` تنظیم کنید:

```env
ADMIN_SEED_USERNAME=admin
ADMIN_SEED_PASSWORD=یک-رمز-عبور-حداقل-۱۲-کاراکتری
```

سپس:

```bash
php artisan migrate --seed
```

ورود مدیریت از مسیر `/admin/login` انجام می‌شود و صفحه ورود مدیریت مسیر ثبت‌نام ندارد.

برای اجرای جداگانه frontend در توسعه:

```bash
npm run dev
```

## بررسی کیفیت قبل از انتشار

```bash
composer ci:check
npm run build:ssr
```

`composer ci:check` lint فرمت PHP، فرمت و lint فرانت‌اند، بررسی‌های استاتیک و تست‌های پروژه را اجرا می‌کند.

## استقرار

قبل از production:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
npm ci
npm run build:ssr
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

برای ساخت اولین مدیر سیستم پس از اجرای migrationها، فقط از روی سرور برنامه این دستور را اجرا کنید:

```bash
php artisan admin:create
```

این دستور در صورت وجود کاربر با آن شماره، حساب را به مدیر ارتقا می‌دهد و برای حساب جدید/ارتقایافته تأیید اولیه شماره را از طریق دسترسی مستقیم سرور ثبت می‌کند. آن را در محیط عمومی یا از طریق وب اجرا نکنید.

در صورت استفاده از `QUEUE_CONNECTION=database`، یک worker دائمی نیز باید روی سرور اجرا شود:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

برای production بهتر است worker با Supervisor یا systemd مدیریت و پس از deploy با `php artisan queue:restart` راه‌اندازی مجدد شود.

## وضعیت سرویس‌های خارجی

در توسعه و تست، `SMS_PROVIDER=fake` از `FakeSmsProvider` استفاده می‌کند. در production اگر provider واقعی تنظیم نشده باشد، برنامه عمداً ارسال OTP را موفق اعلام نمی‌کند و باید یک provider واقعی پیامک و credentialهای امن آن در environment configuration پیکربندی شود.

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

## اجرای مستقل کنار پروژهٔ دیگر

سرور HTTP این پروژه می‌تواند روی ۸۰۰۱ اجرا شود و SSR پیش‌فرض آن روی ۱۳۷۱۵ است. اجرای پروژهٔ دیگر روی ۸۰۰۰ یا SSR روی ۱۳۷۱۴ نیازی به توقف ندارد.

```bash
php artisan serve --host=127.0.0.1 --port=8001 --no-reload
# در ترمینال جدا:
npm run build:ssr
node bootstrap/ssr/ssr.js
```

در `.env` آدرس محلی `APP_URL=http://127.0.0.1:8001`، زبان `APP_LOCALE=fa`، مقدار `SMS_PROVIDER=fake` و `PAYMENT_PROVIDER=disabled` را تنظیم کنید. FakeSms فقط در محیط local کد را برای تست نمایش می‌دهد. سرویس SMS واقعی هنوز متصل نیست و در production ارسال ساختگی موفق اعلام نمی‌شود. درگاه پیش‌فرض غیرفعال است؛ سفارش ثبت می‌شود اما پرداخت واقعی انجام نمی‌شود. اتصال آیندهٔ ZarinPal با تنظیم provider و اعتبارنامه‌های واقعی امکان‌پذیر است و باید جداگانه با بانک آزموده شود.

در Windows این پروژه، افزونه‌ها از پوشهٔ زیر فعال می‌شوند:

```powershell
$env:PHP_INI_SCAN_DIR = "$PWD\tools\php\ini"
php artisan test
```

سرور SSR باید پس از هر build مجدداً اجرا شود؛ در production آن را با supervisor/systemd و دسترسی فقط loopback نگه دارید. `INERTIA_SSR_URL=http://127.0.0.1:13715` و `INERTIA_SSR_PORT=13715` باید برای PHP و Node سازگار باشند. بدون SSR رابط کار می‌کند، اما رندر کامل اولیه برای SEO نیازمند سرویس SSR است.

## راه‌اندازی امکانات مدیریت

از `/admin/security` مدیر می‌تواند TOTP را با برنامهٔ Authenticator فعال و کدهای بازیابی یک‌بارمصرف را ذخیره کند. فعال‌سازی نیازمند رمز فعلی و تأیید کد است. برای حساب‌های واقعی هیچ تغییر خودکاری در رمز یا MFA انجام نشده است. تغییر رمز، نشست‌های قبلی مدیریت را نامعتبر می‌کند. تاریخچهٔ تغییرات بدون مقادیر حساس در `/admin/audit` نمایش داده می‌شود.

اطلاعات تماس و متن قوانین ارسال، مرجوعی و حریم خصوصی در تنظیمات فروشگاه قابل ویرایش است. پیش از فروش واقعی، محتوای واقعی این بخش‌ها را تکمیل کنید. گزارش‌های فروش بر اساس زمان پرداخت در منطقهٔ زمانی فروشگاه محاسبه می‌شوند و خروجی CSV دارند.

تراکنش بانکی تأییدشده‌ای که سفارش آن رزرو معتبر ندارد با وضعیت «نیازمند بررسی» حفظ می‌شود و پرداخت مجدد آن مسدود است. مدیر باید چنین موردی را با بانک تطبیق دهد و ارسال یا استرداد را خارج از عملیات خودکار بررسی کند؛ قابلیت استرداد بانکی خودکار پیاده نشده است.

برای production، scheduler، queue، HTTPS، نسخهٔ پشتیبان خارج از سرور و آزمون بازیابی آن باید در زیرساخت تنظیم شوند. سیاست CSP فعلاً Report-Only است تا پیش از اعمال سخت‌گیرانه با سرویس‌های واقعی بررسی شود. گزارش این تحویل در `DELIVERY-2026-10-10.md` قرار دارد.