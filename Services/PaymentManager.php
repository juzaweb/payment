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
use Juzaweb\Core\Models\User;
use Juzaweb\Modules\Payment\Contracts;
use Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus;
use Juzaweb\Modules\Payment\Events\PaymentFail;
use Juzaweb\Modules\Payment\Events\PaymentSuccess;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Models\PaymentHistory;
use Juzaweb\Modules\Payment\Models\PaymentMethod;

class PaymentManager implements Contracts\PaymentManager
{
    protected array $drivers = [];

    protected array $modules = [];

    public function create(User $user, string $module, string $method, array $params): PurchaseResult
    {
        $handler = $this->getModule($module);
        $order = $handler->createOrder($params);

        $paymentMethod = PaymentMethod::where('driver', $method)
            ->where('active', true)
            ->first();

        throw_if($paymentMethod == null, PaymentException::make(__('Payment method not found!')));

        $purchase = $this->driver($paymentMethod->driver, $paymentMethod->getConfig())->purchase(
            [
                'amount' => $order->getTotalAmount(),
                'currency' => $order->getCurrency(),
                'description' => $order->getPaymentDescription(),
            ]
        );

        $paymentHistory = new PaymentHistory(
            [
                'payment_method' => $method,
                'module' => $module,
                'status' => PaymentHistoryStatus::PROCESSING,
                'payment_id' => $purchase->getTransactionId(),
            ]
        );

        $paymentHistory->payer()->associate($user);

        if ($purchase->isSuccessful()) {
            $paymentHistory->fill(
                [
                    'status' => PaymentHistoryStatus::SUCCESS,
                ]
            );

            $paymentHistory->paymentable()->associate($order);
        }

        $paymentHistory->save();

        return $purchase;
    }

    public function complete(string $module, PaymentHistory $paymentHistory, array $params): CompleteResult
    {
        $gateway = $this->driver($paymentHistory->paymentMethod->driver, $paymentHistory->paymentMethod->getConfig());

        $handler = $this->getModule($module);

        $params['transactionReference'] = $paymentHistory->payment_id;

        unset($params['token']);

        $response = $gateway->complete($params);

        if ($response->isSuccessful()) {
            $handler->success($paymentHistory->paymentable);

            event(new PaymentSuccess($paymentHistory->paymentable));
        } else {
            $handler->fail($paymentHistory->paymentable);

            event(new PaymentFail($paymentHistory->paymentable));
        }

        return $response;
    }

    public function cancel(string $module, PaymentHistory $paymentHistory, array $params): true
    {
        $handler = $this->getModule($module);

        $handler->cancel($paymentHistory->paymentable);

        event(new PaymentFail($paymentHistory->paymentable));

        return true;
    }

    public function registerDriver(string $name, string $resolver): void
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

    public function getModule(string $module): Contracts\ModuleHandlerInterface
    {
        if (!isset($this->modules[$module])) {
            throw new InvalidArgumentException("Payment module [$module] not registered.");
        }

        return $this->modules[$module];
    }
}
