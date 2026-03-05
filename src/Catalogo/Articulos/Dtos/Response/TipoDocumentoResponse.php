<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use JsonSerializable;

readonly class TipoDocumentoResponse implements JsonSerializable
{

    public function __construct(
        private int $id,
        private string $codigo, 
        private string $descripcion, 
        private bool $renovable, 
        private ?string $detalle = null)
    {
    }

    public function jsonSerialize(): array 
    {
        return [
            "id" => $this->id,
            "codigo" => $this->codigo,
            "descripcion" => $this->descripcion,
            "renovable" => $this->renovable,
            "detalle" => $this->detalle,
        ];
    }
}