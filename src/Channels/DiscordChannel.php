<?php

namespace Mktmsameera\LaravelErrorNotifier\Channels;

use Illuminate\Support\Facades\Http;
use Mktmsameera\LaravelErrorNotifier\Contracts\NotificationChannelInterface;

class DiscordChannel implements NotificationChannelInterface
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
        $embed = $this->buildEmbed($errorData);
        
        $payload = [
            'username' => $this->config['username'] ?? 'Error Bot',
            'embeds' => [$embed]
        ];

        // Add mention if role ID is provided
        if (!empty($this->config['mention_role'])) {
            $payload['content'] = "<@&{$this->config['mention_role']}> Critical error detected!";
        }

        $response = Http::timeout(10)->post($this->config['webhook_url'], $payload);

        if (!$response->successful()) {
            throw new \Exception("Discord webhook failed: " . $response->body());
        }
    }

    protected function buildEmbed(array $errorData): array
    {
        $embed = [
            'title' => '🚨 Server Error Alert',
            'color' => 15158332, // Red color
            'timestamp' => $errorData['timestamp'],
            'fields' => [
                [
                    'name' => 'Application',
                    'value' => "{$errorData['app_name']} ({$errorData['app_environment']})",
                    'inline' => true
                ],
                [
                    'name' => 'Exception',
                    'value' => $errorData['exception_class'],
                    'inline' => true
                ],
                [
                    'name' => 'Message',
                    'value' => substr($errorData['message'], 0, 1024),
                    'inline' => false
                ],
                [
                    'name' => 'Location',
                    'value' => basename($errorData['file']) . ':' . $errorData['line'],
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => 'Laravel Error Notifier'
            ]
        ];

        // Add request information if available
        if (!empty($errorData['request'])) {
            $embed['fields'][] = [
                'name' => 'Request',
                'value' => "{$errorData['request']['method']} {$errorData['request']['url']}",
                'inline' => false
            ];
            
            $embed['fields'][] = [
                'name' => 'IP Address',
                'value' => $errorData['request']['ip'] ?? 'Unknown',
                'inline' => true
            ];
        }

        // Add user information if available
        if (!empty($errorData['user'])) {
            $embed['fields'][] = [
                'name' => 'User',
                'value' => $errorData['user']['email'] ?? "ID: {$errorData['user']['id']}",
                'inline' => true
            ];
        }

        // Add stack trace if available (truncated)
        if (!empty($errorData['stack_trace'])) {
            $stackTrace = substr($errorData['stack_trace'], 0, 800);
            $embed['fields'][] = [
                'name' => 'Stack Trace (truncated)',
                'value' => "```\n{$stackTrace}\n```",
                'inline' => false
            ];
        }

        return $embed;
    }

    public function getName(): string
    {
        return 'discord';
    }
}