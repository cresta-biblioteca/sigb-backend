<?php
namespace App\Catalogo\Articulos\Exceptions;

use App\Shared\Exceptions\EntityNotFoundException;

class MateriaNotFoundException extends EntityNotFoundException {
    public function __construct(mixed $identifier)
    {
        return parent::__construct("Materia", $identifier);
    }
}