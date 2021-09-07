<?php

namespace KnotAShell\Orders;

use KnotAShell\Orders\App\Http\Middleware\AdminOnly;
use KnotAShell\Orders\App\Http\Middleware\PaymentKey;
use KnotAShell\Orders\Console\ManagerSuspension;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class OrdersServiceProvider extends ServiceProvider
{
    private $_packageTag = 'orders';

    /**
    * Bootstrap any application services.
    *
    * @return  void
    */
    public function boot()
    {
        $this->registerRoutes();
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('paymentKey', PaymentKey::class);
        $router->aliasMiddleware('admin', AdminOnly::class);
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/orders.php' => config_path('orders.php'),
                __DIR__ . '/../config/cashier.php'  => config_path('cashier.php'),
                __DIR__ . '/../config/manager-suspension.php'  => config_path('manager-suspension.php'),
                __DIR__ . '/../config/stripe-webhooks.php'  => config_path('stripe-webhooks.php'),
                __DIR__ . '/../config/webhook-client.php'  => config_path('webhook-client.php'),
                __DIR__.'/Database/Migrations'      => database_path('migrations')
            ], $this->_packageTag);
            $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
            $this->publishes([
                __DIR__.'/resources/views' => resource_path('views/vendor/' . $this->_packageTag),
              ], 'views');
            $this->commands([
                ManagerSuspension::class,
                ]);
            }
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->_packageTag);
        $this->app->booted(function() {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('manager:suspend')->weekdays()->at('13:00');
        });
    }

    /**
    * Register any application services.
    *
    * @return void
    */
    public function register()
    {
        $this->app->register(\Spatie\WebhookClient\WebhookClientServiceProvider::class);
        $this->app->register(\Spatie\StripeWebhooks\StripeWebhooksServiceProvider::class);
        $this->mergeConfigFrom(__DIR__ . '/../config/orders.php', $this->_packageTag);
        $this->mergeConfigFrom(__DIR__ . '/../config/cashier.php', $this->_packageTag);
        $this->mergeConfigFrom(__DIR__ . '/../config/manager-suspension.php', $this->_packageTag);
        $this->mergeConfigFrom(__DIR__ . '/../config/stripe-webhooks.php', $this->_packageTag);
        $this->mergeConfigFrom(__DIR__ . '/../config/webhook-client.php', $this->_packageTag);
        $this->app->bind('payment', function ($app) {
            return new Payment();
        });
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        });

        Route::group(['prefix' => config('orders.route_prefix'), 'middleware' => ['api']], function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/webhooks.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('orders.route_prefix'),
            'middleware' => config('orders.middleware'),
        ];
    }
}