<?php

namespace Juzaweb\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Juzaweb\Core\Models\Model;
use Juzaweb\Core\Traits\HasAPI;
use Juzaweb\Core\Traits\HasCodeWithMonth;
use Juzaweb\Modules\Payment\Contracts\Paymentable;
use Juzaweb\Modules\Payment\Traits\HasPayment;

class TestOrder extends Model implements Paymentable
{
    use HasAPI, HasPayment, HasCodeWithMonth;

    protected $table = 'test_orders';

    protected $fillable = [
        'code',
        'amount',
        'status',
    ];

    public function paymentHistories(): MorphMany
    {
        return $this->morphMany(PaymentHistory::class, 'paymentable');
    }

    public function getTotalAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return 'USD';
    }

    public function getPaymentDescription(): string
    {
        return __('Payment order #:code', ['code' => $this->code]);
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
