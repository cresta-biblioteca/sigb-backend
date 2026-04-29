<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "TipoPrestamoResponse",
    required: [
        "id",
        "codigo",
        "descripcion",
        "max_cant_prestamos",
        "duracion",
        "renovaciones",
        "dias_renovacion",
        "cant_dias_renovar",
        "activo"
    ]
)]
readonly class TipoPrestamoResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        private int $id,
        #[OA\Property(type: "string", example: "PD")]
        private string $codigo,
        #[OA\Property(type: "string", example: "Prestamo 1 dia")]
        private string $descripcion,
        #[OA\Property(type: "integer", example: 30)]
        private int $max_cant_prestamos,
        #[OA\Property(type: "integer", example: 1)]
        private int $duracion,
        #[OA\Property(type: "integer", example: 0)]
        private int $renovaciones,
        #[OA\Property(type: "integer", example: 3)]
        private int $dias_renovacion,
        #[OA\Property(type: "integer", example: 1)]
        private int $cant_dias_renovar,
        #[OA\Property(description: "Indica si el tipo de préstamo está activo", type: "bool", example: true)]
        private bool $activo
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "codigo" => $this->codigo,
            "descripcion" => $this->descripcion,
            "max_cant_prestamos" => $this->max_cant_prestamos,
            "duracion" => $this->duracion,
            "renovaciones" => $this->renovaciones,
            "dias_renovacion" => $this->dias_renovacion,
            "cant_dias_renovar" => $this->cant_dias_renovar,
            "activo" => $this->activo,
        ];
    }
}
