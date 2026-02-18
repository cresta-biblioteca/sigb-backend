<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Exceptions;

use Exception;

class MateriaAlreadyExistsException extends Exception
{
    private string $materia;

    public function __construct(string $materia)
    {
        $this->materia = $materia;
        parent::__construct(
            sprintf("La materia '%s' ya se encuentra registrada", $materia)
        );
    }

    public function getMateria(): string
    {
        return $this->materia;
    }
}