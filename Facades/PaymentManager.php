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
use Juzaweb\Core\Models\User;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Models\PaymentHistory;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;

/**
 * @method static PaymentGatewayInterface driver(string $name, array $config = [])
 * @method static void registerDriver(string $name, string $resolver)
 * @method static void registerModule(string $name, array $config = [])
 * @method static PurchaseResult create(User $user, string $module, string $method, array $params)
 * @method static CompleteResult complete(string $module, PaymentHistory $paymentHistory, array $params)
 * @method static bool cancel(string $module, PaymentHistory $paymentHistory, array $params)
 * @method static array drivers()
 * @method static array config(string $driver)
 * @method static string renderConfig(string $driver, array $config = [])
 * @method static array modules()
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
