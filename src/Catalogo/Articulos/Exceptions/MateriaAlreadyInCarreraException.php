<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class MateriaAlreadyInCarreraException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La materia ya está agregada a esta carrera', 'materia');
    }

    public function getErrorCode(): string
    {
        return 'MATERIA_ALREADY_IN_CARRERA';
    }
}
