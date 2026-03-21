<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class BusinessRuleException extends AppException
{
    public function __construct(
        private readonly string $errorCode,
        private readonly string $safeMsg,
        string $internalMessage = '',
        private readonly ?string $field = null
    ) {
        parent::__construct($internalMessage !== '' ? $internalMessage : $safeMsg);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return 409;
    }

    public function getSafeMessage(): string
    {
        return $this->safeMsg;
    }

    public function getField(): ?string
    {
        return $this->field;
    }
}
