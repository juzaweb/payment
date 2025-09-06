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
    public function __construct(protected string $driver, protected array $config, protected bool $hasSandbox = true)
    {
    }

    public function makeDriver(array $config): PaymentGatewayInterface
    {
        return app()->make($this->driver, ['config' => $config]);
    }

    public function hasSandbox(): bool
    {
        return $this->hasSandbox;
    }

    public function setHasSandbox(bool $hasSandbox): self
    {
        $this->hasSandbox = $hasSandbox;

        return $this;
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
