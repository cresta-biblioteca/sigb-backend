<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class MateriaNotFoundException extends NotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct("Materia", $identifier);
    }
}
