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
use Illuminate\Support\Arr;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;
use Omnipay\Common\GatewayInterface;
use Omnipay\Omnipay;

class PayPal extends PaymentGateway implements PaymentGatewayInterface
{
    protected string $driver = 'PayPal_Rest';

    public function __construct(protected array $config)
    {
    }

    public function purchase(array $params): PurchaseResult
    {
        $response = $this->createGateway()->purchase($params)->send();

        if (! in_array($response->getCode(), [200, 201])) {
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
        // TODO: Implement handleWebhook() method.
        return null;
    }

    protected function createGateway(): GatewayInterface
    {
        $gateway = Omnipay::create($this->driver);
        $gateway->initialize(Arr::except($this->config, ['sandbox', 'token']));
        if (isset($this->config['sandbox']) && $this->config['sandbox']) {
            $gateway->setTestMode(true);
        }
        return $gateway;
    }
}
