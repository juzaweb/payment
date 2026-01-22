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
use Juzaweb\Modules\Payment\Rules\OrderableExists;

class CartAddRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('orderable_type')) {
            $this->merge([
                'orderable_type' => decrypt($this->input('orderable_type')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'orderable_type' => 'required|string',
            'orderable_id' => [
                'required',
                'uuid',
                new OrderableExists($this->input('orderable_type', '')),
            ],
            'quantity' => 'required|integer|min:1',
        ];
    }
}
