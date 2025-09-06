<?php

namespace Juzaweb\Modules\Payment\Providers;

use Juzaweb\Core\Facades\Menu;
use Juzaweb\Core\Providers\ServiceProvider;
use Juzaweb\Modules\Payment\Contracts\PaymentManager;
use Juzaweb\Modules\Payment\Methods;
use Juzaweb\Modules\Payment\Services\PaymentDriverAdapter;
use Juzaweb\Modules\Payment\Services\PaymentTestHandler;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->booted(
            function () {
                $this->registerMenu();
            }
        );

        $this->app[PaymentManager::class]->registerModule(
            'test',
            new PaymentTestHandler()
        );

        $this->app[PaymentManager::class]->registerDriver(
            'PayPal',
            fn () => new PaymentDriverAdapter(
                Methods\PayPal::class,
                [
                    'clientId' => __('Client ID'),
                    'secret' => __('Secret'),
                ]
            )
        );

        $this->app[PaymentManager::class]->registerDriver(
            'Payos',
            fn () => new PaymentDriverAdapter(
                Methods\Payos::class,
                [
                    'clientId' => __('Client ID'),
                    'key' => __('Key'),
                    'checksumKey' => __('Checksum Key'),
                ],
                false
            )
        );

        $this->app[PaymentManager::class]->registerDriver(
            'Stripe',
            fn () => new PaymentDriverAdapter(
                Methods\Stripe::class,
                [
                    'publishable_key' => __('Publishable key'),
                    'secret_key' => __('Secret key'),
                    'webhook_secret' => __('Webhook secret'),
                ]
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
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(
            PaymentManager::class,
            function ($app) {
                return new \Juzaweb\Modules\Payment\Services\PaymentManager();
            }
        );
    }

    protected function registerMenu(): void
    {
        Menu::make('payment-methods', __('Payment Methods'))
            ->parent('settings');
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/payment.php' => config_path('payment.php'),
        ], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../config/payment.php', 'payment');
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
