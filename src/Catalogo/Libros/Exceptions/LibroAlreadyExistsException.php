<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class LibroAlreadyExistsException extends AlreadyExistsException
{
    public function __construct()
    {
        parent::__construct('El libro ya existe');
    }
}
