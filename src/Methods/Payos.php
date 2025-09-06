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
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;
use Omnipay\Common\GatewayInterface;
use Omnipay\Omnipay;

class Payos extends PaymentGateway implements PaymentGatewayInterface
{
    protected bool $returnInEmbed = true;

    public function __construct(protected array $config)
    {
    }

    public function purchase(array $params): PurchaseResult
    {
        $response = $this->createGateway()->purchase(
            [
                'amount' => $params['amount'] * 26000,
                'orderCode' => $params['code'],
                ...Arr::except($params, ['amount', 'paymentHistoryId', 'quantity']),
            ]
        )->send();

        return PurchaseResult::make(
            $response->getTransactionReference(),
            $response->getRedirectUrl() . '/?embedded=true',
            $response->getData()
        );
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

    public function handleWebhook(Request $request): ?CompleteResult
    {
        $checksumkey = $this->config['checksumKey'];

        if (verifySignaturePayos($request->input('data'), $request->input('signature'), $checksumkey)) {
            return CompleteResult::make(
                $request->input('data.paymentLinkId'),
                $request->input('data.code') === '00',
                $request->input('data')
            );
        }

        return CompleteResult::make(
            $request->input('data.paymentLinkId'),
            false,
            $request->input('data')
        );
    }

    protected function createGateway(): GatewayInterface
    {
        $gateway = Omnipay::create('Payos');
        $gateway->initialize(Arr::except($this->config, ['sandbox', 'token']));
        if (isset($this->config['sandbox']) && $this->config['sandbox']) {
            $gateway->setTestMode(true);
        }
        return $gateway;
    }
}
