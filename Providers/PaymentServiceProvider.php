<?php

namespace Juzaweb\Modules\Payment\Providers;

use Juzaweb\Core\Facades\Menu;
use Juzaweb\Core\Providers\ServiceProvider;
use Juzaweb\Modules\Payment\Contracts\PaymentManager;

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
        $this->loadJsonTranslationsFrom(__DIR__ . '/../resources/lang', 'payment');
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

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', 'payment-module-views']);

        $this->loadViewsFrom($sourcePath, 'payment');
    }
}
