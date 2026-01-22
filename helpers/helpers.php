<?php

use Illuminate\Support\Facades\Cookie;
use Juzaweb\Modules\Payment\Models\Cart;

if (!function_exists('find_or_create_cart')) {
    /**
     * Find or create a cart for the current actor.
     *
     * @param string|null $cartId The cart ID from cookie.
     * @return Cart
     */
    function find_or_create_cart(?string $cartId = null): Cart
    {
        $actor = current_actor();

        if ($cartId) {
            $cart = Cart::where('id', $cartId)
                ->where('created_type', get_class($actor))
                ->where('created_by', $actor->id)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        return Cart::create([
            'created_by' => $actor->id,
            'created_type' => get_class($actor),
        ]);
    }
}

if (!function_exists('get_cart')) {
    /**
     * Get the current cart from cookie.
     *
     * @return Cart|null
     */
    function get_cart(): ?Cart
    {
        $cartId = Cookie::get('cart_id');

        if (!$cartId) {
            return null;
        }

        return Cart::whereFrontend()->find($cartId);
    }
}

/**
 * Format price with currency symbol
 *
 * @param float|null $price
 * @param string $currency Currency code (default: USD)
 * @return string
 */
function format_price(?float $price, string $currency = 'USD'): string
{
    if ($price === null) {
        return '';
    }

    // Currency symbols mapping
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'VND' => '₫',
    ];

    $symbol = $symbols[$currency] ?? $currency . ' ';

    // Format based on currency
    if ($currency === 'VND') {
        return number_format($price, 0, '.', ',') . $symbol;
    }

    // Default format for USD, EUR, etc.
    return $symbol . number_format($price, 2, '.', ',');
}
