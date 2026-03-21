<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class AlreadyExistsException extends AppException
{
    public function __construct(
        private readonly string $entityType,
        string $field,
        mixed $value
    ) {
        parent::__construct(
            sprintf('%s con %s "%s" ya existe', $entityType, $field, (string) $value)
        );
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
        return sprintf('%s ya existe', $this->entityType);
    }
}
