<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;
use RuntimeException;
use Throwable;

class ArticuloPersistenceException extends RuntimeException
{
    public static function couldNotSave(int $id, ?Throwable $previous = null): self
    {
        return new self(
            "No se pudo guardar el artículo con ID {$id}.",
            0,
            $previous
        );
    }

    public static function couldNotDelete(int $id, ?Throwable $previous = null): self
    {
        return new self(
            "No se pudo eliminar el artículo con ID {$id}.",
            0,
            $previous
        );
    }

    public static function databaseError(string $message, ?Throwable $previous = null): self
    {
        return new self(
            "Error de base de datos: {$message}",
            0,
            $previous
        );
    }
}
