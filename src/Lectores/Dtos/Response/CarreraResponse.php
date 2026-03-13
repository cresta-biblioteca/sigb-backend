<?php

declare(strict_types=1);

namespace App\Lectores\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CarreraResponse",
    required: ["id", "codigo", "nombre"]
)]
readonly class CarreraResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        private int $id,
        #[OA\Property(type: "string", example: "CO")]
        private string $cod,
        #[OA\Property(type: "string", example: "Contador Público")]
        private string $nombre
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->cod,
            'nombre' => $this->nombre,
        ];
    }
}
