<?php

namespace Mktmsameera\LaravelErrorNotifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mktmsameera\LaravelErrorNotifier\ErrorNotifier;

class SendErrorNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $errorData;
    protected array $config;

    public function __construct(array $errorData, array $config)
    {
        $this->errorData = $errorData;
        $this->config = $config;
        
        // Set queue connection and name from config
        $this->onConnection($config['queue']['connection'] ?? 'default');
        $this->onQueue($config['queue']['queue'] ?? 'notifications');
    }

    public function handle()
    {
        $notifier = new ErrorNotifier($this->config);
        $notifier->sendNotifications($this->errorData);
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('Error notification job failed: ' . $exception->getMessage(), [
            'error_data' => $this->errorData,
            'job_exception' => $exception->getMessage(),
        ]);
    }
}