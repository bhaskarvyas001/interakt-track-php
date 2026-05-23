<?php

namespace Interakt\Track\Exception;

class APIError extends \Exception
{
    private string $status;
    private int $statusCode;

    public function __construct(string $status, int $statusCode, string $message)
    {
        parent::__construct($message, $statusCode);
        $this->status = $status;
        $this->statusCode = $statusCode;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function __toString(): string
    {
        return sprintf('[interakt-track] StatusCode(%d): %s (Success=%s)',
            $this->statusCode,
            $this->getMessage(),
            $this->status
        );
    }
}
