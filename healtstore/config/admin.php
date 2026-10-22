<?php

return [
    'seed' => [
        'username' => env('ADMIN_SEED_USERNAME', 'admin'),
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],

    'permissions' => [
        'products.view' => 'مشاهده محصولات',
        'products.manage' => 'ایجاد و ویرایش محصولات',
        'categories.manage' => 'مدیریت دسته‌بندی‌ها',
        'brands.manage' => 'مدیریت برندها',
        'inventory.view' => 'مشاهده موجودی و گردش انبار',
        'inventory.manage' => 'ثبت و اصلاح موجودی',
        'orders.view' => 'مشاهده سفارش‌ها',
        'orders.manage' => 'تغییر وضعیت سفارش‌ها',
        'payments.view' => 'مشاهده پرداخت‌ها',
        'customers.view' => 'مشاهده مشتری‌ها',
        'coupons.manage' => 'مدیریت کدهای تخفیف',
        'content.manage' => 'مدیریت مقالات',
        'reports.view' => 'مشاهده گزارش‌ها',
        'settings.manage' => 'مدیریت تنظیمات فروشگاه',
    ],

    'permission_groups' => [
        [
            'label' => 'کالا و کاتالوگ',
            'permissions' => [
                'products.view',
                'products.manage',
                'categories.manage',
                'brands.manage',
            ],
        ],
        [
            'label' => 'انبار',
            'permissions' => [
                'inventory.view',
                'inventory.manage',
            ],
        ],
        [
            'label' => 'سفارش و مالی',
            'permissions' => [
                'orders.view',
                'orders.manage',
                'payments.view',
                'reports.view',
            ],
        ],
        [
            'label' => 'مشتری و فروش',
            'permissions' => [
                'customers.view',
                'coupons.manage',
            ],
        ],
        [
            'label' => 'محتوا و تنظیمات',
            'permissions' => [
                'content.manage',
                'settings.manage',
            ],
        ],
    ],

    'admin_only_routes' => [
        'admin.audit',
        'admin.users.index',
        'admin.users.store',
        'admin.users.update',
        'admin.logout',
    ],

    'route_permissions' => [
        'admin.articles.preview' => 'content.manage',
        'admin.products.index' => 'products.view',
        'admin.products.create' => 'products.manage',
        'admin.products.store' => 'products.manage',
        'admin.products.edit' => 'products.manage',
        'admin.products.update' => 'products.manage',

        'admin.categories.index' => 'categories.manage',
        'admin.categories.create' => 'categories.manage',
        'admin.categories.store' => 'categories.manage',
        'admin.categories.edit' => 'categories.manage',
        'admin.categories.update' => 'categories.manage',

        'admin.brands.index' => 'brands.manage',
        'admin.brands.create' => 'brands.manage',
        'admin.brands.store' => 'brands.manage',
        'admin.brands.edit' => 'brands.manage',
        'admin.brands.update' => 'brands.manage',

        'admin.inventory.index' => 'inventory.view',
        'admin.inventory.store' => 'inventory.manage',
        'admin.inventory.adjust' => 'inventory.manage',
        'admin.inventory.movements' => 'inventory.view',

        'admin.orders.index' => 'orders.view',
        'admin.orders.show' => 'orders.view',
        'admin.orders.status' => 'orders.manage',

        'admin.payments.index' => 'payments.view',
        'admin.payments.show' => 'payments.view',

        'admin.customers.index' => 'customers.view',
        'admin.customers.show' => 'customers.view',

        'admin.coupons.index' => 'coupons.manage',
        'admin.coupons.store' => 'coupons.manage',
        'admin.coupons.update' => 'coupons.manage',
        'admin.coupons.destroy' => 'coupons.manage',

        'admin.articles.index' => 'content.manage',
        'admin.articles.create' => 'content.manage',
        'admin.articles.store' => 'content.manage',
        'admin.articles.edit' => 'content.manage',
        'admin.articles.update' => 'content.manage',
        'admin.articles.destroy' => 'content.manage',

        'admin.reports.index' => 'reports.view',

        'admin.settings.index' => 'settings.manage',
        'admin.settings.update' => 'settings.manage',
    ],

    'landing_routes' => [
        'admin.dashboard',
        'admin.products.index',
        'admin.inventory.index',
        'admin.orders.index',
        'admin.payments.index',
        'admin.customers.index',
        'admin.coupons.index',
        'admin.articles.index',
        'admin.reports.index',
        'admin.settings.index',
    ],
];
