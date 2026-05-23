<?php

namespace Interakt\Track;

use Interakt\Track\Exception\APIError;
use SplQueue;

class Client
{
    private string $apiKey;
    private bool $debug;
    private ?string $host;
    private bool $syncMode;
    private int $timeout;
    private $onError;
    private int $maxQueueSize;
    private SplQueue $queue;
    private ?Consumer $consumer = null;

    public function __construct(
        string $apiKey,
        ?string $host = null,
        bool $debug = false,
        bool $syncMode = false,
        int $timeout = 10,
        int $maxQueueSize = 10000,
        ?callable $onError = null,
        int $maxRetries = 3
    ) {
        Utils::requireType('api_key', $apiKey, 'string');

        $this->apiKey = $apiKey;
        $this->debug = $debug;
        $this->host = $host;
        $this->syncMode = $syncMode;
        $this->timeout = $timeout;
        $this->onError = $onError;
        $this->maxQueueSize = $maxQueueSize;
        $this->queue = new SplQueue();

        if (!$this->syncMode) {
            register_shutdown_function([$this, 'shutdown']);
            $this->consumer = new Consumer(
                $this->apiKey,
                $this->host,
                $this->onError,
                $maxRetries,
                $this->timeout
            );
        }
    }

    public function user(?string $userId = null, string $countryCode = '+91', ?string $phoneNumber = null, array $traits = [])
    {
        if ($userId === null && $phoneNumber === null) {
            throw new \InvalidArgumentException('Either user_id or phone_number is required');
        }

        if ($userId !== null) {
            Utils::requireType('user_id', $userId, 'string');
        }

        if ($phoneNumber !== null) {
            Utils::requireType('country_code', $countryCode, 'string');
            Utils::verifyCountryCode($countryCode);
            Utils::requireType('phone_number', $phoneNumber, 'string');
            if (!ctype_digit($phoneNumber)) {
                throw new \InvalidArgumentException(sprintf('Invalid phone_number %s', $phoneNumber));
            }
        }

        Utils::requireType('traits', $traits, 'array');

        $body = ['traits' => $traits];

        if ($userId !== null) {
            $body['userId'] = Utils::stringify($userId);
        }

        if ($phoneNumber !== null) {
            $body['countryCode'] = $countryCode;
            $body['phoneNumber'] = $phoneNumber;
        }

        return $this->queueRequest(ApiPaths::USER, $body);
    }

    public function event(?string $userId = null, ?string $event = null, array $traits = [], string $countryCode = '+91', ?string $phoneNumber = null)
    {
        $traits = $traits ?? [];

        if ($userId === null && $phoneNumber === null) {
            throw new \InvalidArgumentException('Either user_id or phone_number is required');
        }

        if ($userId !== null) {
            Utils::requireType('user_id', $userId, 'string');
        }

        if ($phoneNumber !== null) {
            Utils::requireType('country_code', $countryCode, 'string');
            Utils::verifyCountryCode($countryCode);
            Utils::requireType('phone_number', $phoneNumber, 'string');
            if (!ctype_digit($phoneNumber)) {
                throw new \InvalidArgumentException(sprintf('Invalid phone_number %s', $phoneNumber));
            }
        }

        Utils::requireType('traits', $traits, 'array');
        Utils::requireType('event', $event, 'string');
        if ($event === null || $event === '') {
            throw new \InvalidArgumentException('event is required');
        }

        $body = [
            'event' => $event,
            'traits' => $traits,
        ];

        if ($userId !== null) {
            $body['userId'] = Utils::stringify($userId);
        }

        if ($phoneNumber !== null) {
            $body['countryCode'] = $countryCode;
            $body['phoneNumber'] = $phoneNumber;
        }

        return $this->queueRequest(ApiPaths::EVENT, $body);
    }

    public function message(array $body)
    {
        Utils::requireType('body', $body, 'array');
        return $this->queueRequest(ApiPaths::MESSAGE, $body);
    }

    public function sendTemplate(string $countryCode, string $phoneNumber, array $template, ?string $callbackData = null, ?string $campaignId = null)
    {
        Utils::requireType('country_code', $countryCode, 'string');
        Utils::verifyCountryCode($countryCode);
        Utils::requireType('phone_number', $phoneNumber, 'string');
        if (!ctype_digit($phoneNumber)) {
            throw new \InvalidArgumentException(sprintf('Invalid phone_number %s', $phoneNumber));
        }

        Utils::requireType('template', $template, 'array');

        $body = [
            'countryCode' => $countryCode,
            'phoneNumber' => $phoneNumber,
            'type' => 'Template',
            'template' => $template,
        ];

        if ($callbackData !== null) {
            Utils::requireType('callbackData', $callbackData, 'string');
            $body['callbackData'] = $callbackData;
        }

        if ($campaignId !== null) {
            Utils::requireType('campaignId', $campaignId, 'string');
            $body['campaignId'] = $campaignId;
        }

        return $this->queueRequest(ApiPaths::MESSAGE, $body);
    }

    public function flush(): void
    {
        if ($this->syncMode) {
            return;
        }

        while (!$this->queue->isEmpty()) {
            $queueMsg = $this->queue->dequeue();
            try {
                $this->consumer->request($queueMsg);
            } catch (\Throwable $e) {
                if ($this->onError) {
                    call_user_func($this->onError, $e, $queueMsg);
                }
            }
        }
    }

    public function join(): void
    {
        $this->flush();
    }

    public function shutdown(): void
    {
        $this->flush();
    }

    public function getQueueSize(): int
    {
        return $this->queue->count();
    }

    private function queueRequest(string $path, array $body)
    {
        if ($this->syncMode) {
            return Request::post($this->apiKey, $this->host, $path, $body, $this->timeout);
        }

        if ($this->queue->count() >= $this->maxQueueSize) {
            $queueMsg = ['path' => $path, 'body' => $body];
            return [false, $queueMsg];
        }

        $queueMsg = ['path' => $path, 'body' => $body];
        $this->queue->enqueue($queueMsg);
        if ($this->debug) {
            error_log(sprintf('Enqueued msg for %s', $path));
        }

        return [true, $queueMsg];
    }
}
