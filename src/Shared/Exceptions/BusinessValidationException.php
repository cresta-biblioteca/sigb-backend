<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

class BusinessValidationException extends RuntimeException
{
    private string $field;

    public function __construct(string $field, string $message)
    {
        parent::__construct($message);
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public static function forField(string $field, string $message): self
    {
        return new self($field, $message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'field' => $this->field,
        ];
    }
}
