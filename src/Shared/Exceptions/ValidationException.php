<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

/**
 * Excepcion utilizada para errores de validación de campos en los requests.
 *
 * Se deben mapear los campos de error al array $errors y devolver la informacion al cliente
 */
class ValidationException extends AppException
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

    public function getErrorCode(): string
    {
        return 'VALIDATION_ERROR';
    }

    public function getHttpStatus(): int
    {
        return 400;
    }

    public function getSafeMessage(): string
    {
        return 'Los datos ingresados no son válidos';
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
