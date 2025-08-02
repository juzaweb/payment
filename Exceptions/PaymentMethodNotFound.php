<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Exceptions;

class PaymentMethodNotFound extends PaymentException
{
    public static function make(string $method): static
    {
        return new static(
            sprintf('Payment method "%s" not found', $method)
        );
    }
}
