<?php

namespace Signifly\EventSourceGenerator;

use Illuminate\Support\ServiceProvider;
use Signifly\EventSourceGenerator\Lexers\CommandLexer;
use Signifly\EventSourceGenerator\Lexers\ComputedLexer;
use Signifly\EventSourceGenerator\Lexers\EventLexer;
use Signifly\EventSourceGenerator\Lexers\FieldLexer;
use Signifly\EventSourceGenerator\Lexers\InterfaceLexer;
use Signifly\EventSourceGenerator\Lexers\TypeLexer;

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
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'eventsource-generator');

        $this->app->singleton(EventSourceGenerator::class, function ($app) {
            $generator = new EventSourceGenerator();
            $generator->registerLexer($fieldLexer = new FieldLexer());
            $generator->registerLexer($computed = new ComputedLexer());
            $generator->registerLexer(new CommandLexer($fieldLexer, $computed));
            $generator->registerLexer(new EventLexer($fieldLexer));
            $generator->registerLexer(new InterfaceLexer());
            $generator->registerLexer(new TypeLexer());

//            $generator->registerGenerator(new \Blueprint\Generators\MigrationGenerator($app['files']));
//            $generator->registerGenerator(new \Blueprint\Generators\ModelGenerator($app['files']));
//            $generator->registerGenerator(new \Blueprint\Generators\FactoryGenerator($app['files']));

            return $generator;
        });
    }

    public function provides()
    {
        return [
            EventSourceGenerator::class,
        ];
    }
}
