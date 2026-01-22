<?php

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Juzaweb\Modules\Core\Http\Controllers\ThemeController;

class TestPaymentController extends ThemeController
{
    public function index(Request $request)
    {
        $amount = $request->get('amount');
        $currency = $request->get('currency');
        $returnUrl = $request->get('return_url');
        $cancelUrl = $request->get('cancel_url');

        if (!filter_var($returnUrl, FILTER_VALIDATE_URL) || !str_starts_with($returnUrl, 'http')) {
            abort(400, 'Invalid return_url');
        }

        if (!filter_var($cancelUrl, FILTER_VALIDATE_URL) || !str_starts_with($cancelUrl, 'http')) {
            abort(400, 'Invalid cancel_url');
        }

        return view(
            'payment::test.checkout',
            compact('amount', 'currency', 'returnUrl', 'cancelUrl')
        );
    }
}
