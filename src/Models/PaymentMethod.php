<?php

namespace Juzaweb\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Juzaweb\Modules\Core\Models\Model;
use Juzaweb\Modules\Core\Traits\HasAPI;
use Juzaweb\Modules\Core\Traits\Translatable;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Facades\PaymentManager;

class PaymentMethod extends Model
{
    use HasAPI, HasUuids, Translatable;

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
        'locale',
    ];

    protected $hidden = [
        'config',
    ];

    public function scopeWhereActive(Builder $builder, bool $active = true): Builder
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
