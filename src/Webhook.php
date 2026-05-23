<?php

namespace Interakt\Track;

class Webhook
{
    public static function computeSignature(string $secret, $payload): string
    {
        if (is_string($payload)) {
            $data = $payload;
        } else {
            $data = json_encode($payload);
        }

        $hexHash = hash_hmac('sha256', $data, utf8_encode($secret));
        return 'sha256=' . $hexHash;
    }

    public static function verify(string $secret, $payload, string $signature): bool
    {
        $computed = self::computeSignature($secret, $payload);
        return hash_equals($computed, $signature);
    }
}
