<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Response;

use App\Circulacion\Models\Reserva;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ReservaResponse",
    required: ["id", "lector_id", "articulo_id", "fecha_inicio"]
)]
readonly class ReservaResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        public int $id,
        #[OA\Property(type: "integer", example: 3)]
        public int $lectorId,
        #[OA\Property(type: "integer", example: 5)]
        public int $articuloId,
        #[OA\Property(type: "string", format: "date-time", example: "2026-04-06 10:00:00")]
        public \DateTimeImmutable $fechaInicio,
        #[OA\Property(type: "string", format: "date-time", example: "2026-04-09 10:00:00", nullable: true)]
        public ?\DateTimeImmutable $fechaVencimiento
    ) {
    }

    public static function fromReserva(Reserva $reserva): self
    {
        return new self(
            $reserva->getId(),
            $reserva->getLectorId(),
            $reserva->getArticuloId(),
            $reserva->getFechaReserva(),
            $reserva->getFechaVencimiento()
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'               => $this->id,
            'lector_id'        => $this->lectorId,
            'articulo_id'      => $this->articuloId,
            'fecha_inicio'     => $this->fechaInicio->format('Y-m-d H:i:s'),
            'fecha_vencimiento' => $this->fechaVencimiento?->format('Y-m-d H:i:s'),
        ];
    }
}
