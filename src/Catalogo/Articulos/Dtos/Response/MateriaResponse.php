<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MateriaResponse",
    required: ["id", "titulo"]
)]
readonly class MateriaResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        private int $id,
        #[OA\Property(type: "integer", example: "Contabilidad")]
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
