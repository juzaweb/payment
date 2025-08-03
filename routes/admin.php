<?php

use Juzaweb\Core\Facades\RouteResource;
use Juzaweb\Modules\Payment\Http\Controllers\MethodController;

RouteResource::admin('payment-methods', MethodController::class);
