<?php

namespace Mktmsameera\LaravelErrorNotifier;

use Illuminate\Support\ServiceProvider;
use Mktmsameera\LaravelErrorNotifier\Commands\TestNotificationCommand;

class LaravelErrorNotifierServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ErrorNotifier::class, function ($app) {
            return new ErrorNotifier(config('error-notifier'));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/error-notifier.php' => config_path('error-notifier.php'),
        ], 'error-notifier-config');

        // Load package views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'error-notifier');

        // Publish views
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/error-notifier'),
        ], 'error-notifier-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestNotificationCommand::class,
            ]);
        }

        $this->mergeConfigFrom(
            __DIR__.'/config/error-notifier.php', 'error-notifier'
        );
    }
}