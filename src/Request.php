<?php

namespace Interakt\Track;

use Interakt\Track\Exception\APIError;

class Request
{
    private const DEFAULT_HOST = 'https://api.interakt.ai';
    private static $handler;

    public static function setHandler(?callable $handler): void
    {
        self::$handler = $handler;
    }

    public static function post(string $apiKey, ?string $host, string $path, array $body, int $timeout = 10)
    {
        if (self::$handler !== null) {
            return call_user_func(self::$handler, $apiKey, $host, $path, $body, $timeout);
        }

        $url = Utils::removeTrailingSlash($host ?? self::DEFAULT_HOST) . $path;
        $payload = json_encode($body);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: interakt-track-php/1.0.0',
        ]);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new APIError('Unknown', 0, $error ?: 'Unable to send request');
        }

        if ($status >= 200 && $status <= 299) {
            return json_decode($response, true) ?? $response;
        }

        $payload = json_decode($response, true);
        throw new APIError(
            $payload['result'] ?? 'Unknown',
            $status,
            $payload['message'] ?? $response
        );
    }
}
