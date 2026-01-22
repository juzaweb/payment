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
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;

class Custom extends PaymentGateway implements PaymentGatewayInterface
{
    public function __construct(protected array $config) {}

    public function purchase(array $params): PurchaseResult
    {
        // Custom payment method doesn't process payment immediately
        // Just return success to allow order creation with PENDING status
        return PurchaseResult::make(
            null, // No transaction reference
            null, // No redirect URL
            []    // No data
        )->setSuccessful(true);
    }

    public function complete(array $params): CompleteResult
    {
        // Custom payment doesn't use external gateways, so complete is not needed
        return CompleteResult::make(
            null,
            true,
            []
        );
    }

    public function handleWebhook(Request $request): ?CompleteResult
    {
        // No webhook handling for custom payment
        return null;
    }
}
