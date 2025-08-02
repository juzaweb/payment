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

use InvalidArgumentException;
use Juzaweb\Modules\Payment\Contracts;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;

class PaymentManager implements Contracts\PaymentManager
{
    protected array $drivers = [];

    protected array $modules = [];

    public function registerDriver(string $name, callable $resolver): void
    {
        $this->drivers[$name] = $resolver;
    }

    public function registerModule(string $name, array $config = []): void
    {
        if (isset($this->modules[$name])) {
            throw new InvalidArgumentException("Payment module [$name] already registered.");
        }

        $this->modules[$name] = $config;
    }

    public function driver(string $name): PaymentGatewayInterface
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Payment driver [$name] not registered.");
        }

        return call_user_func($this->drivers[$name]);
    }
}
