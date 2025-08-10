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

class Payos implements PaymentGatewayInterface
{
    public function __construct(protected array $config)
    {
    }

    public function purchase(array $params): PurchaseResult
    {
        $response = $this->createGateway()->purchase(
            [
                'amount' => $params['amount'] * 26000,
                'orderCode' => $params['code'] + random_int(1000, 9999),
                ...Arr::except($params, ['amount', 'paymentHistoryId', 'quantity']),
            ]
        )->send();

        return PurchaseResult::make(
            $response->getTransactionReference(),
            $response->getRedirectUrl(),
            $response->getData()
        )
            ->setEmbedUrl($response->getEmbedUrl());
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

        if ($this->verifySignature($request->input('data'), $request->input('signature'), $checksumkey)) {
            return CompleteResult::make(
                $request->input('data.id'),
                true,
                $request->input('data')
            );
        }

        return CompleteResult::make(
            $request->input('data.id'),
            false,
            $request->input('data')
        );
    }

    public function verifySignature($transaction, $transactionSignature, $checksumKey): bool
    {
        ksort($transaction);
        $transactionStrArr = [];
        foreach ($transaction as $key => $value) {
            if (is_null($value) || in_array($value, ["undefined", "null"])) {
                $value = "";
            }

            if (is_array($value)) {
                $valueSortedElementObj = array_map(function ($ele) {
                    ksort($ele);
                    return $ele;
                }, $value);
                $value = json_encode($valueSortedElementObj, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }
            $transactionStrArr[] = $key . "=" . $value;
        }

        $transactionStr = implode("&", $transactionStrArr);

        $signature = hash_hmac("sha256", $transactionStr, $checksumKey);

        return $signature == $transactionSignature;
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
