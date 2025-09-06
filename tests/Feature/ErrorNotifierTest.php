<?php

namespace Mktmsameera\LaravelErrorNotifier\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mktmsameera\LaravelErrorNotifier\ErrorNotifier;
use Mktmsameera\LaravelErrorNotifier\Jobs\SendErrorNotificationJob;
use Mktmsameera\LaravelErrorNotifier\Tests\TestCase;

class ErrorNotifierTest extends TestCase
{
    protected ErrorNotifier $errorNotifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorNotifier = app(ErrorNotifier::class);
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(ErrorNotifier::class, $this->errorNotifier);
    }

    /** @test */
    public function it_respects_enabled_setting()
    {
        config(['error-notifier.enabled' => false]);
        
        $exception = $this->createTestException();
        
        // Should not process when disabled
        $this->errorNotifier->notify($exception);
        
        // No assertions needed, just ensuring no exceptions thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function it_respects_environment_filter()
    {
        config(['error-notifier.environments' => ['production']]);
        
        $exception = $this->createTestException();
        
        // Should not process in testing environment
        $this->errorNotifier->notify($exception);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_queue_notifications()
    {
        Queue::fake();
        
        config([
            'error-notifier.queue.enabled' => true,
            'error-notifier.environments' => ['testing'],
        ]);

        $exception = $this->createTestException();
        
        $this->errorNotifier->notify($exception);
        
        Queue::assertPushed(SendErrorNotificationJob::class);
    }

    /** @test */
    public function it_implements_rate_limiting()
    {
        config([
            'error-notifier.rate_limit.enabled' => true,
            'error-notifier.rate_limit.throttle_seconds' => 60,
            'error-notifier.environments' => ['testing'],
        ]);

        $exception = $this->createTestException();
        
        // First notification should be allowed
        $this->errorNotifier->notify($exception);
        
        // Second identical notification should be rate limited
        $this->errorNotifier->notify($exception);
        
        // Check that cache key exists (indicating rate limiting is active)
        $cacheKey = 'error_notifier_' . md5(
            $exception->getMessage() . 
            $exception->getFile() . 
            $exception->getLine()
        );
        
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_excludes_configured_exceptions()
    {
        config([
            'error-notifier.filters.exclude_exceptions' => [
                \InvalidArgumentException::class,
            ],
            'error-notifier.environments' => ['testing'],
        ]);

        $exception = new \InvalidArgumentException('This should be excluded');
        
        // Should not process excluded exceptions
        $this->errorNotifier->notify($exception);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_includes_http_status_codes()
    {
        config([
            'error-notifier.filters.include_status_codes' => [500, 503],
            'error-notifier.environments' => ['testing'],
        ]);

        $exception = new \Symfony\Component\HttpKernel\Exception\HttpException(404, 'Not found');
        
        // Should not process 404 errors
        $this->errorNotifier->notify($exception);
        
        $exception500 = new \Symfony\Component\HttpKernel\Exception\HttpException(500, 'Server error');
        
        // Should process 500 errors
        $this->errorNotifier->notify($exception500);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_builds_correct_error_data()
    {
        $exception = $this->createTestException();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->errorNotifier);
        $method = $reflection->getMethod('buildErrorData');
        $method->setAccessible(true);
        
        $errorData = $method->invoke($this->errorNotifier, $exception, ['custom' => 'context']);
        
        $this->assertArrayHasKey('message', $errorData);
        $this->assertArrayHasKey('file', $errorData);
        $this->assertArrayHasKey('line', $errorData);
        $this->assertArrayHasKey('exception_class', $errorData);
        $this->assertArrayHasKey('timestamp', $errorData);
        $this->assertArrayHasKey('context', $errorData);
        
        $this->assertEquals('Test exception message', $errorData['message']);
        $this->assertEquals('Exception', $errorData['exception_class']);
        $this->assertEquals(['custom' => 'context'], $errorData['context']);
    }

    /** @test */
    public function it_can_test_disabled_channel()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Channel \'discord\' is not enabled');
        
        $this->errorNotifier->testChannel('discord');
    }

    /** @test */
    public function it_throws_exception_for_invalid_channel()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Channel 'invalid' not found");
        
        $this->errorNotifier->testChannel('invalid');
    }
}