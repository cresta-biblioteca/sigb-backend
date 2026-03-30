<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateTipoDocumentoRequest",
    required: ["codigo", "descripcion"]
)]
readonly class CreateTipoDocumentoRequest
{
    #[OA\Property(description: "Codigo del documento(3 carac. max)", type: "string", example: "LIB")]
    public string $codigo;
    #[OA\Property(description: "Descripcion", type: "string", example: "Libro")]
    public string $descripcion;
    #[OA\Property(description: "Es renovable", type: "boolean", example: "true")]
    public bool $renovable;
    #[OA\Property(description: "Detalle", type: "string", example: "Material bibliografico", nullable: true)]
    public ?string $detalle;

    public function __construct(string $codigo, string $descripcion, bool $renovable = true, ?string $detalle = null)
    {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->renovable = $renovable;
        $this->detalle = $detalle ?? null;
    }
}
