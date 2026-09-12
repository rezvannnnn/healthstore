<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DevAuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ZarinPalCallbackController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/checkout', [CheckoutController::class, 'show'])
    ->name('checkout.show');

Route::post('/checkout/confirm', [CheckoutController::class, 'confirm'])
    ->name('checkout.confirm');

Route::post('/checkout/reject', [CheckoutController::class, 'reject'])
    ->name('checkout.reject');

Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])
    ->name('orders.show');

Route::post('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel'])
    ->name('orders.cancel');

Route::post('/orders/{orderNumber}/payment', [PaymentController::class, 'start'])
    ->name('orders.payment.start');

Route::get('/payment/zarinpal/callback', [ZarinPalCallbackController::class, 'handle'])
    ->name('payment.zarinpal.callback');

Route::post('/register/send-otp', [RegistrationController::class, 'sendOtp'])
    ->name('register.send-otp');

Route::post('/register/verify-otp', [RegistrationController::class, 'verifyOtp'])
    ->name('register.verify-otp');

Route::post('/register', [RegistrationController::class, 'store'])
    ->name('register');

Route::post('/login', [LoginController::class, 'store'])
    ->name('login');

Route::post('/login/send-otp', [LoginController::class, 'sendOtp'])
    ->name('login.send-otp');

Route::post('/logout', [LogoutController::class, 'store'])
    ->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/account/orders', [OrderController::class, 'index'])
        ->name('account.orders.index');

    Route::get('/account/addresses', [AddressController::class, 'index'])
        ->name('account.addresses.index');

    Route::post('/account/addresses', [AddressController::class, 'store'])
        ->name('account.addresses.store');

    Route::put('/account/addresses/{address}', [AddressController::class, 'update'])
        ->name('account.addresses.update');

    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])
        ->name('account.addresses.destroy');

    Route::get('/account/profile', [ProfileController::class, 'index'])
        ->name('account.profile.index');

    Route::put('/account/profile', [ProfileController::class, 'update'])
        ->name('account.profile.update');
});

/*
|--------------------------------------------------------------------------
| Development Authentication
|--------------------------------------------------------------------------
|
| Temporary local-only authentication routes.
| These will be removed when the real login/register flow is implemented.
|
*/

Route::get('/dev/login', [DevAuthController::class, 'login'])
    ->name('dev.login');

Route::get('/dev/logout', [DevAuthController::class, 'logout'])
    ->name('dev.logout');
