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

    }

    public function return(): mixed
    {
        // TODO: Implement redirect() method.
    }

    public function handleWebhook(array $data): mixed
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
