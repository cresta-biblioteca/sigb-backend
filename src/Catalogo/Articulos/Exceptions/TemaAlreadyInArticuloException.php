<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class TemaAlreadyInArticuloException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('El tema ya está agregado a este artículo', 'tema');
    }

    public function getErrorCode(): string
    {
        return 'TEMA_ALREADY_IN_ARTICULO';
    }
}
