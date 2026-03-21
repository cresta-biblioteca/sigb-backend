<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class TemaAlreadyEliminatedException extends BusinessRuleException
{
    public function __construct(int $temaId, int $articuloId)
    {
        parent::__construct(
            errorCode: 'TEMA_NOT_IN_ARTICULO',
            safeMsg: 'El tema no pertenece a este artículo',
            internalMessage: "El tema (ID: {$temaId}) no pertenece al artículo (ID: {$articuloId})",
            field: 'tema'
        );
    }
}
