<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Exceptions\BusinessValidationException;
use DateTimeImmutable;

abstract class Entity
{
    protected ?int $id = null;
    protected ?DateTimeImmutable $createdAt = null;
    protected ?DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Asigna el ID después de persistir
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Asigna timestamps desde la base de datos
     */
    protected function setTimestamps(?string $createdAt, ?string $updatedAt): void
    {
        $this->createdAt = $createdAt !== null
            ? new DateTimeImmutable($createdAt)
            : null;
        $this->updatedAt = $updatedAt !== null
            ? new DateTimeImmutable($updatedAt)
            : null;
    }

    /**
     * Valida que un valor no esté vacío
     *
     * @throws BusinessValidationException
     */
    protected function assertNotEmpty(mixed $value, string $field): void
    {
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            throw BusinessValidationException::forField($field, "El campo {$field} es requerido");
        }
    }

    /**
     * Valida la longitud máxima de un string
     *
     * @throws BusinessValidationException
     */
    protected function assertMaxLength(string $value, int $max, string $field): void
    {
        if (mb_strlen($value) > $max) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} no debe exceder {$max} caracteres"
            );
        }
    }

    /**
     * Valida la longitud exacta de un string
     *
     * @throws BusinessValidationException
     */
    protected function assertExactLength(string $value, int $length, string $field): void
    {
        if (mb_strlen($value) !== $length) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} debe tener exactamente {$length} caracteres"
            );
        }
    }

    /**
     * Valida la longitud mínima de un string
     *
     * @throws BusinessValidationException
     */
    protected function assertMinLength(string $value, int $min, string $field): void
    {
        if (mb_strlen($value) < $min) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} debe tener al menos {$min} caracteres"
            );
        }
    }

    /**
     * Valida que un número sea positivo (> 0)
     *
     * @throws BusinessValidationException
     */
    protected function assertPositive(int|float $value, string $field): void
    {
        if ($value <= 0) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} debe ser mayor a 0"
            );
        }
    }

    /**
     * Valida que un número sea no negativo (>= 0)
     *
     * @throws BusinessValidationException
     */
    protected function assertNonNegative(int|float $value, string $field): void
    {
        if ($value < 0) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} no puede ser negativo"
            );
        }
    }

    /**
     * Valida formato de email
     *
     * @throws BusinessValidationException
     */
    protected function assertValidEmail(string $value, string $field): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} debe ser un email válido"
            );
        }
    }

    /**
     * Valida que un valor esté en un array de opciones permitidas
     *
     * @param array<mixed> $allowed
     * @throws BusinessValidationException
     */
    protected function assertInArray(mixed $value, array $allowed, string $field): void
    {
        if (!in_array($value, $allowed, true)) {
            $options = implode(', ', array_map(fn($v) => (string) $v, $allowed));
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} debe ser uno de: {$options}"
            );
        }
    }

    /**
     * Valida que un string coincida con un patrón regex
     *
     * @throws BusinessValidationException
     */
    protected function assertMatchesPattern(
        string $value,
        string $pattern,
        string $field,
        string $message
    ): void {
        if (!preg_match($pattern, $value)) {
            throw BusinessValidationException::forField($field, $message);
        }
    }

    /**
     * Valida que una fecha no sea futura
     *
     * @throws BusinessValidationException
     */
    protected function assertNotFutureDate(DateTimeImmutable $date, string $field): void
    {
        if ($date > new DateTimeImmutable()) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} no puede ser una fecha futura"
            );
        }
    }

    /**
     * Valida que una fecha sea futura o presente
     *
     * @throws BusinessValidationException
     */
    protected function assertFutureOrPresentDate(DateTimeImmutable $date, string $field): void
    {
        $today = new DateTimeImmutable('today');
        if ($date < $today) {
            throw BusinessValidationException::forField(
                $field,
                "El campo {$field} debe ser una fecha presente o futura"
            );
        }
    }

     /**
     * Crea una entidad desde una fila de base de datos
     *
     * @param array<string, mixed> $row
     */
    abstract public static function fromDatabase(array $row): self;


    /**
     * Reconstruye la entidad desde un registro de base de datos
     *
     * @param array<string, mixed> $row
     */
    abstract public static function fromDatabase(array $row): self;

    /**
     * Serializa la entidad a array para respuesta API
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
