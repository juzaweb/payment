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

interface Paymentable
{
    public function getTotalAmount(): float;

    public function getCurrency(): string;

    public function getDescription(): string;
}
