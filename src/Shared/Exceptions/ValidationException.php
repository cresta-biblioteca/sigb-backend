<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Exception;

class ValidationException extends Exception
{
    /** @var array<string, string[]> */
    private array $errors;

    /**
     * @param array<string, string[]> $errors Errores por campo
     */
    public function __construct(array $errors, string $message = 'Error de validación')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Crea una excepción con un solo error de campo
     */
    public static function forField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }

    /**
     * Serializa los errores para respuesta API
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ];
    }
}
