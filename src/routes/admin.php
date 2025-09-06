<?php

use Juzaweb\Core\Facades\RouteResource;
use Juzaweb\Modules\Payment\Http\Controllers\MethodController;

Route::get('payment-methods/{driver}/get-data', [MethodController::class, 'getData']);
RouteResource::admin('payment-methods', MethodController::class);
