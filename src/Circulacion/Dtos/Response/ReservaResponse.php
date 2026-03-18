<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Response;

use App\Circulacion\Models\Reserva;
use JsonSerializable;

readonly class ReservaResponse implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $lectorId,
        public int $articuloId,
        public \DateTimeImmutable $fechaInicio,
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