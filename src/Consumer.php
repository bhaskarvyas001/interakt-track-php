<?php

namespace Interakt\Track;

use Interakt\Track\Exception\APIError;

class Consumer
{
    private string $apiKey;
    private ?string $host;
    private $onError;
    private int $retries;
    private int $timeout;

    public function __construct(
        string $apiKey,
        ?string $host = null,
        ?callable $onError = null,
        int $retries = 3,
        int $timeout = 10
    ) {
        $this->apiKey = $apiKey;
        $this->host = $host;
        $this->onError = $onError;
        $this->retries = $retries;
        $this->timeout = $timeout;
    }

    public function request(array $queueMsg): void
    {
        $attempt = 0;
        do {
            $attempt++;
            try {
                Request::post(
                    $this->apiKey,
                    $this->host,
                    $queueMsg['path'],
                    $queueMsg['body'],
                    $this->timeout
                );
                return;
            } catch (APIError $exception) {
                if ($this->isFatal($exception) || $attempt > $this->retries) {
                    throw $exception;
                }
            } catch (\Throwable $exception) {
                if ($attempt > $this->retries) {
                    throw $exception;
                }
            }

            if ($attempt <= $this->retries) {
                sleep((int) pow(2, $attempt - 1));
            }
        } while ($attempt <= $this->retries);
    }

    private function isFatal(APIError $exception): bool
    {
        $status = $exception->getStatusCode();
        return $status >= 400 && $status < 500 && $status !== 429;
    }
}
