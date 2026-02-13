<?php

namespace Zeyn4loff\FluentResources\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Zeyn4loff\FluentResources\FluentResourceServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [FluentResourceServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Устанавливаем дефолтные настройки для тестов
        $app['config']->set('fluent-resources.default_language_id', 1);
        $app['config']->set('fluent-resources.convert_keys_to_camel_case', false);
    }
}