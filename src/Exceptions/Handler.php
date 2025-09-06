<?php

namespace Mktmsameera\LaravelErrorNotifier\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Mktmsameera\LaravelErrorNotifier\ErrorNotifier;
use Throwable;

/**
 * Example Exception Handler that users can extend or use as reference
 * 
 * Users should either:
 * 1. Extend this class in their app/Exceptions/Handler.php
 * 2. Copy the report() method to their existing Handler
 * 3. Use the ErrorNotifier directly in their own exception handling
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        // Send error notification
        if (app()->bound(ErrorNotifier::class)) {
            try {
                app(ErrorNotifier::class)->notify($exception, [
                    'reported_at' => now()->toISOString(),
                    'session_id' => session()->getId(),
                ]);
            } catch (\Exception $e) {
                // Log the notification failure but don't break the app
                \Log::error('Error notifier failed: ' . $e->getMessage());
            }
        }

        parent::report($exception);
    }
}