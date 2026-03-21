<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(
        private readonly string $entityType,
        private readonly mixed $identifier
    ) {
        parent::__construct(
            sprintf('%s con identificador "%s" no encontrado', $entityType, (string) $identifier)
        );
    }

    public function getErrorCode(): string
    {
        return 'ENTITY_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }

    public function getSafeMessage(): string
    {
        return sprintf('%s no encontrado', $this->entityType);
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }
}
