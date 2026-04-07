<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateReservaRequest",
    required: ["lectorId", "articuloId"]
)]
readonly class CreateReservaRequest
{
    public function __construct(
        #[OA\Property(description: "ID del lector que realiza la reserva", type: "integer", example: 1)]
        public int $lectorId,
        #[OA\Property(description: "ID del articulo a reservar", type: "integer", example: 5)]
        public int $articuloId
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            lectorId: $data['lectorId'],
            articuloId: $data['articuloId']
        );
    }
}
