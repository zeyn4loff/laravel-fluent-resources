<?php

namespace Zeyn4loff\FluentResources;

use Illuminate\Support\ServiceProvider;

class FluentResourceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/fluent-resources.php' => config_path('fluent-resources.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fluent-resources.php', 'fluent-resources');
    }
}