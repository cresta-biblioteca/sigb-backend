<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "UpdateTipoDocumentoRequest")]
class UpdateTipoDocumentoRequest
{
    #[OA\Property(description: "Codigo del documento(3 carac. max)", type: "string", example: "ABC", nullable: true)]
    public ?string $codigo;
    #[OA\Property(description: "Descripcion", type: "string", example: "Abecedario", nullable: true)]
    public ?string $descripcion;
    #[OA\Property(description: "Es renovable", type: "boolean", example: "false", nullable: true)]
    public ?bool $renovable;
    #[OA\Property(description: "Detalle", type: "string", example: "Abecedario completo", nullable: true)]
    public ?string $detalle;

    public function __construct(
        ?string $codigo = null,
        ?string $descripcion = null,
        ?bool $renovable = null,
        ?string $detalle = null
    ) {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->renovable = $renovable;
        $this->detalle = $detalle;
    }
    public function setCodigo(?string $codigo): void
    {
        $this->codigo = $codigo;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setRenovable(?bool $renovable): void
    {
        $this->renovable = $renovable;
    }

    public function setDetalle(?string $detalle): void
    {
        $this->detalle = $detalle;
    }
}
