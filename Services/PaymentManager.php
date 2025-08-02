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
use Juzaweb\Modules\Payment\Models\PaymentMethod;

class PaymentManager implements Contracts\PaymentManager
{
    protected array $drivers = [];

    protected array $modules = [];

    public function create(string $module, PaymentMethod $method, array $params): PurchaseResult
    {
        if (!isset($this->modules[$module])) {
            throw new InvalidArgumentException("Payment module [$module] not registered.");
        }

        $handler = $this->modules[$module];
        if (!$handler instanceof Contracts\ModuleHandlerInterface) {
            throw new InvalidArgumentException("Payment module [$module] does not implement ModuleHandlerInterface.");
        }

        $order = $handler->createOrder($params);

        return $this->driver($method->driver, $method->getConfig())->purchase(
            [
                'amount' => $order->getTotalAmount(),
                'currency' => $order->getCurrency(),
                // 'description'   => 'This is a test purchase transaction.',
            ]
        );
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

    public function driver(string $name, array $config): Contracts\PaymentGatewayInterface
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Payment driver [$name] not registered.");
        }

        $config['returnUrl'] = $config['returnUrl'] ?? url("/payment/{$name}/complete");
        $config['cancelUrl'] = $config['cancelUrl'] ?? url("/payment/{$name}/cancel");

        return app($this->drivers[$name], ['config' => $config]);
    }
}
