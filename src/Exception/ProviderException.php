<?php

declare(strict_types=1);

namespace Eram\Ersal\Exception;

/**
 * Thrown when a shipping provider returns an error response.
 */
class ProviderException extends ErsalException
{
    public function __construct(
        string $message,
        private readonly string $providerName,
        private readonly int|string $errorCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, \is_int($errorCode) ? $errorCode : 0, $previous);
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getErrorCode(): int|string
    {
        return $this->errorCode;
    }
}
