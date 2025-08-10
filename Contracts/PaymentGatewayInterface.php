<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Contracts;

use Illuminate\Http\Request;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;

interface PaymentGatewayInterface
{
    public function purchase(array $params): PurchaseResult;

    public function complete(array $params): CompleteResult;

    public function handleWebhook(Request $request): ?CompleteResult;

    public function isReturnInEmbed(): bool;
}
