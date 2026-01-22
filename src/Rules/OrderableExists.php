<?php

/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OrderableExists implements ValidationRule
{
    protected string $orderableType;

    public function __construct(string $orderableType)
    {
        $this->orderableType = $orderableType;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!class_exists($this->orderableType)) {
            $fail(trans('ecommerce::validation.orderable_type_invalid'));
            return;
        }

        if (!$this->orderableType::where('id', $value)->exists()) {
            $fail(trans('ecommerce::validation.orderable_not_found'));
        }
    }
}
