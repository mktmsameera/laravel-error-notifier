<?php

namespace Mktmsameera\LaravelErrorNotifier\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Mktmsameera\LaravelErrorNotifier\LaravelErrorNotifierServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpConfig();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelErrorNotifierServiceProvider::class,
        ];
    }

    protected function setUpConfig()
    {
        config([
            'error-notifier.enabled' => true,
            'error-notifier.environments' => ['testing'],
            'error-notifier.rate_limit.enabled' => false,
            'error-notifier.queue.enabled' => false,
            'error-notifier.channels.discord.enabled' => false,
            'error-notifier.channels.slack.enabled' => false,
            'error-notifier.channels.whatsapp.enabled' => false,
            'error-notifier.channels.email.enabled' => false,
        ]);
    }

    protected function createTestException(): \Exception
    {
        return new \Exception('Test exception message', 500);
    }

    protected function createTestErrorData(): array
    {
        return [
            'message' => 'Test exception message',
            'file' => '/test/TestFile.php',
            'line' => 42,
            'exception_class' => 'Exception',
            'timestamp' => now()->toISOString(),
            'app_name' => 'Test App',
            'app_environment' => 'testing',
            'request' => [
                'url' => 'https://example.com/test',
                'method' => 'GET',
                'ip' => '127.0.0.1',
                'user_agent' => 'Test Agent',
            ],
            'user' => [
                'id' => 1,
                'email' => 'test@example.com',
            ],
        ];
    }
}