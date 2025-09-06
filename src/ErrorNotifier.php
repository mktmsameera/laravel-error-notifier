<?php

namespace Mktmsameera\LaravelErrorNotifier;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mktmsameera\LaravelErrorNotifier\Channels\DiscordChannel;
use Mktmsameera\LaravelErrorNotifier\Channels\EmailChannel;
use Mktmsameera\LaravelErrorNotifier\Channels\SlackChannel;
use Mktmsameera\LaravelErrorNotifier\Channels\WhatsAppChannel;
use Mktmsameera\LaravelErrorNotifier\Jobs\SendErrorNotificationJob;

class ErrorNotifier
{
    protected array $config;
    protected array $channels;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeChannels();
    }

    protected function initializeChannels(): void
    {
        $this->channels = [
            'discord' => new DiscordChannel($this->config['channels']['discord'] ?? []),
            'slack' => new SlackChannel($this->config['channels']['slack'] ?? []),
            'whatsapp' => new WhatsAppChannel($this->config['channels']['whatsapp'] ?? []),
            'email' => new EmailChannel($this->config['channels']['email'] ?? []),
        ];
    }

    public function notify(\Throwable $exception, array $context = []): void
    {
        if (!$this->shouldNotify($exception)) {
            return;
        }

        if ($this->isRateLimited($exception)) {
            return;
        }

        $errorData = $this->buildErrorData($exception, $context);

        if ($this->config['queue']['enabled'] ?? true) {
            SendErrorNotificationJob::dispatch($errorData, $this->config);
        } else {
            $this->sendNotifications($errorData);
        }
    }

    public function sendNotifications(array $errorData): void
    {
        $enabledChannels = $this->getEnabledChannels();

        foreach ($enabledChannels as $channelName => $channel) {
            try {
                Log::info("Sending error notification via {$channelName}");
                $channel->send($errorData);
            } catch (\Exception $e) {
                Log::error("Failed to send notification via {$channelName}: " . $e->getMessage(), [
                    'channel' => $channelName,
                    'error' => $e->getMessage(),
                    'original_error' => $errorData['message'],
                ]);
            }
        }
    }

    protected function shouldNotify(\Throwable $exception): bool
    {
        if (!($this->config['enabled'] ?? true)) {
            return false;
        }

        if (!in_array(app()->environment(), $this->config['environments'] ?? [])) {
            return false;
        }

        // Check excluded exceptions
        $excludedExceptions = $this->config['filters']['exclude_exceptions'] ?? [];
        foreach ($excludedExceptions as $excludedException) {
            if ($exception instanceof $excludedException) {
                return false;
            }
        }

        // Check HTTP status codes for HTTP exceptions
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $includedCodes = $this->config['filters']['include_status_codes'] ?? [500];
            return in_array($exception->getStatusCode(), $includedCodes);
        }

        return true;
    }

    protected function isRateLimited(\Throwable $exception): bool
    {
        if (!($this->config['rate_limit']['enabled'] ?? true)) {
            return false;
        }

        $cacheKey = $this->generateCacheKey($exception);
        $throttleSeconds = $this->config['rate_limit']['throttle_seconds'] ?? 300;

        if (Cache::has($cacheKey)) {
            return true;
        }

        Cache::put($cacheKey, true, $throttleSeconds);
        return false;
    }

    protected function generateCacheKey(\Throwable $exception): string
    {
        $prefix = $this->config['rate_limit']['cache_key_prefix'] ?? 'error_notifier_';
        return $prefix . md5(
            $exception->getMessage() . 
            $exception->getFile() . 
            $exception->getLine()
        );
    }

    protected function buildErrorData(\Throwable $exception, array $context = []): array
    {
        $maxLength = $this->config['filters']['max_message_length'] ?? 2000;
        $appInfo = $this->config['app_info'] ?? [];

        $data = [
            'message' => substr($exception->getMessage() ?: 'Unknown error', 0, $maxLength),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'exception_class' => get_class($exception),
            'timestamp' => now()->toISOString(),
            'app_name' => $appInfo['name'] ?? 'Laravel App',
            'app_environment' => $appInfo['environment'] ?? 'production',
            'app_url' => $appInfo['url'] ?? null,
        ];

        // Add request information
        if (($appInfo['include_request_info'] ?? true) && request()) {
            $data['request'] = [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];
        }

        // Add user information
        if (($appInfo['include_user_info'] ?? true) && auth()->check()) {
            $data['user'] = [
                'id' => auth()->id(),
                'email' => auth()->user()->email ?? null,
            ];
        }

        // Add stack trace if enabled
        if ($appInfo['include_stack_trace'] ?? false) {
            $data['stack_trace'] = $exception->getTraceAsString();
        }

        // Add custom context
        if (!empty($context)) {
            $data['context'] = $context;
        }

        return $data;
    }

    protected function getEnabledChannels(): array
    {
        return array_filter($this->channels, function ($channel, $name) {
            return $channel->isEnabled();
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function testChannel(string $channelName): bool
    {
        if (!isset($this->channels[$channelName])) {
            throw new \InvalidArgumentException("Channel '{$channelName}' not found");
        }

        $channel = $this->channels[$channelName];
        
        if (!$channel->isEnabled()) {
            throw new \RuntimeException("Channel '{$channelName}' is not enabled");
        }

        $testData = [
            'message' => 'This is a test notification from Laravel Error Notifier',
            'file' => '/test/TestFile.php',
            'line' => 42,
            'exception_class' => 'TestException',
            'timestamp' => now()->toISOString(),
            'app_name' => $this->config['app_info']['name'] ?? 'Test App',
            'app_environment' => 'testing',
            'request' => [
                'url' => 'https://example.com/test',
                'method' => 'GET',
                'ip' => '127.0.0.1',
            ],
        ];

        try {
            $channel->send($testData);
            return true;
        } catch (\Exception $e) {
            Log::error("Test notification failed for {$channelName}: " . $e->getMessage());
            throw $e;
        }
    }
}