<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class ArticuloAlreadyExistsException extends AlreadyExistsException
{
    public function __construct()
    {
        parent::__construct('El artículo ya existe');
    }
}
