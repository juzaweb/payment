<?php

/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Juzaweb\Modules\Core\Http\Controllers\ThemeController;
use Juzaweb\Modules\Payment\Enums\OrderPaymentStatus;
use Juzaweb\Modules\Payment\Facades\PaymentManager;
use Juzaweb\Modules\Payment\Models\Cart;
use Juzaweb\Modules\Payment\Models\Order;
use Juzaweb\Modules\Payment\Models\PaymentMethod;

class CheckoutController extends ThemeController
{
    public function index(Request $request, string $module, string $cartId)
    {
        abort_if(PaymentManager::hasModule($module) === false, 404, __('Payment module not found!'));

        $cookieCartId = $request->cookie('cart_id');

        if ($cartId != $cookieCartId) {
            abort(404, __('Cart not found'));
        }

        $cart = Cart::where('id', $cartId)->first();

        abort_if($cart === null, 404, __('Cart not found'));

        $cart->load([
            'items.orderable' => function ($q) {
                $q->with(['media'])->withTranslation();
            }
        ]);
        $user = $request->user();

        $paymentMethods = PaymentMethod::withTranslation()->whereActive()->get();

        return view(
            'payment::checkout.index',
            compact('cart', 'user', 'paymentMethods', 'module')
        );
    }

    public function orderCheckout(Request $request, string $orderId)
    {
        $order = Order::findOrFail($orderId);

        // Only allow checkout for pending orders
        abort_if(
            $order->payment_status !== OrderPaymentStatus::PENDING,
            403,
            __('Order already paid or cancelled')
        );

        $order->load([
            'items.orderable' => function ($q) {
                $q->with(['media'])->withTranslation();
            }
        ]);

        $user = $order->creator;
        $module = $order->module;
        $paymentMethods = PaymentMethod::withTranslation()->whereActive()->get();

        return view(
            'payment::checkout.order',
            compact('user', 'paymentMethods', 'module', 'order')
        );
    }

    public function thankyou(Request $request, string $orderId)
    {
        $order = Order::findOrFail($orderId);

        $order->load([
            'items.orderable' => fn($q) => $q->withTranslation(),
        ]);

        return view(
            'payment::checkout.thankyou',
            compact('order')
        );
    }
}
