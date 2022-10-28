<?php

namespace Andruby\Pay;

use Andruby\Pay\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;

class PayServiceProvider extends ServiceProvider
{

    protected $routeMiddleware = [

    ];

    protected $commands = [
        InstallCommand::class
    ];

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->publishes([
                __DIR__ . '/../config/deep_pay.php' => config_path('deep_pay.php'),
            ]);
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/route.php');

    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/deep_pay.php', 'deep_pay');

        $this->registerRouteMiddleware();

        $this->commands($this->commands);
    }


    protected function registerRouteMiddleware()
    {
        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            app('router')->aliasMiddleware($key, $middleware);
        }

    }
}
