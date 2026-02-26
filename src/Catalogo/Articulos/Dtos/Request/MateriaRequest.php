<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

readonly class MateriaRequest
{
    public string $titulo;

    public function __construct(
        string $titulo
    ) {
        $this->titulo = $titulo;
    }
}
