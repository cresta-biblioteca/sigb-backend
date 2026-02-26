<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\EntityNotFoundException;

class TemaNotFoundException extends EntityNotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct("Tema", $identifier);
    }
}
