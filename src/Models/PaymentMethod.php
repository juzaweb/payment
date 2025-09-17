<?php

namespace Juzaweb\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Builder;
use Juzaweb\Core\Models\Model;
use Juzaweb\Core\Traits\HasAPI;
use Juzaweb\Core\Traits\Translatable;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Facades\PaymentManager;

class PaymentMethod extends Model
{
    use HasAPI, Translatable;

    protected $table = 'payment_methods';

    protected $fillable = [
        'driver',
        'config',
        'active',
    ];

    protected $casts = [
        'config' => 'array',
        'active' => 'boolean',
    ];

    protected $appends = [
        'sandbox',
    ];

    public $translatedAttributes = [
        'name',
        'description',
    ];

    protected $hidden = [
        'config',
    ];

    public function scopeWhereActive(Builder $builder, $active = true): Builder
    {
        return $builder->where('active', $active);
    }

    public function getSandboxAttribute(): bool
    {
        return (bool) $this->getConfig('sandbox', false);
    }

    public function paymentDriver(): PaymentGatewayInterface
    {
        return PaymentManager::driver(
            $this->driver,
            $this->getConfig()
        );
    }

    public function getConfig(?string $key = null, $default = null): null|array|string
    {
        if (is_null($key)) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }
}
