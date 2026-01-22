<?php

use Juzaweb\Modules\Payment\Http\Controllers\MethodController;
use Juzaweb\Modules\Payment\Http\Controllers\OrderController;

Route::get('payment-methods/{driver}/get-data', [MethodController::class, 'getData']);

Route::admin('orders', OrderController::class)->except(['create', 'store']);
Route::admin('payment-methods', MethodController::class);
