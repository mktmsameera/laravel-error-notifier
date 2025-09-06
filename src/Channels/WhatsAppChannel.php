<?php

namespace Mktmsameera\LaravelErrorNotifier\Channels;

use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client as TwilioClient;
use Mktmsameera\LaravelErrorNotifier\Contracts\NotificationChannelInterface;

class WhatsAppChannel implements NotificationChannelInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        if (!($this->config['enabled'] ?? false)) {
            return false;
        }

        $provider = $this->config['provider'] ?? 'twilio';
        
        if ($provider === 'twilio') {
            return !empty($this->config['twilio']['sid']) && 
                   !empty($this->config['twilio']['token']) &&
                   !empty($this->config['twilio']['from']) &&
                   !empty($this->config['twilio']['to']);
        }

        if ($provider === 'whatsapp-business') {
            return !empty($this->config['whatsapp_business']['token']) &&
                   !empty($this->config['whatsapp_business']['phone_id']) &&
                   !empty($this->config['whatsapp_business']['to']);
        }

        return false;
    }

    public function send(array $errorData): void
    {
        $provider = $this->config['provider'] ?? 'twilio';
        
        if ($provider === 'twilio') {
            $this->sendViaTwilio($errorData);
        } elseif ($provider === 'whatsapp-business') {
            $this->sendViaWhatsAppBusiness($errorData);
        } else {
            throw new \InvalidArgumentException("Unsupported WhatsApp provider: {$provider}");
        }
    }

    protected function sendViaTwilio(array $errorData): void
    {
        $twilioConfig = $this->config['twilio'];
        
        $client = new TwilioClient($twilioConfig['sid'], $twilioConfig['token']);
        
        $message = $this->buildMessage($errorData);
        
        $client->messages->create(
            $twilioConfig['to'],
            [
                'from' => $twilioConfig['from'],
                'body' => $message
            ]
        );
    }

    protected function sendViaWhatsAppBusiness(array $errorData): void
    {
        $businessConfig = $this->config['whatsapp_business'];
        
        $message = $this->buildMessage($errorData);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $businessConfig['to'],
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $businessConfig['token'],
            'Content-Type' => 'application/json'
        ])->timeout(10)->post(
            "https://graph.facebook.com/v17.0/{$businessConfig['phone_id']}/messages",
            $payload
        );

        if (!$response->successful()) {
            throw new \Exception("WhatsApp Business API failed: " . $response->body());
        }
    }

    protected function buildMessage(array $errorData): string
    {
        $message = "🚨 *SERVER ERROR ALERT* 🚨\n\n";
        $message .= "📱 *App:* {$errorData['app_name']} ({$errorData['app_environment']})\n";
        $message .= "⏰ *Time:* " . date('Y-m-d H:i:s', strtotime($errorData['timestamp'])) . "\n";
        $message .= "🔥 *Error:* {$errorData['exception_class']}\n\n";
        
        $message .= "💬 *Message:*\n";
        $message .= substr($errorData['message'], 0, 300) . "\n\n";
        
        $message .= "📍 *Location:*\n";
        $message .= basename($errorData['file']) . ":" . $errorData['line'] . "\n\n";

        // Add request information if available
        if (!empty($errorData['request'])) {
            $message .= "🌐 *Request:*\n";
            $message .= "{$errorData['request']['method']} {$errorData['request']['url']}\n";
            $message .= "📡 *IP:* {$errorData['request']['ip']}\n\n";
        }

        // Add user information if available
        if (!empty($errorData['user'])) {
            $message .= "👤 *User:* ";
            $message .= $errorData['user']['email'] ?? "ID: {$errorData['user']['id']}";
            $message .= "\n\n";
        }

        $message .= "🔧 *Laravel Error Notifier*";

        // WhatsApp has a 4096 character limit
        return substr($message, 0, 4000);
    }

    public function getName(): string
    {
        return 'whatsapp';
    }
}