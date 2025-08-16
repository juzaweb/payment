<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Methods;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;
use Omnipay\Common\GatewayInterface;
use Omnipay\Omnipay;
use Stripe\Webhook;

class Stripe extends PaymentGateway implements PaymentGatewayInterface
{
    protected bool $returnInEmbed = true;

    public function __construct(protected array $config)
    {
    }

    public function purchase(array $params): PurchaseResult
    {
        $response = $this->createGateway()->purchase($params)->send();

        if (isset($response->getData()['error']) && $response->getData()['error']) {
            throw new PaymentException($response->getData()['error']['message']);
        }

        $data = $response->getData();

        $paymentIntentId = $data['id'] ?? null;

        if ($paymentIntentId && $data['status'] === 'requires_confirmation') {
            $confirmResponse = $this->createGateway()->confirm(
                [
                    'paymentIntentReference' => $paymentIntentId,
                    'returnUrl' => $params['returnUrl'],
                ]
            )->send();

            return PurchaseResult::make(
                $paymentIntentId,
                $confirmResponse->getRedirectUrl(),
                $confirmResponse->getData()
            )->setSuccessful($confirmResponse->isSuccessful() && !$confirmResponse->isRedirect());
        }

        return PurchaseResult::make(
            $response->getTransactionReference(),
            $response->getRedirectUrl(),
            $response->getData()
        )->setSuccessful($response->isSuccessful() && !$response->isRedirect());
    }

    public function complete(array $params): CompleteResult
    {
        $params['paymentIntentReference'] = $params['payment_intent'];
        $response = $this->createGateway()->completePurchase($params)->send();

        if (isset($response->getData()['error']) && $response->getData()['error']) {
            throw new PaymentException($response->getData()['error']['message']);
        }

        $data = $response->getData();

        $paymentIntentId = $data['id'];

        return CompleteResult::make(
            $paymentIntentId,
            $response->isSuccessful(),
            $response->getData()
        );
    }

    public function handleWebhook(Request $request): ?CompleteResult
    {
        $payload = @file_get_contents('php://input');
        $sigHeader = $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, 'whsec_TbTC0wsspTjhQWxPQLywVLTj2YugpCci'
            );

            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object;

                $transactionId = $paymentIntent->id;

                return CompleteResult::make(
                    $transactionId,
                    true
                );
            }

            if ($event->type === 'payment_intent.payment_failed') {
                $paymentIntent = $event->data->object;

                $transactionId = $paymentIntent->id;

                return CompleteResult::make(
                    $transactionId,
                    false
                );
            }
        } catch (\Exception $e) {
            report($e);
        }

        return null;
    }

    protected function createGateway(): GatewayInterface
    {
        $gateway = Omnipay::create('Stripe\PaymentIntents');
        $gateway->setApiKey($this->config['secret_key']);
        if (isset($this->config['sandbox']) && $this->config['sandbox']) {
            $gateway->setTestMode(true);
        }
        return $gateway;
    }
}
