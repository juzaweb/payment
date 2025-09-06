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
use Juzaweb\Modules\Payment\Contracts\ModuleHandlerInterface;
use Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus;
use Juzaweb\Modules\Payment\Events\PaymentCancel;
use Juzaweb\Modules\Payment\Events\PaymentFail;
use Juzaweb\Modules\Payment\Events\PaymentSuccess;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Models\PaymentHistory;
use Juzaweb\Modules\Payment\Models\PaymentMethod;

class PaymentManager implements Contracts\PaymentManager
{
    protected array $drivers = [];

    protected array $modules = [];

    public function create(User $user, string $module, string|PaymentMethod $paymentMethod, array $params): PurchaseResult
    {
        $handler = $this->module($module);
        $order = $handler->createOrder($params);

        if (!$paymentMethod instanceof PaymentMethod) {
            $paymentMethod = PaymentMethod::where('driver', $paymentMethod)
                ->where('active', true)
                ->first();
        }

        throw_if($paymentMethod == null, PaymentException::make(__('Payment method not found!')));

        $paymentHistory = new PaymentHistory(
            [
                'payment_method' => $paymentMethod->driver,
                'module' => $module,
                'status' => PaymentHistoryStatus::PROCESSING,
            ]
        );

        $paymentHistory->payer()->associate($user);
        $paymentHistory->paymentable()->associate($order);
        $paymentHistory->save();

        $config = $paymentMethod->getConfig();
        $purchase = $this->driver($paymentMethod->driver, $config)->purchase(
            [
                ...$params,
                'code' => $order->getCode(),
                'amount' => $order->getTotalAmount(),
                'currency' => $order->getCurrency(),
                'description' => $order->getPaymentDescription(),
                'paymentHistoryId' => $paymentHistory->id,
                'returnUrl' => route('payment.return', [$module, $paymentHistory->id]),
                'cancelUrl' => route('payment.cancel', [$module, $paymentHistory->id]),
            ]
        );

        $paymentHistory->fill(['payment_id' => $purchase->getTransactionId()]);
        $paymentHistory->save();

        if ($purchase->isSuccessful()) {
            $handler->success($order, $params);

            $paymentHistory->update(
                [
                    'status' => PaymentHistoryStatus::SUCCESS,
                ]
            );

            event(new PaymentSuccess($paymentHistory->paymentable, $params));
        }

        $purchase->setPaymentHistory($paymentHistory);

        return $purchase;
    }

    public function complete(string $module, PaymentHistory $paymentHistory, array $params): CompleteResult
    {
        $gateway = $this->driver($paymentHistory->paymentMethod->driver, $paymentHistory->paymentMethod->getConfig());

        $handler = $this->module($module);

        $params['transactionReference'] = $paymentHistory->payment_id;
        $params['returnUrl'] = route('payment.return', [$module, $paymentHistory->id]);

        $response = $gateway->complete($params);

        if ($response->isSuccessful()) {
            $handler->success($paymentHistory->paymentable, $params);

            $paymentHistory->update(
                [
                    'status' => PaymentHistoryStatus::SUCCESS,
                ]
            );

            event(new PaymentSuccess($paymentHistory->paymentable, $params));
        } else {
            $handler->fail($paymentHistory->paymentable, $params);

            $paymentHistory->update(
                [
                    'status' => PaymentHistoryStatus::FAILED,
                ]
            );

            event(new PaymentFail($paymentHistory->paymentable, $params));
        }

        return $response;
    }

    public function cancel(string $module, PaymentHistory $paymentHistory, array $params): true
    {
        $handler = $this->module($module);

        $handler->cancel($paymentHistory->paymentable, $params);

        event(new PaymentCancel($paymentHistory->paymentable, $params));

        return true;
    }

    public function registerDriver(string $name, callable $resolver): void
    {
        if (isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Payment driver [$name] already registered.");
        }

        $this->drivers[$name] = $resolver;
    }

    public function registerModule(string $name, ModuleHandlerInterface $handler): void
    {
        if (isset($this->modules[$name])) {
            throw new InvalidArgumentException("Payment module [$name] already registered.");
        }

        $this->modules[$name] = $handler;
    }

    public function drivers(): array
    {
        return collect(array_keys($this->drivers))
            ->mapWithKeys(fn ($driver) => [
                $driver => title_from_key($driver),
            ])
            ->all();
    }

    public function modules(): array
    {
        return array_keys($this->modules);
    }

    public function driver(string $name, array $config): Contracts\PaymentGatewayInterface
    {
        return $this->driverAdapter($name)->makeDriver($config);
    }

    public function config(string $driver): array
    {
        if (!isset($this->drivers[$driver])) {
            throw new PaymentException("Payment driver [$driver] not registered.");
        }

        return $this->drivers[$driver]()->getConfig();
    }

    public function driverAdapter(string $name): PaymentDriverAdapter
    {
        if (!isset($this->drivers[$name])) {
            throw new PaymentException("Payment driver [$name] not registered.");
        }

        return $this->drivers[$name]();
    }

    public function renderConfig(string $driver, array $config = []): string
    {
        $fields = $this->config($driver);
        $hasSandbox = $this->driverAdapter($driver)->hasSandbox();

        if (empty($fields)) {
            throw new PaymentException("Payment driver [$driver] has no configuration.");
        }

        return view(
            'payment::method.components.config',
            ['fields' => $fields, 'config' => $config, 'hasSandbox' => $hasSandbox]
        )->render();
    }

    public function module(string $module): Contracts\ModuleHandlerInterface
    {
        if (!isset($this->modules[$module])) {
            throw new PaymentException("Payment module [$module] not registered.");
        }

        return $this->modules[$module];
    }
}
