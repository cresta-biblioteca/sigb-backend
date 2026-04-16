<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class AlreadyExistsException extends AppException
{
    public function __construct(string $message, private readonly ?string $field = null)
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'ENTITY_ALREADY_EXISTS';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }

    public function getSafeMessage(): string
    {
        return $this->getMessage();
    }

    public function getField(): ?string
    {
        return $this->field;
    }
}
