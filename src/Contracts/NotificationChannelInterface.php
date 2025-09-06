<?php

namespace Mktmsameera\LaravelErrorNotifier\Contracts;

interface NotificationChannelInterface
{
    /**
     * Check if the channel is enabled and properly configured
     */
    public function isEnabled(): bool;

    /**
     * Send the error notification
     */
    public function send(array $errorData): void;

    /**
     * Get the channel name
     */
    public function getName(): string;
}