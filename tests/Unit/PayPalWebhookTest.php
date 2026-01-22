<?php

namespace Juzaweb\Modules\Payment\Tests\Unit;

use Illuminate\Http\Request;
use Juzaweb\Modules\Payment\Methods\PayPal;
use Juzaweb\Modules\Payment\Tests\TestCase;
use Mockery;

class PayPalWebhookTest extends TestCase
{
    public function test_handle_webhook_returns_success_for_completed_sale()
    {
        $config = [
            'sandbox' => true,
            'sandbox_client_id' => 'test_id',
            'sandbox_secret' => 'test_secret',
            'sandbox_webhook_id' => 'test_webhook_id',
        ];

        $paypal = Mockery::mock(PayPal::class, [$config])->makePartial();
        $paypal->shouldAllowMockingProtectedMethods();
        $paypal->shouldReceive('verifyWebhookSignature')->andReturn(true);

        $payload = [
            'id' => 'WH-Test',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => [
                'id' => 'SALE-123',
                'parent_payment' => 'PAY-456',
            ],
        ];

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        $result = $paypal->handleWebhook($request);

        $this->assertNotNull($result);
        $this->assertEquals('PAY-456', $result->getTransactionId());
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals($payload, $result->getData());
    }

    public function test_handle_webhook_returns_failure_for_denied_sale()
    {
        $config = [
            'sandbox' => true,
            'sandbox_webhook_id' => 'test_webhook_id',
        ];

        $paypal = Mockery::mock(PayPal::class, [$config])->makePartial();
        $paypal->shouldAllowMockingProtectedMethods();
        $paypal->shouldReceive('verifyWebhookSignature')->andReturn(true);

        $payload = [
            'id' => 'WH-Test-Denied',
            'event_type' => 'PAYMENT.SALE.DENIED',
            'resource' => [
                'id' => 'SALE-123',
                'parent_payment' => 'PAY-456',
            ],
        ];

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        $result = $paypal->handleWebhook($request);

        $this->assertNotNull($result);
        $this->assertEquals('PAY-456', $result->getTransactionId());
        $this->assertFalse($result->isSuccessful());
    }

    public function test_handle_webhook_returns_null_for_irrelevant_event()
    {
        $config = [
            'sandbox_webhook_id' => 'test_webhook_id',
        ];

        $paypal = Mockery::mock(PayPal::class, [$config])->makePartial();
        $paypal->shouldAllowMockingProtectedMethods();
        $paypal->shouldReceive('verifyWebhookSignature')->andReturn(true);

        $payload = [
            'id' => 'WH-Ignored',
            'event_type' => 'IRRELEVANT.EVENT',
            'resource' => [],
        ];

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        $result = $paypal->handleWebhook($request);

        $this->assertNull($result);
    }

    public function test_handle_webhook_returns_null_if_verification_fails()
    {
        $config = [
            'sandbox_webhook_id' => 'test_webhook_id',
        ];

        $paypal = Mockery::mock(PayPal::class, [$config])->makePartial();
        $paypal->shouldAllowMockingProtectedMethods();
        $paypal->shouldReceive('verifyWebhookSignature')->andReturn(false);

        $payload = [
            'id' => 'WH-Test',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => [
                'id' => 'SALE-123',
                'parent_payment' => 'PAY-456',
            ],
        ];

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        $result = $paypal->handleWebhook($request);

        $this->assertNull($result);
    }
}
