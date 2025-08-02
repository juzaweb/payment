<?php

namespace Juzaweb\Modules\Payment\Models;

use Juzaweb\Core\Models\Model;
use Juzaweb\Core\Traits\HasAPI;

class PaymentMethod extends Model
{
    use HasAPI;

    protected $table = 'payment_methods';

    protected $fillable = [
        'driver',
        'name',
        'description',
        'config',
        'active',
    ];

    protected $casts = [
        'config' => 'array',
        'active' => 'boolean',
    ];

    public function getConfig(?string $key = null, $default = null): null|array|string
    {
        if (is_null($key)) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }
}
