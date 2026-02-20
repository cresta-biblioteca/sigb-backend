<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\EntityAlreadyExistsException;

class MateriaAlreadyExistsException extends EntityAlreadyExistsException
{
    public function __construct(string $materia)
    {
        parent::__construct(
            'Materia',
            'titulo',
            $materia
        );
    }
}
