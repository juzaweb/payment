<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 */

namespace Juzaweb\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Juzaweb\Modules\Payment\Enums\OrderDeliveryStatus;
use Juzaweb\Modules\Payment\Enums\OrderPaymentStatus;

class OrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
			'payment_status' => ['required', Rule::enum(OrderPaymentStatus::class)],
			'delivery_status' => ['required', Rule::enum(OrderDeliveryStatus::class)]
		];
    }
}
