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

interface ModuleHandlerInterface
{
    public function createOrder(array $params): Paymentable;

    public function fail(Paymentable $paymentable, array $params): void;

    public function success(Paymentable $paymentable, array $params): void;

    public function cancel(Paymentable $paymentable, array $params): void;

    public function getReturnUrl(): string;
}
