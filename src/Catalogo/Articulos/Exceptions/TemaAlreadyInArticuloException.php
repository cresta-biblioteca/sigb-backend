<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class TemaAlreadyInArticuloException extends BusinessRuleException
{
    public function __construct(int $temaId, int $articuloId)
    {
        parent::__construct(
            errorCode: 'TEMA_ALREADY_IN_ARTICULO',
            safeMsg: 'El tema ya está agregado a este artículo',
            internalMessage: "El tema (ID: {$temaId}) ya está agregado al artículo (ID: {$articuloId})",
            field: 'tema'
        );
    }
}
