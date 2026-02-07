<?php

namespace Juzaweb\Modules\Payment\Providers;

use Juzaweb\Modules\Core\Facades\Menu;
use Juzaweb\Modules\Core\Providers\ServiceProvider;
use Juzaweb\Modules\Payment\Contracts\PaymentManager;
use Juzaweb\Modules\Payment\Methods;
use Juzaweb\Modules\Payment\Services\PaymentDriverAdapter;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerMenu();

        // $this->app[PaymentManager::class]->registerModule(
        //     'test',
        //     new PaymentTestHandler()
        // );

        $this->app[PaymentManager::class]->registerDriver(
            'PayPal',
            fn() => new PaymentDriverAdapter(
                Methods\PayPal::class,
                [
                    'sandbox_client_id' => __('Sandbox Client ID'),
                    'sandbox_secret' => __('Sandbox Secret'),
                    'sandbox_webhook_id' => __('Sandbox Webhook ID'),
                    'live_client_id' => __('Live Client ID'),
                    'live_secret' => __('Live Secret'),
                    'live_webhook_id' => __('Live Webhook ID'),
                ]
            )
        );

        // $this->app[PaymentManager::class]->registerDriver(
        //     'Payos',
        //     fn() => new PaymentDriverAdapter(
        //         Methods\Payos::class,
        //         [
        //             'clientId' => __('Client ID'),
        //             'key' => __('Key'),
        //             'checksumKey' => __('Checksum Key'),
        //         ],
        //         false
        //     )
        // );

        $this->app[PaymentManager::class]->registerDriver(
            'Stripe',
            fn() => new PaymentDriverAdapter(
                Methods\Stripe::class,
                [
                    'sandbox_publishable_key' => __('Sandbox Publishable key'),
                    'sandbox_secret_key' => __('Sandbox Secret key'),
                    'sandbox_webhook_secret' => __('Sandbox Webhook secret'),
                    'live_publishable_key' => __('Live Publishable key'),
                    'live_secret_key' => __('Live Secret key'),
                    'live_webhook_secret' => __('Live Webhook secret'),
                ]
            )
        );

        $this->app[PaymentManager::class]->registerDriver(
            'Custom',
            fn() => new PaymentDriverAdapter(
                Methods\Custom::class,
                [] // No configuration needed
            )
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerHelpers();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        $this->app->singleton(
            PaymentManager::class,
            function ($app) {
                return new \Juzaweb\Modules\Payment\Services\PaymentManager();
            }
        );
    }

    protected function registerHelpers(): void
    {
        require_once __DIR__ . '/../../helpers/helpers.php';
    }

    protected function registerMenu(): void
    {
        Menu::make('orders', function () {
            return [
                'title' => __('Orders'),
            ];
        });

        Menu::make('payment-methods', function () {
            return [
                'title' => __('Payment Methods'),
                'parent' => 'settings',
            ];
        });
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/payment.php' => config_path('payment.php'),
        ], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../../config/payment.php', 'payment');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'payment');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../resources/lang');
    }

    /**
     * Register views.
     *
     * @return void
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/payment');

        $sourcePath = __DIR__ . '/../resources/views';

        $this->publishes([$sourcePath => $viewPath], ['views', 'payment-module-views']);

        $this->loadViewsFrom($sourcePath, 'payment');
    }
}
