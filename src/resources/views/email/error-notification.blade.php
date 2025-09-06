<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 20px -30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .alert-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #dc3545;
            background: #f8f9fa;
        }
        .section h3 {
            margin: 0 0 10px 0;
            color: #dc3545;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .info-item strong {
            color: #495057;
            display: block;
            margin-bottom: 5px;
        }
        .code-block {
            background: #f1f3f4;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="alert-icon">🚨</div>
            <h1>Server Error Alert</h1>
            <p>Critical error detected in your application</p>
        </div>

        <div class="section">
            <h3>Error Details</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Application:</strong>
                    {{ $errorData['app_name'] }} ({{ $errorData['app_environment'] }})
                </div>
                <div class="info-item">
                    <strong>Exception:</strong>
                    {{ $errorData['exception_class'] }}
                </div>
                <div class="info-item">
                    <strong>Time:</strong>
                    {{ date('Y-m-d H:i:s T', strtotime($errorData['timestamp'])) }}
                </div>
                <div class="info-item">
                    <strong>Location:</strong>
                    {{ basename($errorData['file']) }}:{{ $errorData['line'] }}
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Error Message</h3>
            <div class="code-block">{{ $errorData['message'] ?: 'No error message available' }}</div>
        </div>

        @if(!empty($errorData['request']))
        <div class="section">
            <h3>Request Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>URL:</strong>
                    {{ $errorData['request']['url'] ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>Method:</strong>
                    {{ $errorData['request']['method'] ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>IP Address:</strong>
                    {{ $errorData['request']['ip'] ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>User Agent:</strong>
                    {{ Str::limit($errorData['request']['user_agent'] ?? 'N/A', 50) }}
                </div>
            </div>
        </div>
        @endif

        @if(!empty($errorData['user']))
        <div class="section">
            <h3>User Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>User ID:</strong>
                    {{ $errorData['user']['id'] ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    {{ $errorData['user']['email'] ?? 'N/A' }}
                </div>
            </div>
        </div>
        @endif

        <div class="section">
            <h3>File Location</h3>
            <div class="code-block">{{ $errorData['file'] }}:{{ $errorData['line'] }}</div>
        </div>

        @if(!empty($errorData['stack_trace']))
        <div class="section">
            <h3>Stack Trace</h3>
            <div class="code-block">{{ $errorData['stack_trace'] }}</div>
        </div>
        @endif

        @if(!empty($errorData['context']))
        <div class="section">
            <h3>Additional Context</h3>
            <div class="code-block">{{ json_encode($errorData['context'], JSON_PRETTY_PRINT) }}</div>
        </div>
        @endif

        <div class="footer">
            <p>This notification was sent by <strong>Laravel Error Notifier</strong></p>
            <p>Generated at {{ date('Y-m-d H:i:s T') }}</p>
        </div>
    </div>
</body>
</html>