<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Mappers;

use App\Catalogo\Ejemplares\Dtos\Response\EjemplarResponse;
use App\Catalogo\Ejemplares\Models\Ejemplar;

class EjemplarMapper
{
    public static function toResponse(Ejemplar $ejemplar): EjemplarResponse
    {
        return new EjemplarResponse(
            $ejemplar->getId() ?? 0,
            $ejemplar->getCodigoBarras() ?? '',
            $ejemplar->isHabilitado(),
            $ejemplar->getArticuloId()
        );
    }
}
