<?php

namespace Juzaweb\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Juzaweb\Core\Models\Model;
use Juzaweb\Core\Traits\HasAPI;
use Juzaweb\Modules\Payment\Contracts\Paymentable;
use Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus;

class PaymentHistory extends Model
{
    use HasUuids, HasAPI;

    protected $table = 'payment_histories';

    protected $fillable = [
        'payment_method',
        'status',
        'data',
        'module',
        'payer_type',
        'payer_id',
        'payment_id',
        'paymentable_type',
        'paymentable_id',
    ];

    protected $casts = [
        'data' => 'array',
        'status' => PaymentHistoryStatus::class,
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'driver');
    }

    public function payer(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'payer_type', 'payer_id');
    }

    /**
     * Get the paymentable model (e.g., Order, Subscription, etc.)
     *
     * @return MorphTo|Paymentable
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'paymentable_type', 'paymentable_id');
    }

    public function getData(?string $key = null, $default = null): null|array|string
    {
        if (is_null($key)) {
            return $this->data;
        }

        return data_get($this->data, $key, $default);
    }
}
