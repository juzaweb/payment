<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

use Juzaweb\Modules\Payment\Http\Controllers\PaymentController;

Route::post('payment/{module}/webhook/{driver}', [PaymentController::class, 'webhook'])
    ->name('payment.webhook');
 