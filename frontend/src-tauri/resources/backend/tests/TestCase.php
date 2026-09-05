<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Log;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        // Ensure in-memory SQLite is used for testing
        // Must set $_ENV and $_SERVER before app boots for env() helper to work
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        
        $app->make(Kernel::class)->bootstrap();
        
        // Disable logging during tests to avoid permission issues
        Log::swap(new \Monolog\Logger('testing', [new \Monolog\Handler\NullHandler()]));
        
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}
