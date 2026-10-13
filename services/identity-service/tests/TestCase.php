<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    public function createApplication()
    {
        if (file_exists(__DIR__.'/../bootstrap/cache/config.php')) {
            throw new \RuntimeException('Refusing tests with cached config');
        }
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Feature tests require isolated in-memory SQLite');
        }

        return $app;
    }
}
