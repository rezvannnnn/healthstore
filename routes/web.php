<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ZarinPalCallbackController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/login', 'Auth/Login')->name('login');
Route::inertia('/register', 'Auth/Register')->name('register.form');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/blog', [ArticleController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [ArticleController::class, 'show'])->name('blog.show');

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
    Route::post('/checkout/reject', [CheckoutController::class, 'reject'])->name('checkout.reject');

    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{orderNumber}/payment', [PaymentController::class, 'start'])->name('orders.payment.start');
});

Route::get('/payment/zarinpal/callback', [ZarinPalCallbackController::class, 'handle'])->name('payment.zarinpal.callback');
Route::post('/register/send-otp', [RegistrationController::class, 'sendOtp'])->name('register.send-otp');
Route::post('/register/verify-otp', [RegistrationController::class, 'verifyOtp'])->name('register.verify-otp');
Route::post('/register', [RegistrationController::class, 'store'])->name('register');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/login/send-otp', [LoginController::class, 'sendOtp'])->name('login.send-otp');
Route::post('/logout', [LogoutController::class, 'store'])->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::get('/brands', [AdminBrandController::class, 'index'])->name('brands.index');
    Route::get('/brands/create', [AdminBrandController::class, 'create'])->name('brands.create');
    Route::post('/brands', [AdminBrandController::class, 'store'])->name('brands.store');
    Route::get('/brands/{brand}/edit', [AdminBrandController::class, 'edit'])->name('brands.edit');
    Route::put('/brands/{brand}', [AdminBrandController::class, 'update'])->name('brands.update');
    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [AdminInventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/{inventory}/adjust', [AdminInventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('/inventory/{inventory}/movements', [AdminInventoryController::class, 'movements'])->name('inventory.movements');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{user}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::put('/cart/{cart}/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/{cart}/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders.index');
    Route::get('/account/addresses', [AddressController::class, 'index'])->name('account.addresses.index');
    Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/account/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');
    Route::get('/account/profile', [ProfileController::class, 'index'])->name('account.profile.index');
    Route::put('/account/profile', [ProfileController::class, 'update'])->name('account.profile.update');
});
