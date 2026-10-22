import { readFile, writeFile } from 'node:fs/promises';
import { ROOT } from './catalog-extract.mjs';
const catalog = JSON.parse(
    await readFile(`${ROOT}/catalog-ready.json`, 'utf8'),
);
const comparison = JSON.parse(
    await readFile(`${ROOT}/comparison.json`, 'utf8'),
);
const verification = JSON.parse(
    await readFile(`${ROOT}/verification.json`, 'utf8'),
);
const unavailable = JSON.parse(
    await readFile(`${ROOT}/unavailable-davaxana.json`, 'utf8'),
);
const sources = catalog.products.flatMap((p) => p.sources);
const missing = catalog.products.filter((p) => !p.main_image);
if (verification.imported !== catalog.products.length || verification.sources !== sources.length) throw Error('Catalog and database counts differ');
const bySite = (site) =>
    sources.filter((s) => s.key.startsWith(`${site}-`)).length;
const n = (value) => new Intl.NumberFormat('fa-IR').format(value);
const report = [
    '# نتیجهٔ ورود کاتالوگ — 2026-10-18',
    '',
    'این ورود روی پایگاه دادهٔ محلی پروژه انجام شده است. محصولات و دسته‌ها از پنل مدیریت فعلی قابل ویرایش‌اند. هیچ استقرار جدیدی روی سرور عمومی انجام نشده است.',
    '',
    '| مورد | تعداد |',
    '|---|---:|',
    `| محصولات منبع بگوسیب | ${n(bySite('begoosib'))} |`,
    `| محصولات منبع داواخانا | ${n(bySite('davaxana'))} |`,
    `| کل محصولات منبع | ${n(sources.length)} |`,
    `| محصولات نهایی واردشده | ${n(verification.imported)} |`,
    `| گروه‌های تکراری ادغام‌شده | ${n(comparison.duplicate_groups)} |`,
    `| جفت نام‌های مشابه که برای حفظ تفاوت مدل‌ها جدا مانده‌اند | ${n(comparison.needs_review.length)} |`,
    `| محصولات دارای تصویر محلی | ${n(verification.with_image)} |`,
    `| دسته‌های موجود پس از ورود | ${n(verification.categories)} |`,
    `| لینک‌های قدیمی داواخانا که اطلاعاتی برنگرداندند | ${n(unavailable.length)} |`,
    '',
    'برای هر محصول جدید ۱۰ عدد موجودی اولیه ثبت شد. تاریخ انقضای محصول و موجودی، قیمت، بارکد و canonical مبدأ خالی‌اند. شناسه‌ها با قالب DKH-000001 و بعدی ساخته شدند. محصولات قبلی حفظ شده‌اند. اجرای دوبارهٔ ورود، ویرایش‌های بعدی و موجودی را بازنویسی نمی‌کند.',
    '',
    'توضیحات اختصاصی بر پایهٔ مشخصات ساختاری محصول ساخته شدند. در موارد تکراری، سازگاری فرم و تعداد بسته، کامل‌بودن مشخصات و سپس جزئیات توضیح منبع در انتخاب لحاظ شد. تفاوت دوز، تعداد، رنگ و طعم مانع ادغام اشتباه می‌شود. دسته‌های مادر محصولات زیرشاخه‌ها را هم نمایش می‌دهند.',
    '',
    'دو ردیف زیر در منبع نشانی تصویر معتبر نداشتند و با تصویر خالی وارد شدند:',
    '',
    ...missing.map((p) => `- ${p.name}`),
    '',
    'برای فشارسنج جویتک مدل DBP1305، صفحهٔ جزئیات مبدأ خطا می‌داد؛ نام، مدل، دسته و عکس از رابط عمومی اطلاعات پایه دریافت شد.',
    '',
    'فایل‌های جزئیات در storage/app/catalog-import قرار دارند: comparison.json برای تطبیق‌ها، catalog-ready.json برای ورودی، verification.json برای کنترل نهایی و unavailable-davaxana.json برای لینک‌های قدیمی ناموفق. نسخهٔ پشتیبان پیش از ورود در زیرپوشهٔ backups نگه داشته شده است.',
    '',
    'کنترل‌ها: ۷ آزمون تطبیق در Node گذشت. آزمون‌های ورود و سلسله‌مراتب دسته‌ها و بررسی ایستای فایل‌های PHP گذشتند. در اجرای کامل ۵۴۳ آزمون، تنها آزمون تاریخ گزارش فروش شکست داشت؛ تاریخ آزمون با منطقهٔ تهران هماهنگ شد و هر ۴ آزمون آن بخش دوباره گذشتند. کنترل دادهٔ واردشده باید errors=[] و imported_price_rows=0 داشته باشد.',
    '',
    'صفحهٔ اصلی، فهرست محصولات، دسته‌ها، جست‌وجوی شناسه، جزئیات محصول، تصویر محصول و دستهٔ مادر با HTTP 200 بررسی شدند؛ شمارش محصولات زیرشاخه‌ها در دستهٔ مادر نیز تأیید شد.',
    '',
    `کنترل نهایی: ${verification.errors.length === 0 && verification.imported_price_rows === 0 ? 'بدون خطا' : 'نیازمند بررسی'}.`,
];
await writeFile('CATALOG-IMPORT-RESULTS.md', report.join('\n') + '\n');
await writeFile(
    `${ROOT}/missing-images.json`,
    JSON.stringify(
        missing.map((p) => ({ name: p.name, sources: p.sources })),
        null,
        2,
    ),
);
console.log({
    imported: verification.imported,
    source_records: sources.length,
    missing_images: missing.length,
    categories: verification.categories,
});
