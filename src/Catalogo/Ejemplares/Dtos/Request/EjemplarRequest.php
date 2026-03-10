<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Request;

readonly class EjemplarRequest
{
    public function __construct(
        private int $articuloId,
        private string $codigoBarras,
        private bool $habilitado = true
    ) {
    }

    public function getArticuloId(): int
    {
        return $this->articuloId;
    }

    public function getCodigoBarras(): string
    {
        return $this->codigoBarras;
    }

    public function isHabilitado(): bool
    {
        return $this->habilitado;
    }
}
