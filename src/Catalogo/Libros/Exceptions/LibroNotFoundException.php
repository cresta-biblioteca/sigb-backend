<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class LibroNotFoundException extends NotFoundException
{
    public function __construct(int $articuloId)
    {
        parent::__construct('Libro', $articuloId);
    }
}
