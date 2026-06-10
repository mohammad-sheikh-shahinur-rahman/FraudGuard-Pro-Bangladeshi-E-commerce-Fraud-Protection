<?php

namespace FraudChecker\Laravel;

use Illuminate\Support\ServiceProvider;
use FraudChecker\FraudChecker;
use FraudChecker\Couriers\Steadfast;

class FraudCheckerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/fraudchecker.php', 'fraudchecker');

        $this->app->singleton('fraud-checker', function ($app) {
            $checker = new FraudChecker();
            $config = config('fraudchecker');

            // Automatically load enabled couriers
            if ($config['couriers']['steadfast']['enabled']) {
                $checker->addCourier(new Steadfast($config['couriers']['steadfast']));
            }
            
            // Add other couriers similarly...

            return $checker;
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/fraudchecker.php' => config_path('fraudchecker.php'),
        ], 'config');
    }
}
