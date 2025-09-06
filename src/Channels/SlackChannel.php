<?php

namespace Mktmsameera\LaravelErrorNotifier\Channels;

use Illuminate\Support\Facades\Http;
use Mktmsameera\LaravelErrorNotifier\Contracts\NotificationChannelInterface;

class SlackChannel implements NotificationChannelInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        return ($this->config['enabled'] ?? false) && !empty($this->config['webhook_url']);
    }

    public function send(array $errorData): void
    {
        $attachment = $this->buildAttachment($errorData);
        
        $payload = [
            'channel' => $this->config['channel'] ?? '#errors',
            'username' => $this->config['username'] ?? 'Error Bot',
            'icon_emoji' => $this->config['icon'] ?? ':warning:',
            'text' => ':rotating_light: *Critical Error Detected!*',
            'attachments' => [$attachment]
        ];

        $response = Http::timeout(10)->post($this->config['webhook_url'], $payload);

        if (!$response->successful()) {
            throw new \Exception("Slack webhook failed: " . $response->body());
        }
    }

    protected function buildAttachment(array $errorData): array
    {
        $attachment = [
            'color' => 'danger',
            'fallback' => "Error in {$errorData['app_name']}: {$errorData['message']}",
            'title' => $errorData['exception_class'],
            'text' => $errorData['message'],
            'ts' => strtotime($errorData['timestamp']),
            'fields' => [
                [
                    'title' => 'Application',
                    'value' => "{$errorData['app_name']} ({$errorData['app_environment']})",
                    'short' => true
                ],
                [
                    'title' => 'Location',
                    'value' => basename($errorData['file']) . ':' . $errorData['line'],
                    'short' => true
                ]
            ],
            'footer' => 'Laravel Error Notifier',
            'footer_icon' => 'https://laravel.com/img/favicon/favicon-32x32.png'
        ];

        // Add request information if available
        if (!empty($errorData['request'])) {
            $attachment['fields'][] = [
                'title' => 'Request',
                'value' => "{$errorData['request']['method']} {$errorData['request']['url']}",
                'short' => false
            ];
            
            $attachment['fields'][] = [
                'title' => 'IP Address',
                'value' => $errorData['request']['ip'] ?? 'Unknown',
                'short' => true
            ];
        }

        // Add user information if available
        if (!empty($errorData['user'])) {
            $attachment['fields'][] = [
                'title' => 'User',
                'value' => $errorData['user']['email'] ?? "ID: {$errorData['user']['id']}",
                'short' => true
            ];
        }

        // Add context if available
        if (!empty($errorData['context'])) {
            $contextStr = json_encode($errorData['context'], JSON_PRETTY_PRINT);
            $attachment['fields'][] = [
                'title' => 'Context',
                'value' => "```\n" . substr($contextStr, 0, 500) . "\n```",
                'short' => false
            ];
        }

        return $attachment;
    }

    public function getName(): string
    {
        return 'slack';
    }
}