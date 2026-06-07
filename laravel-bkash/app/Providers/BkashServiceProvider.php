<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BkashService;

class BkashServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('bkash', function ($app) {
            return new BkashService();
        });

        $this->mergeConfigFrom(
            base_path('config/bkash.php'), 'bkash'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                base_path('config/bkash.php') => config_path('bkash.php'),
            ], 'bkash-config');
        }
    }
}
