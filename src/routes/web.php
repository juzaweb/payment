<?php

use Juzaweb\Modules\Payment\Http\Controllers\PaymentController;
use Juzaweb\Modules\Payment\Http\Controllers\CartController;
use Juzaweb\Modules\Payment\Http\Controllers\CheckoutController;

Route::post('payment/{module}', [PaymentController::class, 'checkout'])
    ->name('payment.checkout');
Route::get('order/{orderId}/checkout', [CheckoutController::class, 'orderCheckout'])
    ->name('payment.order.checkout');

Route::post('payment/{module}/purchase', [PaymentController::class, 'purchase'])
    ->name('payment.purchase');
Route::get('payment/{module}/return/{paymentHistoryId}', [PaymentController::class, 'return'])
    ->name('payment.return');
Route::get('payment/{module}/cancel/{paymentHistoryId}', [PaymentController::class, 'cancel'])
    ->name('payment.cancel');
Route::get('payment/{module}/embed/{paymentHistoryId}', [PaymentController::class, 'embed'])
    ->name('payment.embed');
Route::get('payment/{module}/status/{paymentHistoryId}', [PaymentController::class, 'status'])
    ->name('payment.status');

Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
Route::delete('cart/{itemId}', [CartController::class, 'remove'])
    ->name('cart.remove');

Route::get('checkout/{module}/{cartId}', [CheckoutController::class, 'index'])
    ->name('checkout');
Route::post('checkout/{module}/{cartId}', [CheckoutController::class, 'index']);

Route::get('invoices/{orderId}', [CheckoutController::class, 'thankyou'])
    ->name('checkout.thankyou');
