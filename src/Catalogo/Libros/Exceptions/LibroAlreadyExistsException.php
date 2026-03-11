<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Exceptions;

use App\Shared\Exceptions\EntityAlreadyExistsException;

class LibroAlreadyExistsException extends EntityAlreadyExistsException
{
    public function __construct(int|string $value, string $field = 'articulo_id')
    {
        parent::__construct('Libro', $field, $value);
    }
}
