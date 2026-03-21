<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class MateriaAlreadyEliminatedException extends BusinessRuleException
{
    public function __construct(int $materiaId, int $carreraId)
    {
        parent::__construct(
            errorCode: 'MATERIA_NOT_IN_CARRERA',
            safeMsg: 'La materia no pertenece a esta carrera',
            internalMessage: "La materia (ID: {$materiaId}) no pertenece a la carrera (ID: {$carreraId})",
            field: 'materia'
        );
    }
}
