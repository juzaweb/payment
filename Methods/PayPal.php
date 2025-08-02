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

use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;
use Omnipay\Common\GatewayInterface;
use Omnipay\Omnipay;

class PayPal implements PaymentGatewayInterface
{
    protected string $driver = 'PayPal_Express';

    public function __construct(protected array $config)
    {
    }

    public function purchase(array $params): PurchaseResult
    {
        $response = $this->createGateway()->purchase($params)->send();

        return PurchaseResult::make(
            $response->getTransactionReference(),
            $response->getRedirectUrl(),
            $response->getData()
        )->setSuccessful($response->isSuccessful());
    }

    public function complete(array $params): CompleteResult
    {
        $response = $this->createGateway()->completePurchase($params)->send();

        return CompleteResult::make(
            $response->getTransactionReference(),
            $response->isSuccessful(),
            $response->getData()
        );
    }

    public function handleWebhook(array $data): void
    {
        // TODO: Implement handleWebhook() method.
    }

    protected function createGateway(): GatewayInterface
    {
        $gateway = Omnipay::create($this->driver);
        $gateway->initialize($this->config);
        return $gateway;
    }
}
