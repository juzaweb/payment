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

    public function create(User $user, string $module, string $method, array $params): PurchaseResult
    {
        $handler = $this->getModule($module);
        $order = $handler->createOrder($params);

        $paymentMethod = PaymentMethod::where('driver', $method)
            ->where('active', true)
            ->first();

        throw_if($paymentMethod == null, PaymentException::make(__('Payment method not found!')));

        $paymentHistory = new PaymentHistory(
            [
                'payment_method' => $method,
                'module' => $module,
                'status' => PaymentHistoryStatus::PROCESSING,
            ]
        );

        $paymentHistory->payer()->associate($user);
        $paymentHistory->save();

        $config = $paymentMethod->getConfig();
        $config['returnUrl'] = route('payment.return', [$module, $paymentHistory->id]);
        $config['cancelUrl'] = route('payment.cancel', [$module, $paymentHistory->id]);

        $purchase = $this->driver($paymentMethod->driver, $config)->purchase(
            [
                'amount' => $order->getTotalAmount(),
                'currency' => $order->getCurrency(),
                'description' => $order->getPaymentDescription(),
            ]
        );

        $paymentHistory->fill(['payment_id' => $purchase->getTransactionId()]);
        $paymentHistory->paymentable()->associate($order);
        $paymentHistory->save();

        if ($purchase->isSuccessful()) {
            $handler->success($order, $params);

            $paymentHistory->update(['status' => PaymentHistoryStatus::SUCCESS]);

            event(new PaymentSuccess($paymentHistory->paymentable, $params));
        }

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
        $handler = $this->getModule($module);

        $handler->cancel($paymentHistory->paymentable, $params);

        event(new PaymentCancel($paymentHistory->paymentable, $params));

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
