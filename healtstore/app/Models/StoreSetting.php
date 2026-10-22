<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /** @return array<int, array{question: string, answer: string}> */
    public static function publicFaqs(): array
    {
        $saved = json_decode(static::getValue('faq_items', 'null'), true);
        if (is_array($saved)) {
            return array_values(array_filter($saved, fn ($item) => is_array($item) && is_string($item['question'] ?? null) && is_string($item['answer'] ?? null)));
        }

        return [
            ['question' => 'چطور محصول موردنظر را پیدا کنم؟', 'answer' => 'نام محصول، کد کالا یا بارکد را جستجو کنید. در صفحه محصولات می‌توانید دسته، برند، بازه قیمت و فقط کالاهای موجود را انتخاب کنید.'],
            ['question' => 'قیمت و موجودی چه زمانی بررسی می‌شود؟', 'answer' => 'قیمت و موجودی هنگام افزودن به سبد و پیش از ثبت سفارش دوباره بررسی می‌شوند. افزودن محصول به سبد به‌تنهایی به معنی رزرو موجودی نیست.'],
            ['question' => 'تاریخ انقضای محصول را از کجا ببینم؟', 'answer' => 'تاریخ‌های ثبت‌شده برای موجودی فعلی در صفحه محصول نمایش داده می‌شوند. اگر تاریخ ثبت نشده یا سری‌های کالا متفاوت باشند، برای تأیید تاریخ سری ارسالی با پشتیبانی هماهنگ کنید.'],
        ];
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
