<?php

namespace Juzaweb\Modules\Payment\Models;

use Juzaweb\Core\Models\Model;
use Juzaweb\Core\Traits\HasAPI;
use Juzaweb\Core\Traits\Translatable;

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

    public $translatedAttributes = [
        'name',
        'description',
    ];

    public function getConfig(?string $key = null, $default = null): null|array|string
    {
        if (is_null($key)) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }
}
