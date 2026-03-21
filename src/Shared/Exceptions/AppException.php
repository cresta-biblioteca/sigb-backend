<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

/**
 * Base para todas las excepciones de dominio de la aplicación.
 *
 * Garantiza que cada excepción tenga:
 *  - Un código legible por máquina (para que el frontend pueda reaccionar programáticamente)
 *  - Un HTTP status asociado
 *  - Un mensaje seguro para el usuario (sin internals como IDs o rutas)
 */
abstract class AppException extends RuntimeException
{
    abstract public function getErrorCode(): string;

    abstract public function getHttpStatus(): int;

    abstract public function getSafeMessage(): string;
}
