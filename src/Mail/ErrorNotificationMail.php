<?php

namespace Mktmsameera\LaravelErrorNotifier\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErrorNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected array $errorData;
    protected array $config;

    public function __construct(array $errorData, array $config)
    {
        $this->errorData = $errorData;
        $this->config = $config;
    }

    public function build()
    {
        $subjectPrefix = $this->config['subject_prefix'] ?? '[ERROR]';
        $subject = "{$subjectPrefix} {$this->errorData['app_name']} - {$this->errorData['exception_class']}";

        return $this->from($this->config['from'])
                    ->subject($subject)
                    ->view('error-notifier::email.error-notification')
                    ->with([
                        'errorData' => $this->errorData,
                        'config' => $this->config,
                    ]);
    }
}