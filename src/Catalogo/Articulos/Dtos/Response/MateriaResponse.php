<?php

namespace App\Catalogo\Articulos\Dtos\Response;

class MateriaResponse
{
    public readonly int $id;
    public readonly string $titulo;

    public function __construct(
        int $id,
        string $titulo
    ) {
        $this->id = $id;
        $this->titulo = $titulo;
    }
}
