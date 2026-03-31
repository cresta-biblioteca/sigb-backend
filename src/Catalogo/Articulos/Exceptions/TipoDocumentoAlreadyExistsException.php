<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class TipoDocumentoAlreadyExistsException extends AlreadyExistsException
{
    public function __construct(string $codigo)
    {
        parent::__construct("TipoDocumento con Codigo \"{$codigo}\" ya existe");
    }
}
