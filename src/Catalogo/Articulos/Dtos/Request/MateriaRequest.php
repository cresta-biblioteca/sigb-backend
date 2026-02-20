<?php

namespace App\Catalogo\Articulos\Dtos\Request;

class MateriaRequest
{
    public readonly string $titulo;

    public function __construct(
        string $titulo
    ) {
        $this->titulo = $titulo;
    }
}
