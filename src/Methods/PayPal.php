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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;
use Omnipay\Common\GatewayInterface;
use Omnipay\Omnipay;
use Omnipay\PayPal\Message\RestTokenRequest;

class PayPal extends PaymentGateway implements PaymentGatewayInterface
{
    protected string $driver = 'PayPal_Rest';

    public function __construct(protected array $config) {}

    public function purchase(array $params): PurchaseResult
    {
        $response = $this->createGateway()->purchase(
            [
                'amount' => $params['amount'],
                'currency' => $params['currency'],
                'returnUrl' => $params['returnUrl'],
                'cancelUrl' => $params['cancelUrl'],
                'description' => $params['description'],
            ]
        )->send();

        if (! in_array($response->getCode(), [200, 201])) {
            Log::error('PayPal Purchase Error: ', [
                'code' => $response->getCode(),
                'message' => $response->getMessage(),
                'data' => $response->getData(),
                'params' => $params,
            ]);
            throw new PaymentException(
                __('Payment gateway error: :message', ['message' => $response->getMessage()])
            );
        }

        return PurchaseResult::make(
            $response->getTransactionReference(),
            $response->getRedirectUrl(),
            $response->getData()
        )->setSuccessful($response->isSuccessful() && !$response->isRedirect());
    }

    public function complete(array $params): CompleteResult
    {
        unset($params['token']);
        $response = $this->createGateway()->completePurchase($params)->send();

        return CompleteResult::make(
            $response->getTransactionReference(),
            $response->isSuccessful(),
            $response->getData()
        );
    }

    public function handleWebhook(Request $request): ?CompleteResult
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || empty($payload['event_type'])) {
            return null;
        }

        if (!$this->verifyWebhookSignature($request, $payload)) {
            Log::error('PayPal Signature Verification Failed');
            return null;
        }

        if (in_array($payload['event_type'], ['PAYMENT.SALE.COMPLETED', 'PAYMENT.CAPTURE.COMPLETED'])) {
            $resource = $payload['resource'];
            $transactionId = $resource['parent_payment'] ?? $resource['id'];

            return CompleteResult::make(
                $transactionId,
                true,
                $payload
            );
        }

        if ($payload['event_type'] === 'PAYMENT.SALE.DENIED') {
            $resource = $payload['resource'];
            $transactionId = $resource['parent_payment'] ?? $resource['id'];

            return CompleteResult::make(
                $transactionId,
                false,
                $payload
            );
        }

        return null;
    }

    protected function verifyWebhookSignature(Request $request, array $payload): bool
    {
        $isSandbox = (bool) ($this->config['sandbox'] ?? false);
        $webhookId = $isSandbox
            ? ($this->config['sandbox_webhook_id'] ?? '')
            : ($this->config['live_webhook_id'] ?? '');

        if (empty($webhookId)) {
            return false;
        }

        try {
            $gateway = $this->createGateway();
            $tokenRequest = new RestTokenRequest($gateway->getHttpClient(), $gateway->getHttpRequest());
            $tokenRequest->initialize($gateway->getParameters());
            $response = $tokenRequest->send();

            if (!$response->isSuccessful()) {
                Log::error('PayPal Token Error: ' . $response->getMessage());
                return false;
            }

            $accessToken = $response->getData()['access_token'];

            $verifyPayload = [
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                'cert_url' => $request->header('PAYPAL-CERT-URL'),
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id' => $webhookId,
                'webhook_event' => $payload
            ];

            $apiUrl = $isSandbox
                ? 'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature'
                : 'https://api-m.paypal.com/v1/notifications/verify-webhook-signature';

            $verifyResponse = Http::withToken($accessToken)
                ->post($apiUrl, $verifyPayload);

            if ($verifyResponse->successful()) {
                $data = $verifyResponse->json();
                return isset($data['verification_status']) && $data['verification_status'] === 'SUCCESS';
            }

            Log::error('PayPal Verify Error: ' . $verifyResponse->body());
        } catch (\Exception $e) {
            Log::error('PayPal Webhook Exception: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    protected function createGateway(): GatewayInterface
    {
        $gateway = Omnipay::create($this->driver);

        $isSandbox = (bool) ($this->config['sandbox'] ?? false);
        if ($isSandbox) {
            $config = [
                'clientId' => $this->config['sandbox_client_id'] ?? '',
                'secret' => $this->config['sandbox_secret'] ?? '',
                'testMode' => true,
            ];
        } else {
            $config = [
                'clientId' => $this->config['live_client_id'] ?? '',
                'secret' => $this->config['live_secret'] ?? '',
                'testMode' => false,
            ];
        }

        $gateway->initialize($config);

        return $gateway;
    }
}
