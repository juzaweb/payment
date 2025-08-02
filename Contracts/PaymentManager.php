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

interface PaymentManager
{
    public function registerDriver(string $name, callable $resolver): void;

    public function registerModule(string $name, array $config): void;
}
