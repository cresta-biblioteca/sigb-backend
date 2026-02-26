<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use JsonSerializable;

readonly class MateriaResponse implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $titulo
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->titulo,
        ];
    }
}
