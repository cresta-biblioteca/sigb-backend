<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class TemaAlreadyExistsException extends AlreadyExistsException
{
    public function __construct(string $tema)
    {
        parent::__construct(
            'Tema',
            'titulo',
            $tema
        );
    }
}
