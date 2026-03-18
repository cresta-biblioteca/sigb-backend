<?php

namespace App\Circulacion\Dtos\Response;

readonly class ReservaResponse
{
    public function __construct(
        public \DateTimeImmutable $fechaInicio,
        public ?\DateTimeImmutable $fechaVencimiento
    )
    {
    }
}