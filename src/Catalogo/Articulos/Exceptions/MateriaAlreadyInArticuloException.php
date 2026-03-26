<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class MateriaAlreadyInArticuloException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La materia ya está agregada a este artículo', 'materia');
    }

    public function getErrorCode(): string
    {
        return 'MATERIA_ALREADY_IN_ARTICULO';
    }
}
