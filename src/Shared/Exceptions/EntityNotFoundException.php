<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Exception;

class EntityNotFoundException extends Exception
{
    private string $entityType;
    private mixed $identifier;

    public function __construct(string $entityType, mixed $identifier)
    {
        $this->entityType = $entityType;
        $this->identifier = $identifier;

        parent::__construct(
            sprintf('%s con identificador "%s" no encontrado', $entityType, (string) $identifier)
        );
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'entity' => $this->entityType,
            'identifier' => $this->identifier,
        ];
    }
}
