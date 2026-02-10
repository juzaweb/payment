# Juzaweb Payment Module

A flexible Payment Module for the Juzaweb CMS. This module provides a unified interface for handling multiple payment gateways (PayPal, Stripe) and allows other modules to integrate payment functionalities easily.

## Features

- **Multiple Payment Gateways:** Support for PayPal, Stripe, and Custom payments.
- **Module Integration:** Extensible system allowing other modules (e.g., Shop, Membership) to register themselves as payment handlers.
- **Event Driven:** Dispatches events for payment success, failure, and cancellation.
- **Admin Management:** Configure payment methods and view payment history via the Juzaweb Admin Panel.

## Installation

1.  **Require the package via Composer:**

    ```bash
    composer require juzaweb/payment
    ```

2.  **Publish the configuration and assets:**

    ```bash
    php artisan vendor:publish --tag="payment-config"
    php artisan vendor:publish --tag="payment-module-views"
    ```

3.  **Run migrations:**

    ```bash
    php artisan migrate
    ```

## Configuration

Payment methods are configured directly in the Juzaweb Admin Panel.
Navigate to **Settings > Payment Methods** to enable and configure gateways like PayPal (Client ID, Secret) and Stripe (Publishable Key, Secret Key).

## Usage

### 1. Implement `Paymentable` Contract

Your order model (the entity being paid for) must implement the `Juzaweb\Modules\Payment\Contracts\Paymentable` interface.

```php
use Juzaweb\Modules\Payment\Contracts\Paymentable;
use Illuminate\Database\Eloquent\Model;

class Order extends Model implements Paymentable
{
    public function getTotalAmount(): float
    {
        return $this->total_price;
    }

    public function getCurrency(): string
    {
        return 'USD';
    }

    public function getPaymentDescription(): string
    {
        return "Payment for Order #{$this->code}";
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
```

### 2. Create a Module Handler

Create a class that implements `Juzaweb\Modules\Payment\Contracts\ModuleHandlerInterface`. This handler manages the business logic after a payment transaction.

```php
use Juzaweb\Modules\Payment\Contracts\ModuleHandlerInterface;
use Juzaweb\Modules\Payment\Contracts\Paymentable;

class MyShopModuleHandler implements ModuleHandlerInterface
{
    public function createOrder(array $params): Paymentable
    {
        // Logic to create an order if needed, or return an existing one
        return Order::find($params['order_id']);
    }

    public function success(Paymentable $paymentable, array $params): void
    {
        // Handle successful payment (e.g., update order status to 'completed', send email)
        $paymentable->update(['status' => 'completed']);
    }

    public function fail(Paymentable $paymentable, array $params): void
    {
        // Handle failed payment
        $paymentable->update(['status' => 'failed']);
    }

    public function cancel(Paymentable $paymentable, array $params): void
    {
        // Handle cancelled payment
        $paymentable->update(['status' => 'cancelled']);
    }

    public function getReturnUrl(): string
    {
        return url('/shop/checkout/completed');
    }
}
```

### 3. Register the Module Handler

Register your module handler in your module's `ServiceProvider` using the `PaymentManager`.

```php
use Juzaweb\Modules\Payment\Contracts\PaymentManager;

public function boot()
{
    $this->app[PaymentManager::class]->registerModule(
        'my_shop_module',
        new MyShopModuleHandler()
    );
}
```

### 4. Initiate a Payment

Use the `PaymentManager` to create a payment transaction.

```php
use Juzaweb\Modules\Payment\Contracts\PaymentManager;

public function checkout(Request $request)
{
    $user = $request->user();
    $paymentMethod = $request->input('payment_method'); // e.g., 'paypal'
    $orderId = $request->input('order_id');

    // Additional params passed to the gateway and handler
    $params = [
        'return_url' => route('payment.return'),
        'cancel_url' => route('payment.cancel'),
    ];

    try {
        $response = app(PaymentManager::class)->create(
            $user,
            'my_shop_module',
            $paymentMethod,
            $orderId,
            $params
        );

        // Redirect user to the payment gateway
        if ($response->isRedirect()) {
            return $response->redirect();
        }

        return $response->getMessage();

    } catch (\Exception $e) {
        return redirect()->back()->withErrors($e->getMessage());
    }
}
```

## Supported Gateways

- **PayPal:** Configure Client ID and Secret in Admin Panel.
- **Stripe:** Configure Publishable and Secret Keys in Admin Panel.
- **Custom:** Extend functionality with custom payment drivers.

### Registering a Custom Driver

You can register a custom payment driver in your `ServiceProvider`:

```php
use Juzaweb\Modules\Payment\Contracts\PaymentManager;
use Juzaweb\Modules\Payment\Services\PaymentDriverAdapter;

$this->app[PaymentManager::class]->registerDriver(
    'MyGateway',
    fn() => new PaymentDriverAdapter(
        MyGatewayImplementation::class,
        ['api_key' => 'Config Label']
    )
);
```

## Events

The module fires the following events during the payment process:

- `Juzaweb\Modules\Payment\Events\PaymentSuccess`
- `Juzaweb\Modules\Payment\Events\PaymentFail`
- `Juzaweb\Modules\Payment\Events\PaymentCancel`

Listen to these events in your `EventServiceProvider` to perform additional actions.
