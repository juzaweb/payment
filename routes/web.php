<?php

use Juzaweb\Modules\Payment\Http\Controllers\PaymentController;

Route::post('payment/{module}', [PaymentController::class, 'purchase'])
    ->name('payment.purchase');
