<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Request;

readonly class EjemplarRequest
{
    public function __construct(
        public int $articuloId,
        public string $codigoBarras,
        public bool $habilitado = true
    ) {
    }
}
