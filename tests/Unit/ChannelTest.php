<?php

namespace Mktmsameera\LaravelErrorNotifier\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mktmsameera\LaravelErrorNotifier\Channels\DiscordChannel;
use Mktmsameera\LaravelErrorNotifier\Channels\EmailChannel;
use Mktmsameera\LaravelErrorNotifier\Channels\SlackChannel;
use Mktmsameera\LaravelErrorNotifier\Channels\WhatsAppChannel;
use Mktmsameera\LaravelErrorNotifier\Tests\TestCase;

class ChannelTest extends TestCase
{
    /** @test */
    public function discord_channel_is_disabled_without_webhook()
    {
        $channel = new DiscordChannel(['enabled' => true]);
        
        $this->assertFalse($channel->isEnabled());
    }

    /** @test */
    public function discord_channel_is_enabled_with_webhook()
    {
        $channel = new DiscordChannel([
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test'
        ]);
        
        $this->assertTrue($channel->isEnabled());
    }

    /** @test */
    public function discord_channel_sends_notification()
    {
        Http::fake([
            'discord.com/*' => Http::response([], 204)
        ]);

        $channel = new DiscordChannel([
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
            'username' => 'Test Bot'
        ]);

        $errorData = $this->createTestErrorData();
        
        $channel->send($errorData);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.com/api/webhooks/test' &&
                   $request['username'] === 'Test Bot' &&
                   isset($request['embeds'][0]['title']);
        });
    }

    /** @test */
    public function discord_channel_includes_mention_role_in_payload()
    {
        Http::fake([
            'discord.com/*' => Http::response([], 204)
        ]);

        $channel = new DiscordChannel([
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
            'mention_role' => '123456789012345678'
        ]);

        $errorData = $this->createTestErrorData();
        $channel->send($errorData);
        
        Http::assertSent(function ($request) {
            return isset($request['content']) &&
                   strpos($request['content'], '<@&123456789012345678>') !== false;
        });
    }

    /** @test */
    public function discord_channel_throws_exception_on_webhook_failure()
    {
        Http::fake([
            'discord.com/*' => Http::response(['error' => 'Invalid webhook'], 400)
        ]);

        $channel = new DiscordChannel([
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Discord webhook failed');

        $errorData = $this->createTestErrorData();
        $channel->send($errorData);
    }

    /** @test */
    public function slack_channel_is_disabled_without_webhook()
    {
        $channel = new SlackChannel(['enabled' => true]);
        
        $this->assertFalse($channel->isEnabled());
    }

    /** @test */
    public function slack_channel_is_enabled_with_webhook()
    {
        $channel = new SlackChannel([
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/test'
        ]);
        
        $this->assertTrue($channel->isEnabled());
    }

    /** @test */
    public function slack_channel_sends_notification()
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200)
        ]);

        $channel = new SlackChannel([
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/test',
            'channel' => '#errors',
            'username' => 'Error Bot'
        ]);

        $errorData = $this->createTestErrorData();
        
        $channel->send($errorData);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/test' &&
                   $request['channel'] === '#errors' &&
                   $request['username'] === 'Error Bot' &&
                   isset($request['attachments'][0]);
        });
    }

    /** @test */
    public function slack_channel_includes_context_in_attachment()
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200)
        ]);

        $channel = new SlackChannel([
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/test'
        ]);

        $errorData = $this->createTestErrorData();
        $errorData['context'] = ['order_id' => 12345, 'user_action' => 'checkout'];
        
        $channel->send($errorData);
        
        Http::assertSent(function ($request) {
            $contextField = collect($request['attachments'][0]['fields'])
                           ->firstWhere('title', 'Context');
            
            return $contextField !== null &&
                   strpos($contextField['value'], 'order_id') !== false;
        });
    }

    /** @test */
    public function slack_channel_throws_exception_on_webhook_failure()
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('invalid_payload', 400)
        ]);

        $channel = new SlackChannel([
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/test'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slack webhook failed');

        $errorData = $this->createTestErrorData();
        $channel->send($errorData);
    }

    /** @test */
    public function whatsapp_channel_is_disabled_without_credentials()
    {
        $channel = new WhatsAppChannel(['enabled' => true]);
        
        $this->assertFalse($channel->isEnabled());
    }

    /** @test */
    public function whatsapp_channel_is_enabled_with_twilio_credentials()
    {
        $channel = new WhatsAppChannel([
            'enabled' => true,
            'provider' => 'twilio',
            'twilio' => [
                'sid' => 'test_sid',
                'token' => 'test_token',
                'from' => 'whatsapp:+14155238886',
                'to' => 'whatsapp:+1234567890'
            ]
        ]);
        
        $this->assertTrue($channel->isEnabled());
    }

    /** @test */
    public function whatsapp_channel_is_enabled_with_business_api_credentials()
    {
        $channel = new WhatsAppChannel([
            'enabled' => true,
            'provider' => 'whatsapp-business',
            'whatsapp_business' => [
                'token' => 'test_token',
                'phone_id' => 'test_phone_id',
                'to' => '1234567890'
            ]
        ]);
        
        $this->assertTrue($channel->isEnabled());
    }

    /** @test */
    public function whatsapp_channel_sends_notification_via_twilio()
    {
        $twilioMock = \Mockery::mock('overload:Twilio\Rest\Client');
        $messagesMock = \Mockery::mock();
        
        $twilioMock->shouldReceive('__construct')
                   ->with('test_sid', 'test_token')
                   ->once();
        
        $twilioMock->messages = $messagesMock;
        
        $messagesMock->shouldReceive('create')
                     ->with('whatsapp:+1234567890', \Mockery::on(function ($args) {
                         return $args['from'] === 'whatsapp:+14155238886' &&
                                strpos($args['body'], 'SERVER ERROR ALERT') !== false;
                     }))
                     ->once();

        $channel = new WhatsAppChannel([
            'enabled' => true,
            'provider' => 'twilio',
            'twilio' => [
                'sid' => 'test_sid',
                'token' => 'test_token',
                'from' => 'whatsapp:+14155238886',
                'to' => 'whatsapp:+1234567890'
            ]
        ]);

        $errorData = $this->createTestErrorData();
        
        $channel->send($errorData);
    }

    /** @test */
    public function whatsapp_channel_sends_notification_via_business_api()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'msg_123']]], 200)
        ]);

        $channel = new WhatsAppChannel([
            'enabled' => true,
            'provider' => 'whatsapp-business',
            'whatsapp_business' => [
                'token' => 'test_token',
                'phone_id' => 'test_phone_id',
                'to' => '1234567890'
            ]
        ]);

        $errorData = $this->createTestErrorData();
        
        $channel->send($errorData);
        
        Http::assertSent(function ($request) {
            return strpos($request->url(), 'graph.facebook.com') !== false &&
                   $request->header('Authorization')[0] === 'Bearer test_token' &&
                   $request['messaging_product'] === 'whatsapp' &&
                   $request['to'] === '1234567890' &&
                   isset($request['text']['body']);
        });
    }

    /** @test */
    public function whatsapp_channel_throws_exception_for_invalid_provider()
    {
        $channel = new WhatsAppChannel([
            'enabled' => true,
            'provider' => 'invalid_provider'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported WhatsApp provider: invalid_provider');

        $errorData = $this->createTestErrorData();
        $channel->send($errorData);
    }

    /** @test */
    public function email_channel_is_disabled_without_recipients()
    {
        $channel = new EmailChannel(['enabled' => true]);
        
        $this->assertFalse($channel->isEnabled());
    }

    /** @test */
    public function email_channel_is_enabled_with_recipients()
    {
        $channel = new EmailChannel([
            'enabled' => true,
            'to' => ['test@example.com'],
            'from' => 'errors@example.com'
        ]);
        
        $this->assertTrue($channel->isEnabled());
    }

    /** @test */
    public function email_channel_sends_notification()
    {
        Mail::fake();

        $channel = new EmailChannel([
            'enabled' => true,
            'to' => ['test@example.com', 'admin@example.com'],
            'from' => 'errors@example.com',
            'subject_prefix' => '[TEST ERROR]'
        ]);

        $errorData = $this->createTestErrorData();
        
        $channel->send($errorData);
        
        Mail::assertSent(\Mktmsameera\LaravelErrorNotifier\Mail\ErrorNotificationMail::class, 2);
    }

    /** @test */
    public function email_channel_throws_exception_with_no_recipients()
    {
        $channel = new EmailChannel([
            'enabled' => true,
            'to' => [],
            'from' => 'errors@example.com'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No email recipients configured');

        $errorData = $this->createTestErrorData();
        $channel->send($errorData);
    }

    /** @test */
    public function email_channel_handles_string_recipients()
    {
        Mail::fake();

        $channel = new EmailChannel([
            'enabled' => true,
            'to' => 'single@example.com',  // String instead of array
            'from' => 'errors@example.com'
        ]);

        $errorData = $this->createTestErrorData();
        $channel->send($errorData);
        
        Mail::assertSent(\Mktmsameera\LaravelErrorNotifier\Mail\ErrorNotificationMail::class, 1);
    }

    /** @test */
    public function channels_return_correct_names()
    {
        $discord = new DiscordChannel([]);
        $slack = new SlackChannel([]);
        $whatsapp = new WhatsAppChannel([]);
        $email = new EmailChannel([]);

        $this->assertEquals('discord', $discord->getName());
        $this->assertEquals('slack', $slack->getName());
        $this->assertEquals('whatsapp', $whatsapp->getName());
        $this->assertEquals('email', $email->getName());
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}