<?php

namespace App\Support;

class CatalogCategoryNames
{
    public const TRANSLATIONS = [
        "Women's Multivitamin" => 'مولتی‌ویتامین بانوان',
        "Women's 50+ Multivitamin" => 'مولتی‌ویتامین بانوان بالای ۵۰ سال',
        '50+ Multivitamin' => 'مولتی‌ویتامین بالای ۵۰ سال',
        "Women's 70+ Multivitamin" => 'مولتی‌ویتامین بانوان بالای ۷۰ سال',
        "Men's Multivitamin" => 'مولتی‌ویتامین آقایان',
        "Men's Fertility Multivitamin" => 'مکمل باروری آقایان',
        "Men's Supplement" => 'مکمل آقایان',
        'Energy & Multivitamin' => 'انرژی و مولتی‌ویتامین',
        'General Multivitamin' => 'مولتی‌ویتامین عمومی',
        'General Supplement' => 'مکمل عمومی',
        'Bone & Calcium' => 'استخوان و کلسیم',
        'Bone & Joint' => 'استخوان و مفاصل',
        'Joint Support' => 'مکمل مفاصل',
        "Women's Health Supplement" => 'مکمل سلامت بانوان',
        'Pain & Joint Support' => 'مراقبت از مفاصل',
        "Children's Calcium" => 'کلسیم کودکان',
        'L-Arginine' => 'ال‌آرژینین',
        'L-Carnitine' => 'ال‌کارنیتین',
        'L-Carnitine & L-Arginine' => 'ال‌کارنیتین و ال‌آرژینین',
        'B Vitamins' => 'ویتامین‌های گروه ب',
        'Weight Management' => 'مدیریت وزن',
        'Spirulina & Algae' => 'اسپیرولینا و جلبک',
        'Hair & Beauty' => 'مو و زیبایی',
        'Zinc' => 'زینک',
        'Biotin' => 'بیوتین',
        'Zinc & Biotin' => 'زینک و بیوتین',
        'Zinc & B Vitamins' => 'زینک و ویتامین‌های گروه ب',
        'Zinc & Vitamins' => 'زینک و ویتامین‌ها',
        'Collagen' => 'کلاژن',
        'Beauty Supplement' => 'مکمل زیبایی',
        'Collagen & Biotin' => 'کلاژن و بیوتین',
        'Vitamin C & Zinc' => 'ویتامین ث و زینک',
        'Vitamin C' => 'ویتامین ث',
        'Magnesium' => 'منیزیم',
    ];

    public static function translate(string $name): string
    {
        return self::TRANSLATIONS[$name] ?? $name;
    }
}
