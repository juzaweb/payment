<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Juzaweb\Modules\Payment\Models\PaymentHistory;

trait HasPayment
{
    public function paymentHistories(): MorphMany
    {
        return $this->morphMany(PaymentHistory::class, 'paymentable');
    }
}
