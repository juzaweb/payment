<?php

namespace Juzaweb\Modules\Payment\Tests\Feature;

use Illuminate\Support\Facades\Request;
use Juzaweb\Modules\Payment\Tests\TestCase;
use Juzaweb\Modules\Core\Models\User;
use Juzaweb\Modules\Payment\Models\PaymentMethod;
use Juzaweb\Modules\Payment\Models\Order;
use Juzaweb\Modules\Payment\Facades\PaymentManager;
use Juzaweb\Modules\Payment\Contracts\ModuleHandlerInterface;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Services\PaymentDriverAdapter;
use Juzaweb\Modules\Payment\Services\PurchaseResult;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Mockery;
use Illuminate\Support\Str;
use Juzaweb\Modules\Payment\Enums\OrderPaymentStatus;
use Juzaweb\Modules\Payment\Models\PaymentHistory;
use Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus;

class PaymentRouteTest extends TestCase
{
    protected $user;
    protected $paymentMethod;
    protected $order;
    protected $moduleHandler;
    protected $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mix manifest for payment module
        $path = public_path('modules/payment');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!file_exists($path . '/mix-manifest.json')) {
            file_put_contents($path . '/mix-manifest.json', '{}');
        }

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Define currentActor macro if not exists
        if (!Request::hasMacro('currentActor')) {
            Request::macro('currentActor', function () {
                return $this->user();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('languages', 'default')) {
            \Illuminate\Support\Facades\Schema::table('languages', function ($table) {
                $table->boolean('default')->default(false);
            });
        }

        // Seed default language
        $language = \Juzaweb\Modules\Core\Translations\Models\Language::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English']
        );

        $language->default = true;
        $language->save();

        app()->setLocale('en');

        $this->withoutMiddleware([
            \Juzaweb\Modules\Core\Http\Middleware\MultipleLanguage::class,
        ]);

        // Register Test Module
        $this->moduleHandler = Mockery::mock(ModuleHandlerInterface::class);
        $this->moduleHandler->shouldReceive('getReturnUrl')->andReturn('http://example.com/return');
        $this->moduleHandler->shouldReceive('createOrder')->andReturnUsing(function($params) {
            return $this->order;
        });
        $this->moduleHandler->shouldReceive('success');
        $this->moduleHandler->shouldReceive('fail');
        $this->moduleHandler->shouldReceive('cancel');

        PaymentManager::registerModule('test_module', $this->moduleHandler);

        // Register Test Driver
        $this->gateway = Mockery::mock(PaymentGatewayInterface::class);
        $this->gateway->shouldReceive('isReturnInEmbed')->andReturn(false);

        $driverAdapter = Mockery::mock(PaymentDriverAdapter::class);
        $driverAdapter->shouldReceive('makeDriver')->andReturn($this->gateway);
        $driverAdapter->shouldReceive('getConfig')->andReturn([]);
        $driverAdapter->shouldReceive('hasSandbox')->andReturn(true);
        $driverAdapter->shouldReceive('isReturnInEmbed')->andReturn(false);
        $driverAdapter->shouldReceive('getDriver')->andReturn('TestDriver');

        PaymentManager::registerDriver('TestDriver', fn() => $driverAdapter);

        // Create Payment Method
        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Test Method',
            'driver' => 'TestDriver',
            'active' => true,
            'config' => [],
        ]);

        // Create Order
        $this->order = Order::create([
            'code' => 'ORD-' . Str::random(10),
            'quantity' => 1,
            'total_price' => 100,
            'total' => 100,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_method_name' => 'Test Method',
            'module' => 'test_module',
            'payment_status' => OrderPaymentStatus::PENDING,
        ]);

        $this->order->created_by = $this->user->id;
        $this->order->save();
    }

    public function test_checkout_route()
    {
        $response = $this->postJson(route('payment.checkout', ['module' => 'test_module']), [
            'method' => 'TestDriver',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['order_id', 'order_code', 'amount', 'currency']);
    }

    public function test_order_checkout_route()
    {
        $response = $this->get(route('payment.order.checkout', ['orderId' => $this->order->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment::checkout.order');
    }

    public function test_purchase_route()
    {
        // Mock PurchaseResult
        $purchaseResult = new PurchaseResult('TRANS-123', 'http://redirect.url');
        $purchaseResult->setSuccessful(false);

        $this->gateway->shouldReceive('purchase')->andReturn($purchaseResult);

        $response = $this->postJson(route('payment.purchase', ['module' => 'test_module']), [
            'method' => 'TestDriver',
            'order_id' => $this->order->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'redirect',
            'redirect' => 'http://redirect.url',
        ]);
    }

    public function test_return_route()
    {
        // Create PaymentHistory
        $paymentHistory = PaymentHistory::create([
            'method_id' => $this->paymentMethod->id,
            'payment_method' => $this->paymentMethod->driver,
            'module' => 'test_module',
            'status' => PaymentHistoryStatus::PROCESSING,
            'paymentable_type' => Order::class,
            'paymentable_id' => $this->order->id,
            'payment_id' => 'TRANS-123',
            'payer_type' => get_class($this->user),
            'payer_id' => $this->user->id,
        ]);

        $completeResult = new CompleteResult('TRANS-123', true);
        $completeResult->setSuccessful(true);

        $this->gateway->shouldReceive('complete')->andReturn($completeResult);

        $response = $this->getJson(route('payment.return', [
            'module' => 'test_module',
            'paymentHistoryId' => $paymentHistory->id
        ]));

        $response->assertStatus(200);
    }

    public function test_cancel_route()
    {
         $paymentHistory = PaymentHistory::create([
            'method_id' => $this->paymentMethod->id,
            'payment_method' => $this->paymentMethod->driver,
            'module' => 'test_module',
            'status' => PaymentHistoryStatus::PROCESSING,
            'paymentable_type' => Order::class,
            'paymentable_id' => $this->order->id,
            'payment_id' => 'TRANS-123',
            'payer_type' => get_class($this->user),
            'payer_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('payment.cancel', [
            'module' => 'test_module',
            'paymentHistoryId' => $paymentHistory->id
        ]));

        // Cancel route returns 422 warning response
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Payment has been cancelled!',
            'redirect' => 'http://example.com/return',
        ]);
    }

    public function test_embed_route()
    {
        $paymentHistory = PaymentHistory::create([
            'method_id' => $this->paymentMethod->id,
            'payment_method' => $this->paymentMethod->driver,
            'module' => 'test_module',
            'status' => PaymentHistoryStatus::PROCESSING,
            'paymentable_type' => Order::class,
            'paymentable_id' => $this->order->id,
            'payer_type' => get_class($this->user),
            'payer_id' => $this->user->id,
        ]);

        $response = $this->get(route('payment.embed', [
            'module' => 'test_module',
            'paymentHistoryId' => $paymentHistory->id
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('payment::method.embed');
    }

    public function test_status_route()
    {
        $paymentHistory = PaymentHistory::create([
            'method_id' => $this->paymentMethod->id,
            'payment_method' => $this->paymentMethod->driver,
            'module' => 'test_module',
            'status' => PaymentHistoryStatus::SUCCESS,
            'paymentable_type' => Order::class,
            'paymentable_id' => $this->order->id,
            'payer_type' => get_class($this->user),
            'payer_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('payment.status', [
            'module' => 'test_module',
            'paymentHistoryId' => $paymentHistory->id
        ]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }
}
