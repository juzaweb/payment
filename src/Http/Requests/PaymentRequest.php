<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Juzaweb\Modules\Core\Rules\ModelExists;
use Juzaweb\Modules\Payment\Facades\PaymentManager;
use Juzaweb\Modules\Payment\Models\Order;

class PaymentRequest extends FormRequest
{
    public function rules(): array
    {
        $methods = array_keys(PaymentManager::drivers());

        return [
            'method' => ['required', 'string', Rule::in($methods)],
            'order_id' => ['required', 'string', new ModelExists(Order::class, 'id')],
        ];
    }
}
