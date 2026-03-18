<?php

/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @author     The Anh Dang
 *
 * @link       https://cms.juzaweb.com
 *
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Events;

use Juzaweb\Modules\Payment\Contracts\Paymentable;

class PaymentSuccess
{
    public function __construct(public Paymentable $paymentable, public array $params) {}
}
