<?php

namespace Juzaweb\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Juzaweb\Modules\Core\Rules\AllExist;

class OrderActionsRequest extends FormRequest
{
    public function rules()
    {
        return [
            'action' => ['required'],
            'ids' => ['required', 'array', 'min:1', new AllExist('orders', 'id')],
        ];
    }
}
