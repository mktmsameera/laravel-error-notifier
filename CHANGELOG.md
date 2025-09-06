# Changelog

All notable changes to `laravel-error-notifier` will be documented in this file.

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- Support for Discord notifications via webhooks
- Support for Slack notifications via webhooks  
- Support for WhatsApp notifications via Twilio and WhatsApp Business API
- Support for Email notifications using Laravel's mail system
- Fail-safe design - if one channel fails, others continue working
- Rate limiting to prevent notification spam
- Environment filtering for different deployment stages
- Exception filtering to exclude certain types of errors
- Queue support for asynchronous notification sending
- Comprehensive error context including:
  - Exception details and stack traces
  - Request information (URL, method, IP, user agent)
  - User information (if authenticated)
  - Application information
  - Custom context data
- Rich message formatting specific to each platform
- Built-in testing commands for all channels
- Comprehensive test suite
- Detailed documentation and setup guides

### Features
- **Multi-platform Support**: Send notifications to Discord, Slack, WhatsApp, and Email
- **Robust Error Handling**: Isolated channel failures won't affect other channels
- **Flexible Configuration**: Enable/disable channels, customize message formats
- **Rate Limiting**: Prevent duplicate notifications for the same error
- **Queue Integration**: Async processing for better performance
- **Environment Awareness**: Different settings for production, staging, development
- **Exception Filtering**: Include/exclude specific error types or HTTP status codes
- **Testing Tools**: Artisan commands to test individual channels or all at once
- **Laravel Integration**: Seamless integration with Laravel's exception handling

## [Unreleased]

### Planned Features
- Support for Microsoft Teams notifications
- Support for Telegram notifications
- Support for SMS notifications via multiple providers
- Custom notification templates
- Notification scheduling and batching
- Metrics and analytics dashboard
- Integration with popular monitoring services (Sentry, Bugsnag, etc.)
- Support for Laravel 11.x
- Performance optimizations
- Additional customization options