<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessValidationException;

class MateriaAlreadyInCarreraException extends BusinessValidationException
{
    public function __construct(string $field, string $message)
    {
        parent::__construct($field, $message);
    }
}
