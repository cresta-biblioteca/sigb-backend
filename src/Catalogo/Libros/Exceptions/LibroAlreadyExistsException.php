<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Exceptions;

use App\Shared\Exceptions\EntityAlreadyExistsException;
class LibroAlreadyExistsException extends EntityAlreadyExistsException
{
    public function __construct(int $articuloId)
    {
        parent::__construct('Libro', 'articulo_id', $articuloId);
    }
}