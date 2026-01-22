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
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Juzaweb\Modules\Core\Http\Controllers\ThemeController;
use Juzaweb\Modules\Payment\Http\Requests\CartAddRequest;
use Juzaweb\Modules\Payment\Models\Cart;

class CartController extends ThemeController
{
    public function add(CartAddRequest $request)
    {
        $cart = DB::transaction(
            function () use ($request) {
                $actor = current_actor();

                if ($cartId = $request->cookie('cart_id')) {
                    $cart = Cart::find($cartId);
                }

                if (!isset($cart)) {
                    $cart = Cart::create([
                        'created_by' => $actor->id,
                        'created_type' => get_class($actor),
                    ]);
                }

                $item = $cart->items()->updateOrCreate(
                    [
                        'orderable_type' => $request->input('orderable_type'),
                        'orderable_id' => $request->input('orderable_id'),
                    ],
                    $request->only(['quantity'])
                );

                return ['cart' => $cart, 'item' => $item];
            }
        );

        Cookie::queue('cart_id', $cart['cart']->id, 60 * 24 * 30); // 30 days

        // Load relationships for the response
        $item = $cart['item']->load('orderable');
        $cartModel = $cart['cart']->load('items.orderable');

        $subtotal = $cartModel->items->sum(fn($item) => $item->orderable->price * $item->quantity);
        $itemTotal = $item->orderable->price * $item->quantity;

        return $this->success(
            [
                'message' => __('Product added to cart successfully'),
                'cart_id' => $cartModel->id,
                'cart_count' => $cartModel->items->count(),
                'cart_subtotal' => $subtotal,
                'cart_subtotal_formatted' => format_price($subtotal),
                'item' => [
                    'id' => $item->id,
                    'name' => $item->orderable->name,
                    'price' => $item->orderable->price,
                    'price_formatted' => format_price($itemTotal),
                    'quantity' => $item->quantity,
                    'thumbnail' => $item->orderable->thumbnail,
                    'slug' => $item->orderable->slug,
                ],
            ]
        );
    }

    public function remove(Request $request, string $itemId)
    {
        $cartId = $request->cookie('cart_id');

        $cart = Cart::find($cartId);

        if (!$cart) {
            return $this->error(__('Cart not found'));
        }

        $item = $cart->items()->where('id', $itemId)->first();

        if (!$item) {
            return $this->error(__('Item not found in cart'));
        }

        $item->delete();

        return $this->success(
            [
                'message' => __('Item removed from cart successfully'),
            ]
        );
    }
}
