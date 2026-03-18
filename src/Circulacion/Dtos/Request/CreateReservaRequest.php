<?php

namespace App\Circulacion\Dtos\Request;
readonly class CreateReservaRequest
{
    public function __construct(
        public int $lectorId,
        public int $articuloId
    )
    {
    }
}