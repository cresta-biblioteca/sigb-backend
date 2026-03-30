<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class MateriaAlreadyEliminatedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La materia no pertenece a esta carrera', 'materia');
    }

    public function getErrorCode(): string
    {
        return 'MATERIA_NOT_IN_CARRERA';
    }
}
