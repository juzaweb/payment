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

use Juzaweb\Modules\Payment\Contracts\ModuleHandlerInterface;
use Juzaweb\Modules\Payment\Contracts\Paymentable;
use Juzaweb\Modules\Payment\Models\TestOrder;

class PaymentTestHandler implements ModuleHandlerInterface
{
    public function createOrder(array $params): Paymentable
    {
        // Implement the logic to handle the purchase request
        // For example, you might interact with a payment gateway here

        // Return a PurchaseResult instance with the result of the purchase
        return TestOrder::create(
            [
                'amount' => $params['amount'],
            ]
        );
    }

    public function success(Paymentable $paymentable, array $params): void
    {
        // Implement the logic to handle a successful payment
        \Log::info('Payment successful', ['params' => $params]);

        $paymentable->update(
            [
                'status' => 'success',
            ]
        );
    }

    public function fail(Paymentable $paymentable, array $params): void
    {
        // Implement the logic to handle a failed payment
        // This might involve logging the failure, notifying the user, etc.

        // Example: Log the failure
        \Log::error('Payment failed', ['params' => $params]);

        $paymentable->update(
            [
                'status' => 'failed',
            ]
        );
    }

    public function cancel(Paymentable $paymentable, array $params): void
    {
        // Implement the logic to handle a canceled payment
        // This might involve logging the cancellation, notifying the user, etc.

        // Example: Log the cancellation
        \Log::info('Payment canceled', ['params' => $params]);

        $paymentable->update(
            [
                'status' => 'canceled',
            ]
        );
    }

    public function getReturnUrl(): string
    {
        return admin_url('payment-methods');
    }
}
