<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class TemaAlreadyEliminatedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('El tema no pertenece a este artículo', 'tema');
    }

    public function getErrorCode(): string
    {
        return 'TEMA_NOT_IN_ARTICULO';
    }
}
