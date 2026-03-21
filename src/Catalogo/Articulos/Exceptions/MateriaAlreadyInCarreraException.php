<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class MateriaAlreadyInCarreraException extends BusinessRuleException
{
    public function __construct(int $materiaId, int $carreraId)
    {
        parent::__construct(
            errorCode: 'MATERIA_ALREADY_IN_CARRERA',
            safeMsg: 'La materia ya está agregada a esta carrera',
            internalMessage: "La materia (ID: {$materiaId}) ya está agregada a la carrera (ID: {$carreraId})",
            field: 'materia'
        );
    }
}
