<?php

namespace Interakt\Track;

class Track
{
    public static ?string $apiKey = null;
    public static ?string $host = null;
    public static bool $syncMode = false;
    public static bool $debug = false;
    public static int $timeout = 10;
    public static int $maxRetries = 3;
    public static int $maxQueueSize = 10000;
    public static $onError = null;

    private static ?Client $defaultClient = null;

    public static function user(?string $userId = null, string $countryCode = '+91', ?string $phoneNumber = null, array $traits = [])
    {
        return self::getClient()->user($userId, $countryCode, $phoneNumber, $traits);
    }

    public static function event(?string $userId = null, ?string $event = null, array $traits = [], string $countryCode = '+91', ?string $phoneNumber = null)
    {
        return self::getClient()->event($userId, $event, $traits, $countryCode, $phoneNumber);
    }

    public static function message(array $body)
    {
        return self::getClient()->message($body);
    }

    public static function sendTemplate(string $countryCode, string $phoneNumber, array $template, ?string $callbackData = null, ?string $campaignId = null)
    {
        return self::getClient()->sendTemplate($countryCode, $phoneNumber, $template, $callbackData, $campaignId);
    }

    public static function flush(): void
    {
        self::getClient()->flush();
    }

    public static function join(): void
    {
        self::getClient()->join();
    }

    public static function shutdown(): void
    {
        self::getClient()->shutdown();
    }

    public static function resetClient(): void
    {
        self::$defaultClient = null;
    }

    private static function getClient(): Client
    {
        if (self::$defaultClient === null) {
            if (self::$apiKey === null) {
                throw new \InvalidArgumentException('api_key is required');
            }

            self::$defaultClient = new Client(
                self::$apiKey,
                self::$host,
                self::$debug,
                self::$syncMode,
                self::$timeout,
                self::$maxQueueSize,
                self::$onError,
                self::$maxRetries
            );
        }

        return self::$defaultClient;
    }
}
