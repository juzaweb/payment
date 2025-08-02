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
use Juzaweb\Modules\Payment\Models\PaymentMethod;

class PaymentManager implements Contracts\PaymentManager
{
    protected array $drivers = [];

    protected array $modules = [];

    public function create(string $module, PaymentMethod $method)
    {
        if (!isset($this->modules[$module])) {
            throw new InvalidArgumentException("Payment module [$module] not registered.");
        }

        $handler = $this->modules[$module];
        if (!$handler instanceof Contracts\ModuleHandlerInterface) {
            throw new InvalidArgumentException("Payment module [$module] does not implement ModuleHandlerInterface.");
        }

        $handler->purchase($method);
        $gateway = $this->driver($method->driver, $method->getConfig());

        return $gateway->purchase($params);
    }
    
    public function registerDriver(string $name, callable $resolver): void
    {
        if (isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Payment driver [$name] already registered.");
        }

        $this->drivers[$name] = $resolver;
    }

    public function registerModule(string $name, Contracts\ModuleHandlerInterface $handler): void
    {
        if (isset($this->modules[$name])) {
            throw new InvalidArgumentException("Payment module [$name] already registered.");
        }

        $this->modules[$name] = $handler;
    }

    public function driver(string $name, array $config): PaymentGatewayInterface
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Payment driver [$name] not registered.");
        }

        return app($this->drivers[$name], ['config' => $this->drivers[$name]]);
    }
}
