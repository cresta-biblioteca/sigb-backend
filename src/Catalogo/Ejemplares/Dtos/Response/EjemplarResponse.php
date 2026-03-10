<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Response;

readonly class EjemplarResponse implements \JsonSerializable
{
    public function __construct(
        private int $id,
        private string $codigoBarras,
        private bool $habilitado,
        private int $articuloId,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'codigo_barras' => $this->codigoBarras,
            'habilitado' => $this->habilitado,
            'articulo_id' => $this->articuloId,
        ];
    }
}
