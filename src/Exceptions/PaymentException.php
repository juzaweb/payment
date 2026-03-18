<?php

/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @author     The Anh Dang
 *
 * @link       https://cms.juzaweb.com
 *
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Exceptions;

class PaymentException extends \Exception
{
    public static function make(string $message): self
    {
        return new static($message);
    }
}
