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
            property: "max_cantidad_prestamos",
            description: "Cantidad de prestamos maximos",
            type: "integer",
            example: 30,
            nullable: true
        )]
        public ?int $maxCantidadPrestamos = null,
        #[OA\Property(
            property: "duracion",
            description: "Duracion del prestamo en dias",
            type: "integer",
            example: 7,
            nullable: true
        )]
        public ?int $duracionPrestamo = null,
        #[OA\Property(
            property: "renovaciones",
            description: "Cantidad de renovaciones que se pueden realizar sobre ese prestamo",
            type: "integer",
            example: 4,
            nullable: true
        )]
        public ?int $renovaciones = null,
        #[OA\Property(
            property: "dias_renovacion",
            description: "Dias que dura la renovacion",
            type: "integer",
            example: 7,
            nullable: true
        )]
        public ?int $diasRenovacion = null,
        #[OA\Property(
            property: "cant_dias_renovar",
            description: "Cantidad de dias para renovar un prestamo",
            type: "integer",
            example: 0,
            nullable: true
        )]
        public ?int $cantDiasRenovar = null
    ) {
    }
}
