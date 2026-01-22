<?php

namespace Juzaweb\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Juzaweb\Modules\Core\Models\Model;
use Juzaweb\Modules\Core\Traits\HasAPI;
use Juzaweb\Modules\Core\Traits\HasCodeWithMonth;
use Juzaweb\Modules\Core\Traits\HasCreator;
use Juzaweb\Modules\Payment\Contracts\Paymentable;
use Juzaweb\Modules\Payment\Enums\OrderDeliveryStatus;
use Juzaweb\Modules\Payment\Enums\OrderPaymentStatus;

class Order extends Model implements Paymentable
{
    use HasAPI, HasCodeWithMonth, HasUuids,  HasCreator;

    protected $table = 'orders';

    protected $fillable = [
        'code',
        'address',
        'country_code',
        'quantity',
        'total_price',
        'total',
        'payment_method_id',
        'payment_method_name',
        'note',
        'payment_status',
        'delivery_status',
        'module',
    ];

    protected $casts = [
        'other_address' => 'boolean',
        'total_price' => 'float',
        'total' => 'float',
        'discount' => 'float',
        'quantity' => 'integer',
        'payment_status' => OrderPaymentStatus::class,
        'delivery_status' => OrderDeliveryStatus::class,
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function paymentHistories(): MorphMany
    {
        return $this->morphMany(PaymentHistory::class, 'paymentable');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    public function orderBy(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'order_by_type', 'order_by_id');
    }

    public function getTotalAmount(): float
    {
        return $this->total;
    }

    public function getCurrency(): string
    {
        return 'USD';
    }

    public function getPaymentDescription(): string
    {
        return __('Payment for order #:code', ['code' => $this->code]);
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
