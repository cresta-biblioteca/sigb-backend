<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\EntityAlreadyExistsException;

class ArticuloAlreadyExistsException extends EntityAlreadyExistsException
{
    public function __construct(string $field, mixed $value)
    {
        parent::__construct('Articulo', $field, $value);
    }
}
