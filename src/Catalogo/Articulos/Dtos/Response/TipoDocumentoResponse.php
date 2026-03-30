<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "TipoDocumentoResponse",
    required: ["id", "codigo", "descripcion", "renovable", "detalle"]
)]
readonly class TipoDocumentoResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        private int $id,
        #[OA\Property(type: "string", example: "EA")]
        private string $codigo,
        #[OA\Property(type: "string", example: "Equipos Audiovisuales")]
        private string $descripcion,
        #[OA\Property(type: "boolean", example: true)]
        private bool $renovable,
        #[OA\Property(type: "string", example: "Equipos Audiovisuales", nullable: true)]
        private ?string $detalle = null
    ) {
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
