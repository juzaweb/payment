<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Events;

use Juzaweb\Modules\Payment\Contracts\Paymentable;

class PaymentFail
{
    public function __construct(public Paymentable $paymentable)
    {
    }
}
