<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "EjemplarResponse")]
readonly class EjemplarResponse implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        private int $id,
        #[OA\Property(description: "Código de barras", type: "string", example: "9780321573513")]
        private string $codigoBarras,
        #[OA\Property(description: "Estado del ejemplar", type: "boolean", example: true)]
        private bool $habilitado,
        #[OA\Property(description: "ID del artículo asociado", type: "integer", example: 1)]
        private int $articuloId,
        #[OA\Property(description: "Signatura topográfica", type: "string", nullable: true, example: "001 SED")]
        private ?string $signaturaTopografica = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'codigo_barras' => $this->codigoBarras,
            'habilitado' => $this->habilitado,
            'articulo_id' => $this->articuloId,
            'signatura_topografica' => $this->signaturaTopografica,
        ];
    }
}
