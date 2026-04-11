<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "EjemplarRequest",
    required: ["articulo_id", "codigo_barras"]
)]
readonly class EjemplarRequest
{
    public function __construct(
        #[OA\Property(description: "ID del artículo al que pertenece el ejemplar", type: "integer", example: 1)]
        private int $articuloId,
        #[OA\Property(
            description: "Código de barras del ejemplar (solo dígitos, máximo 13)",
            type: "string",
            example: "9780321573513"
        )]
        private string $codigoBarras,
        #[OA\Property(description: "Indica si el ejemplar está habilitado", type: "boolean", example: true)]
        private bool $habilitado = true,
        #[OA\Property(
            description: "Signatura topográfica del ejemplar",
            type: "string",
            nullable: true,
            example: "001 SED"
        )]
        private ?string $signaturaTopografica = null
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

    public function getSignaturaTopografica(): ?string
    {
        return $this->signaturaTopografica;
    }
}
