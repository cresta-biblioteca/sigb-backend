<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

readonly class MateriaResponse
{
    public int $id;
    public string $titulo;

    public function __construct(
        int $id,
        string $titulo
    ) {
        $this->id = $id;
        $this->titulo = $titulo;
    }
}
