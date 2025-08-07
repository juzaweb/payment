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
use Juzaweb\Modules\Payment\Facades\PaymentManager;

class PaymentMethodRequest extends FormRequest
{
    public function rules(): array
    {
        $locale = $this->getFormLanguage();

        return [
			'driver' => [
                Rule::requiredIf(!$this->route('id')),
                Rule::in(array_keys(PaymentManager::drivers()))
            ],
			"{$locale}.name" => ['required', 'string', 'max:200'],
			"{$locale}.description" => ['nullable', 'string', 'max:500'],
            'locale' => ['required', 'string', 'max:10', 'exists:languages,code'],
			'config' => ['required', 'array'],
			'active' => ['required', 'boolean'],
		];
    }
}
