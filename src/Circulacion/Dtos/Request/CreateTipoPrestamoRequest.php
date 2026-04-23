<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateTipoPrestamoRequest",
    required: [
        "codigo",
        "descripcion",
        "max_cantidad_prestamos",
        "duracion",
        "renovaciones",
        "dias_renovacion",
        "cant_dias_renovar"
    ]
)]
readonly class CreateTipoPrestamoRequest
{
    #[OA\Property(
        description: "Codigo del tipo de prestamo",
        type: "string",
        example: "P1"
    )]
    public string $codigo;
    #[OA\Property(
        description: "Descripcion del tipo de prestamo",
        type: "string",
        example: "Prestamo 15 dias"
    )]
    public string $descripcion;
    #[OA\Property(
        property: "max_cantidad_prestamos",
        description: "Cantidad de prestamos maximos",
        type: "integer",
        example: 30
    )]
    public int $maxCantidadPrestamos;
    #[OA\Property(
        property: "duracion",
        description: "Duracion del prestamo en dias",
        type: "integer",
        example: 15
    )]
    public int $duracionPrestamo;
    #[OA\Property(
        property: "renovaciones",
        description: "Cantidad de renovaciones que se pueden realizar sobre ese prestamo",
        type: "integer",
        example: 4
    )]
    public int $renovaciones;
    #[OA\Property(
        property: "dias_renovacion",
        description: "Dias que dura la renovacion",
        type: "integer",
        example: 15
    )]
    public int $diasRenovacion;
    #[OA\Property(
        property: "cant_dias_renovar",
        description: "Cantidad de dias para renovar un prestamo",
        type: "integer",
        example: 0
    )]
    public int $cantDiasRenovar;

    public function __construct(
        string $codigo,
        string $descripcion,
        int $maxCantidadPrestamos,
        int $duracionPrestamo,
        int $renovaciones,
        int $diasRenovacion,
        int $cantDiasRenovar
    ) {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->maxCantidadPrestamos = $maxCantidadPrestamos;
        $this->duracionPrestamo = $duracionPrestamo;
        $this->renovaciones = $renovaciones;
        $this->diasRenovacion = $diasRenovacion;
        $this->cantDiasRenovar = $cantDiasRenovar;
    }
}
