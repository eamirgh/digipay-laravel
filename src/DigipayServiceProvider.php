<?php

namespace Eamirgh\Digipay;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DigipayServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/digipay.php', 'digipay');

        $this->app->singleton(DigipayManager::class, function ($app) {
            return new DigipayManager($app);
        });

        $this->app->alias(DigipayManager::class, 'digipay');

        $this->app->singleton(DigipayClient::class, function ($app) {
            return $app->make(DigipayManager::class)->connection();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/digipay.php' => $this->app->configPath('digipay.php'),
            ], 'digipay-config');
        }
    }

    public function provides(): array
    {
        return [
            DigipayManager::class,
            DigipayClient::class,
            'digipay',
        ];
    }
}
