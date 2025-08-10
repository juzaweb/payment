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

abstract class PaymentGateway
{
    protected bool $returnInEmbed = false;

    public function isReturnInEmbed(): bool
    {
        return $this->returnInEmbed;
    }
}
