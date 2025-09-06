<?php

namespace Mktmsameera\LaravelErrorNotifier\Commands;

use Illuminate\Console\Command;
use Mktmsameera\LaravelErrorNotifier\ErrorNotifier;

class TestNotificationCommand extends Command
{
    protected $signature = 'error-notifier:test 
                           {channel? : The notification channel to test (discord, slack, whatsapp, email)}
                           {--all : Test all enabled channels}';

    protected $description = 'Test error notification channels';

    public function handle()
    {
        $errorNotifier = app(ErrorNotifier::class);
        
        if ($this->option('all')) {
            $this->testAllChannels($errorNotifier);
            return;
        }

        $channel = $this->argument('channel');
        
        if (!$channel) {
            $channel = $this->choice(
                'Which channel would you like to test?',
                ['discord', 'slack', 'whatsapp', 'email', 'all'],
                0
            );
        }

        if ($channel === 'all') {
            $this->testAllChannels($errorNotifier);
            return;
        }

        $this->testSingleChannel($errorNotifier, $channel);
    }

    protected function testAllChannels(ErrorNotifier $errorNotifier): void
    {
        $channels = ['discord', 'slack', 'whatsapp', 'email'];
        
        $this->info('Testing all enabled channels...');
        $this->newLine();

        foreach ($channels as $channel) {
            $this->testSingleChannel($errorNotifier, $channel, false);
        }

        $this->newLine();
        $this->info('✅ All channel tests completed!');
    }

    protected function testSingleChannel(ErrorNotifier $errorNotifier, string $channel, bool $showHeader = true): void
    {
        if ($showHeader) {
            $this->info("Testing {$channel} channel...");
            $this->newLine();
        }

        try {
            $this->line("🔄 Testing {$channel}...");
            
            $success = $errorNotifier->testChannel($channel);
            
            if ($success) {
                $this->info("✅ {$channel} channel test successful!");
            }
            
        } catch (\InvalidArgumentException $e) {
            $this->error("❌ {$channel} channel not found: " . $e->getMessage());
        } catch (\RuntimeException $e) {
            $this->warn("⚠️  {$channel} channel is not enabled: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->error("❌ {$channel} channel test failed: " . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
        }

        if ($showHeader) {
            $this->newLine();
        }
    }
}