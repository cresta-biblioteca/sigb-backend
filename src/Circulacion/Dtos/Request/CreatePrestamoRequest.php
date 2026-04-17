<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreatePrestamoRequest",
    required: ["reserva_id", "tipo_prestamo_id"]
)]
readonly class CreatePrestamoRequest
{
    #[OA\Property(
        description: "ID de la reserva pendiente a completar",
        type: "integer",
        example: 1
    )]
    public int $reservaId;

    #[OA\Property(
        description: "ID del tipo de préstamo a aplicar",
        type: "integer",
        example: 1
    )]
    public int $tipoPrestamoId;

    public function __construct(int $reservaId, int $tipoPrestamoId)
    {
        $this->reservaId = $reservaId;
        $this->tipoPrestamoId = $tipoPrestamoId;
    }
}
