<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Facades;

use Illuminate\Support\Facades\Facade;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Models\PaymentMethod;
use Juzaweb\Modules\Payment\Services\PurchaseResult;

/**
 * @method static PaymentGatewayInterface driver(string $name)
 * @method static void registerDriver(string $name, callable $resolver)
 * @method static void registerModule(string $name, array $config = [])
 * @method static PurchaseResult create(string $module, PaymentMethod $method, array $params)
 */
class PaymentManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Juzaweb\Modules\Payment\Contracts\PaymentManager::class;
    }
}
