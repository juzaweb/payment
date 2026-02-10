<?php

namespace Juzaweb\Modules\Payment\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Juzaweb\Modules\Payment\Contracts\ModuleHandlerInterface;
use Juzaweb\Modules\Payment\Contracts\PaymentGatewayInterface;
use Juzaweb\Modules\Payment\Contracts\PaymentManager;
use Juzaweb\Modules\Payment\Contracts\Paymentable;
use Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus;
use Juzaweb\Modules\Payment\Events\PaymentCancel;
use Juzaweb\Modules\Payment\Events\PaymentFail;
use Juzaweb\Modules\Payment\Events\PaymentSuccess;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Models\Order;
use Juzaweb\Modules\Payment\Models\PaymentHistory;
use Juzaweb\Modules\Payment\Models\PaymentMethod;
use Juzaweb\Modules\Payment\Services\CompleteResult;
use Juzaweb\Modules\Payment\Services\PurchaseResult;
use Juzaweb\Modules\Payment\Tests\TestCase;
use Mockery;
use Juzaweb\Modules\Admin\Models\User;

class PaymentManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure database tables are created
    }

    public function test_register_driver_exception()
    {
        $manager = app(PaymentManager::class);

        $this->expectException(\InvalidArgumentException::class);
        $manager->registerDriver('PayPal', function () {});
    }

    public function test_register_module_success()
    {
        $manager = app(PaymentManager::class);
        $module = Mockery::mock(ModuleHandlerInterface::class);

        $manager->registerModule('test_module', $module);

        $this->assertTrue($manager->hasModule('test_module'));
        $this->assertContains('test_module', $manager->modules());
    }

    public function test_register_module_exception()
    {
        $manager = app(PaymentManager::class);
        $module = Mockery::mock(ModuleHandlerInterface::class);

        $manager->registerModule('test_module_dup', $module);

        $this->expectException(\InvalidArgumentException::class);
        $manager->registerModule('test_module_dup', $module);
    }

    public function test_get_driver_instance()
    {
        $manager = app(PaymentManager::class);

        // Mock a driver resolver
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $adapter = Mockery::mock('Juzaweb\Modules\Payment\Services\PaymentDriverAdapter');
        $adapter->shouldReceive('makeDriver')->andReturn($gateway);
        $adapter->shouldReceive('getConfig')->andReturn([]);
        $adapter->shouldReceive('hasSandbox')->andReturn(true);

        $manager->registerDriver('test_driver', function () use ($adapter) {
            return $adapter;
        });

        $driver = $manager->driver('test_driver', []);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $driver);

        $config = $manager->config('test_driver');
        $this->assertIsArray($config);

        $this->expectException(PaymentException::class);
        $manager->driver('non_existent_driver', []);
    }

    public function test_create_payment_success()
    {
        Event::fake();
        $manager = app(PaymentManager::class);

        // Create User
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Payment Method
        $method = PaymentMethod::create([
            'driver' => 'test_driver_create',
            'active' => true,
            'config' => ['key' => 'value'],
        ]);

        // Create Order
        $order = Order::create([
            'code' => 'ORDER123',
            'total_price' => 100,
            'total' => 100,
            'quantity' => 1,
            'payment_method_id' => $method->id,
            'payment_method_name' => 'Test Method',
        ]);

        // Mock Gateway
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $purchaseResult = new PurchaseResult('TRANS123', null, []);
        $purchaseResult->setSuccessful(true);
        $gateway->shouldReceive('purchase')->once()->andReturn($purchaseResult);

        // Mock Adapter
        $adapter = Mockery::mock('Juzaweb\Modules\Payment\Services\PaymentDriverAdapter');
        $adapter->shouldReceive('makeDriver')->andReturn($gateway);
        $adapter->shouldReceive('getConfig')->andReturn([]);

        // Register Driver
        $manager->registerDriver('test_driver_create', function () use ($adapter) {
            return $adapter;
        });

        // Mock Module Handler
        $moduleHandler = Mockery::mock(ModuleHandlerInterface::class);
        $moduleHandler->shouldReceive('success')->once();

        // Register Module
        $manager->registerModule('test_module_create', $moduleHandler);

        // Call Create
        $result = $manager->create($user, 'test_module_create', $method, $order->id, []);

        $this->assertInstanceOf(PurchaseResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('TRANS123', $result->getTransactionId());

        // Assert History Created
        $history = PaymentHistory::where('payment_id', 'TRANS123')->first();
        $this->assertNotNull($history);
        $this->assertEquals(PaymentHistoryStatus::SUCCESS, $history->status);
        $this->assertEquals($user->id, $history->payer_id);
        $this->assertEquals($order->id, $history->paymentable_id);

        Event::assertDispatched(PaymentSuccess::class);
    }

    public function test_complete_payment_success()
    {
        Event::fake();
        $manager = app(PaymentManager::class);

        // Create User & Order
        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        $method = PaymentMethod::create([
            'driver' => 'test_driver_complete',
            'active' => true,
            'config' => ['key' => 'value'],
        ]);

        $order = Order::create([
            'code' => 'ORDER456',
            'total_price' => 200,
            'total' => 200,
            'quantity' => 1,
            'payment_method_id' => $method->id,
            'payment_method_name' => 'Test Method',
        ]);

        // Create History
        $history = new PaymentHistory([
            'method_id' => $method->id,
            'payment_method' => $method->driver,
            'module' => 'test_module_complete',
            'status' => PaymentHistoryStatus::PROCESSING,
            'payment_id' => 'TRANS456',
        ]);
        $history->payer()->associate($user);
        $history->paymentable()->associate($order);
        $history->save();

        // Mock Gateway
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        // CompleteResult constructor signature: transactionId, isSuccessful (string), data
        $completeResult = new CompleteResult('TRANS456', '1', []);
        // PaymentResult default isSuccessful is false, we rely on CompleteResult behavior or explicit set
        $completeResult->setSuccessful(true);
        $gateway->shouldReceive('complete')->once()->andReturn($completeResult);

        // Mock Adapter
        $adapter = Mockery::mock('Juzaweb\Modules\Payment\Services\PaymentDriverAdapter');
        $adapter->shouldReceive('makeDriver')->andReturn($gateway);
        $adapter->shouldReceive('getConfig')->andReturn([]);

        // Register Driver
        $manager->registerDriver('test_driver_complete', function () use ($adapter) {
            return $adapter;
        });

        // Mock Module Handler
        $moduleHandler = Mockery::mock(ModuleHandlerInterface::class);
        $moduleHandler->shouldReceive('success')->once();

        // Register Module
        $manager->registerModule('test_module_complete', $moduleHandler);

        // Call Complete
        $result = $manager->complete('test_module_complete', $history, []);

        $this->assertTrue($result->isSuccessful());

        $history->refresh();
        $this->assertEquals(PaymentHistoryStatus::SUCCESS, $history->status);

        Event::assertDispatched(PaymentSuccess::class);
    }

    public function test_cancel_payment()
    {
        Event::fake();
        $manager = app(PaymentManager::class);

         // Create User & Order
         $user = User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
        ]);

        $method = PaymentMethod::create([
            'driver' => 'test_driver_cancel',
            'active' => true,
            'config' => ['key' => 'value'],
        ]);

        $order = Order::create([
            'code' => 'ORDER789',
            'total_price' => 300,
            'total' => 300,
            'quantity' => 1,
            'payment_method_id' => $method->id,
            'payment_method_name' => 'Test Method',
        ]);

        $history = new PaymentHistory([
            'method_id' => $method->id,
            'payment_method' => $method->driver,
            'module' => 'test_module_cancel',
            'status' => PaymentHistoryStatus::PROCESSING,
            'payment_id' => 'TRANS789',
        ]);
        $history->payer()->associate($user);
        $history->paymentable()->associate($order);
        $history->save();

        // Mock Module Handler
        $moduleHandler = Mockery::mock(ModuleHandlerInterface::class);
        $moduleHandler->shouldReceive('cancel')->once();

        // Register Module
        $manager->registerModule('test_module_cancel', $moduleHandler);

        // Call Cancel
        $result = $manager->cancel('test_module_cancel', $history, []);

        $this->assertTrue($result);

        Event::assertDispatched(PaymentCancel::class);
    }
}
