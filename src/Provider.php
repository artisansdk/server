<?php

namespace ArtisanSDK\Server;

use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/server.php' => config_path('server.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ServerStart::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/server.php', 'server');
    }
}
