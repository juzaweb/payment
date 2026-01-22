<?php

namespace Juzaweb\Modules\Payment\Methods;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;

class Test extends PaymentGateway implements PaymentGatewayInterface
{
    public function __construct(protected array $config)
    {
    }

    public function purchase(array $params): PurchaseResult
    {
        $token = Str::random(32);

        $queryParams = http_build_query([
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'return_url' => $params['returnUrl'],
            'cancel_url' => $params['cancelUrl'],
        ]);

        return PurchaseResult::make(
            $token,
            route('payment.test.checkout') . '?' . $queryParams
        );
    }

    public function complete(array $params): CompleteResult
    {
        return CompleteResult::make(
            $params['transactionReference'] ?? Str::random(32),
            true,
            $params
        );
    }

    public function handleWebhook(Request $request): ?CompleteResult
    {
        return null;
    }
}
