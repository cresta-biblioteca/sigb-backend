<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class TipoDocumentoAlreadyExistsException extends AlreadyExistsException
{
    public function __construct()
    {
        parent::__construct('El tipo de documento ya existe');
    }
}
