<?php

namespace Signifly\EventsourceGenerator;

use Illuminate\Support\ServiceProvider;

class EventsourceGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('eventsource-generator.php'),
            ], 'config');

            /*
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'eventsource-generator');

            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/eventsource-generator'),
            ], 'views');
            */
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'eventsource-generator');
    }
}
