<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class TipoDocumentoNotFoundException extends NotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct("TipoDocumento", $identifier);
    }
}
