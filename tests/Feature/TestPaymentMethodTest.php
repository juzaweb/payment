<?php

namespace Juzaweb\Modules\Payment\Tests\Feature;

use Juzaweb\Modules\Payment\Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class TestPaymentMethodTest extends TestCase
{
    use WithoutMiddleware;

    public function test_test_payment_checkout_page_loads()
    {
        $response = $this->get(route('payment.test.checkout', [
            'amount' => 100,
            'currency' => 'USD',
            'return_url' => 'http://example.com/return',
            'cancel_url' => 'http://example.com/cancel',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Test Payment');
        $response->assertSee('100 USD');
        $response->assertSee('http://example.com/return');
        $response->assertSee('http://example.com/cancel');
    }
}
