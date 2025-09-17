# Laravel Error Notifier

A comprehensive Laravel package for sending error notifications to multiple platforms including Discord, Slack, WhatsApp, and Email. Get instant alerts when your application encounters critical errors. [Package Documentation](https://packagist.org/packages/mktmsameera/laravel-error-notifier)

## Features

- 🚨 **Multi-platform notifications**: Discord, Slack, WhatsApp, Email
- ⚡ **Fail-safe design**: If one service fails, others continue working
- 🎛️ **Highly configurable**: Enable/disable channels, customize messages
- 📊 **Rate limiting**: Prevent notification spam
- 🔄 **Queue support**: Async notifications for better performance
- 🧪 **Built-in testing**: Test your notification channels easily
- 🎯 **Environment filtering**: Different settings per environment
- 📝 **Rich formatting**: Platform-specific message formatting
- 🔍 **Full exception details**: Complete error context and stack traces

## Installation

Install the package via Composer:

```bash
composer require mktmsameera/laravel-error-notifier
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=error-notifier-config
```

## Quick Setup

### 1. Configure Your Channels

Edit `config/error-notifier.php` and enable your desired notification channels:

```php
'channels' => [
    'discord' => [
        'enabled' => env('DISCORD_ENABLED', false),
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    ],
    'slack' => [
        'enabled' => env('SLACK_ENABLED', false),
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'),
        // ... provider specific config
    ],
    'email' => [
        'enabled' => env('EMAIL_ENABLED', false),
        'to' => explode(',', env('ERROR_EMAIL_TO', '')),
        'from' => env('ERROR_EMAIL_FROM'),
    ],
],
```

### 2. Set Environment Variables

Add your credentials to `.env`:

```env
# Discord
DISCORD_ENABLED=true
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/your-webhook-url

# Slack
SLACK_ENABLED=true
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/your-webhook-url

# WhatsApp (Twilio)
WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=twilio
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
TWILIO_WHATSAPP_TO=whatsapp:+1234567890

# Email
EMAIL_ENABLED=true
ERROR_EMAIL_TO=admin@yourapp.com,dev@yourapp.com
ERROR_EMAIL_FROM=errors@yourapp.com
```

### 3. Update Your Exception Handler

Extend the provided exception handler or add the notification to your existing handler:

**Option A: Extend the provided handler**

```php
// app/Exceptions/Handler.php
<?php

namespace App\Exceptions;

use Mktmsameera\LaravelErrorNotifier\Exceptions\Handler as BaseHandler;

class Handler extends BaseHandler
{
    // Your existing handler code...
}
```

**Option B: Add to your existing handler**

```php
// app/Exceptions/Handler.php
use Mktmsameera\LaravelErrorNotifier\ErrorNotifier;

public function report(Throwable $exception)
{
    // Send error notification
    if (app()->bound(ErrorNotifier::class)) {
        try {
            app(ErrorNotifier::class)->notify($exception);
        } catch (\Exception $e) {
            \Log::error('Error notifier failed: ' . $e->getMessage());
        }
    }

    parent::report($exception);
}
```

### 4. Test Your Setup

Test all enabled channels:

```bash
php artisan error-notifier:test --all
```

Test a specific channel:

```bash
php artisan error-notifier:test discord
php artisan error-notifier:test slack
php artisan error-notifier:test whatsapp
php artisan error-notifier:test email
```

## Configuration

### Environment Filtering

Only send notifications for specific environments:

```php
'environments' => [
    'production',
    'staging',
],
```

### Rate Limiting

Prevent spam by limiting identical errors:

```php
'rate_limit' => [
    'enabled' => true,
    'throttle_seconds' => 300, // 5 minutes
],
```

### Exception Filtering

Control which exceptions trigger notifications:

```php
'filters' => [
    'include_status_codes' => [500, 503, 504],
    'exclude_exceptions' => [
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
    ],
],
```

### Queue Configuration

Send notifications asynchronously:

```php
'queue' => [
    'enabled' => true,
    'connection' => 'redis',
    'queue' => 'notifications',
],
```

## Channel Setup Guides

### Discord Setup

1. Go to your Discord server
2. Right-click on a channel → Settings → Integrations → Webhooks
3. Create a new webhook and copy the URL
4. Add to your `.env`:

```env
DISCORD_ENABLED=true
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/your-webhook-url
DISCORD_USERNAME="Error Bot"
DISCORD_MENTION_ROLE=123456789012345678  # Optional: Role ID to mention
```

### Slack Setup

1. Go to your Slack workspace
2. Create a new app at api.slack.com
3. Enable Incoming Webhooks and create a webhook
4. Add to your `.env`:

```env
SLACK_ENABLED=true
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/your-webhook-url
SLACK_CHANNEL="#errors"
SLACK_USERNAME="Error Bot"
```

### WhatsApp Setup (Twilio)

1. Create a Twilio account at twilio.com
2. Set up WhatsApp sandbox or get approved for production
3. Add to your `.env`:

```env
WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=twilio
TWILIO_SID=your_account_sid
TWILIO_TOKEN=your_auth_token
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
TWILIO_WHATSAPP_TO=whatsapp:+1234567890
```

### WhatsApp Setup (Business API)

1. Set up Facebook Business account and WhatsApp Business API
2. Get your access token and phone number ID
3. Add to your `.env`:

```env
WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=whatsapp-business
WHATSAPP_BUSINESS_TOKEN=your_access_token
WHATSAPP_BUSINESS_PHONE_ID=your_phone_number_id
WHATSAPP_BUSINESS_TO=1234567890
```

### Email Setup

Uses Laravel's built-in mail configuration:

```env
EMAIL_ENABLED=true
ERROR_EMAIL_TO=admin@yourapp.com,dev@yourapp.com
ERROR_EMAIL_FROM=errors@yourapp.com
ERROR_EMAIL_SUBJECT_PREFIX="[URGENT ERROR]"
```

## Manual Usage

You can also use the error notifier manually:

```php
use Mktmsameera\LaravelErrorNotifier\ErrorNotifier;

// In a controller or service
try {
    // Your code that might throw an exception
    riskyOperation();
} catch (\Exception $e) {
    // Send notification with custom context
    app(ErrorNotifier::class)->notify($e, [
        'user_action' => 'processing_payment',
        'order_id' => 12345,
    ]);
    
    // Re-throw or handle as needed
    throw $e;
}
```

## Advanced Configuration

### Custom Exception Handler Integration

For more control over when notifications are sent:

```php
public function report(Throwable $exception)
{
    // Only notify for critical errors in production
    if (app()->isProduction() && $this->isCriticalError($exception)) {
        app(ErrorNotifier::class)->notify($exception, [
            'severity' => 'critical',
            'requires_immediate_attention' => true,
        ]);
    }

    parent::report($exception);
}

private function isCriticalError(Throwable $exception): bool
{
    return $exception instanceof \Exception &&
           !$exception instanceof \Illuminate\Validation\ValidationException;
}
```

### Environment-Specific Configuration

Create different configurations for different environments:

```php
// config/error-notifier.php
'channels' => [
    'discord' => [
        'enabled' => env('DISCORD_ENABLED', false),
        'webhook_url' => env('APP_ENV') === 'production' 
            ? env('DISCORD_WEBHOOK_PROD') 
            : env('DISCORD_WEBHOOK_STAGING'),
    ],
],
```

## Testing

Run the package tests:

```bash
composer test
```

Test your notification setup:

```bash
# Test all channels
php artisan error-notifier:test --all

# Test specific channel
php artisan error-notifier:test discord

# Test with verbose output
php artisan error-notifier:test slack -v
```

## Troubleshooting

### Common Issues

1. **Notifications not sending**
   - Check that the package is enabled: `ERROR_NOTIFIER_ENABLED=true`
   - Verify you're in the correct environment
   - Check logs for any error messages

2. **WhatsApp not working**
   - Ensure your Twilio sandbox is set up correctly
   - Verify phone numbers are in the correct format
   - Check Twilio account balance

3. **Discord/Slack webhooks failing**
   - Verify webhook URLs are correct and active
   - Check channel permissions
   - Test webhooks manually with curl

4. **Email notifications not sending**
   - Verify Laravel mail configuration
   - Check email credentials and SMTP settings
   - Ensure recipient emails are valid

### Debug Mode

Enable debug logging to troubleshoot issues:

```php
// In your exception handler
\Log::debug('Error notifier triggered', [
    'exception' => get_class($exception),
    'message' => $exception->getMessage(),
]);
```

## Security Considerations

- Store all credentials in environment variables, never in code
- Use rate limiting to prevent notification spam
- Consider which information to include in notifications (avoid sensitive data)
- Regularly rotate API keys and tokens
- Use HTTPS for all webhook URLs

## Contributing

Contributions are welcome! Please read our contributing guide and submit pull requests to our GitHub repository.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Support

- 📖 [Documentation](https://github.com/mktmsameera/laravel-error-notifier)
- 🐛 [Issue Tracker](https://github.com/mktmsameera/laravel-error-notifier/issues)
- 💬 [Discussions](https://github.com/mktmsameera/laravel-error-notifier/discussions)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
