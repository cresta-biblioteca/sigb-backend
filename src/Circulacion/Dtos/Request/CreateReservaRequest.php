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
    #[OA\Property(
        description: "ID del lector que realiza la reserva",
        type: "integer",
        example: 1
    )]
    public int $lectorId;

    #[OA\Property(
        description: "ID del articulo a reservar",
        type: "integer",
        example: 5
    )]
    public int $articuloId;

    public function __construct(
        int $lectorId,
        int $articuloId
    ) {
        $this->lectorId = $lectorId;
        $this->articuloId = $articuloId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            lectorId: $data['lectorId'],
            articuloId: $data['articuloId']
        );
    }
}
