<?php

namespace App\Circulacion\Dtos\Request;

readonly class CreateReservaRequest
{
    public function __construct(
        public int $lectorId,
        public int $articuloId
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            lectorId: $data['lectorId'],
            articuloId: $data['articuloId']
        );
    }
}
