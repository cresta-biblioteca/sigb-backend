<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateTipoPrestamoRequest"
)]
readonly class UpdateTipoPrestamoRequest
{
    public function __construct(
        #[OA\Property(
            description: "Codigo del tipo de prestamo",
            type: "string",
            example: "P7",
            nullable: true
        )]
        public ?string $codigo = null,
        #[OA\Property(
            description: "Descripcion del tipo de prestamo",
            type: "string",
            example: "Prestamo 7 dias",
            nullable: true
        )]
        public ?string $descripcion = null,
        #[OA\Property(
            description: "Cantidad de prestamos maximos",
            type: "integer",
            example: 30,
            nullable: true
        )]
        public ?int $maxCantidadPrestamos = null,
        #[OA\Property(
            description: "Duracion del prestamo",
            type: "integer",
            example: 7,
            nullable: true
        )]
        public ?int $duracionPrestamo = null,
        #[OA\Property(
            description: "Cantidad de renovaciones que se pueden realizar sobre ese prestamo",
            type: "integer",
            example: 4,
            nullable: true
        )]
        public ?int $renovaciones = null,
        #[OA\Property(
            description: "Dias que dura la renovacion",
            type: "integer",
            example: 7,
            nullable: true
        )]
        public ?int $diasRenovacion = null,
        #[OA\Property(
            description: "Cantidad de dias para renovar un prestamo",
            type: "integer",
            example: 0,
            nullable: true
        )]
        public ?int $cantDiasRenovar = null
    ) {
    }
}
