<?php

namespace Mktmsameera\LaravelErrorNotifier\Channels;

use Illuminate\Support\Facades\Mail;
use Mktmsameera\LaravelErrorNotifier\Contracts\NotificationChannelInterface;
use Mktmsameera\LaravelErrorNotifier\Mail\ErrorNotificationMail;

class EmailChannel implements NotificationChannelInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        return ($this->config['enabled'] ?? false) && 
               !empty($this->config['to']) && 
               !empty($this->config['from']);
    }

    public function send(array $errorData): void
    {
        $recipients = $this->config['to'];
        
        if (is_string($recipients)) {
            $recipients = [$recipients];
        }

        $recipients = array_filter($recipients);

        if (empty($recipients)) {
            throw new \Exception("No email recipients configured");
        }

        foreach ($recipients as $recipient) {
            Mail::to(trim($recipient))
                ->send(new ErrorNotificationMail($errorData, $this->config));
        }
    }

    public function getName(): string
    {
        return 'email';
    }
}