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

use Juzaweb\Modules\Payment\Services\PurchaseResult;

interface PaymentGatewayInterface
{
    public function purchase(array $params): PurchaseResult;

    public function return(): mixed;

    public function handleWebhook(array $data): mixed;
}
