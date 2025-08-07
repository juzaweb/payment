<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Services;

use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;

class PaymentDriverAdapter
{
    public function __construct(protected string $driver, protected array $config)
    {
    }

    public function makeDriver(array $config): PaymentGatewayInterface
    {
        return app()->make($this->driver, ['config' => $config]);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
