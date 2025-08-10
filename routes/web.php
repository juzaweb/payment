<?php

use Juzaweb\Modules\Payment\Http\Controllers\PaymentController;

Route::post('payment/{module}', [PaymentController::class, 'purchase'])
    ->name('payment.purchase');
Route::get('payment/{module}/return/{paymentHistoryId}', [PaymentController::class, 'return'])
    ->name('payment.return');
Route::get('payment/{module}/cancel/{paymentHistoryId}', [PaymentController::class, 'cancel'])
    ->name('payment.cancel');
Route::get('payment/{module}/status/{paymentHistoryId}', [PaymentController::class, 'embed'])
    ->name('payment.embed');
