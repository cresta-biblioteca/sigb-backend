<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateTipoPrestamoRequest"
)]
class UpdateTipoPrestamoRequest
{
    #[OA\Property(
        description: "Codigo del tipo de prestamo",
        type: "string",
        example: "P7",
        nullable: true
    )]
    public ?string $codigo;
    #[OA\Property(
        description: "Descripcion del tipo de prestamo",
        type: "string",
        example: "Prestamo 7 dias",
        nullable: true
    )]
    public ?string $descripcion;
    #[OA\Property(
        description: "Cantidad de prestamos maximos",
        type: "integer",
        example: 30,
        nullable: true
    )]
    public ?int $maxCantidadPrestamos;
    #[OA\Property(
        description: "Duracion del prestamo",
        type: "integer",
        example: 7,
        nullable: true
    )]
    public ?int $duracionPrestamo;
    #[OA\Property(
        description: "Cantidad de renovaciones que se pueden realizar sobre ese prestamo",
        type: "integer",
        example: 4,
        nullable: true
    )]
    public ?int $renovaciones;
    #[OA\Property(
        description: "Dias que dura la renovacion",
        type: "integer",
        example: 7,
        nullable: true
    )]
    public ?int $diasRenovacion;
    #[OA\Property(
        description: "Cantidad de dias para renovar un prestamo",
        type: "integer",
        example: 0,
        nullable: true
    )]
    public ?int $cantDiasRenovar;

    public function __construct(
        ?string $codigo = null,
        ?string $descripcion = null,
        ?int $maxCantidadPrestamos = null,
        ?int $duracionPrestamo = null,
        ?int $renovaciones = null,
        ?int $diasRenovacion = null,
        ?int $cantDiasRenovar = null
    ) {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->maxCantidadPrestamos = $maxCantidadPrestamos;
        $this->duracionPrestamo = $duracionPrestamo;
        $this->renovaciones = $renovaciones;
        $this->diasRenovacion = $diasRenovacion;
        $this->cantDiasRenovar = $cantDiasRenovar;
    }

    public function setCodigo(string $codigo): void
    {
        $this->codigo = $codigo;
    }
    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }
    public function setMaxCantidadPrestamos(int $maxCantidadPrestamos): void
    {
        $this->maxCantidadPrestamos = $maxCantidadPrestamos;
    }
    public function setDuracionPrestamo(int $duracionPrestamo): void
    {
        $this->duracionPrestamo = $duracionPrestamo;
    }
    public function setRenovaciones(int $renovaciones): void
    {
        $this->renovaciones = $renovaciones;
    }
    public function setDiasRenovacion(int $diasRenovacion): void
    {
        $this->diasRenovacion = $diasRenovacion;
    }
    public function setCantDiasRenovar(int $cantDiasRenovar): void
    {
        $this->cantDiasRenovar = $cantDiasRenovar;
    }
}
