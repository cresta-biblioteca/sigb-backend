<?php

declare(strict_types=1);

namespace App\Circulacion\Models;

use App\Shared\Entity;

class TipoPrestamo extends Entity
{
    private string $codigo;
    private ?string $descripcion;
    private int $maxCantidadPrestamos;
    private int $duracionPrestamo;
    private int $renovaciones;
    private int $diasRenovacion;
    private int $cantDiasRenovar;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo TipoPrestamo (valida datos)
     */
    public static function create(
        string $codigo,
        int $maxCantidadPrestamos,
        int $duracionPrestamo,
        int $renovaciones,
        int $diasRenovacion,
        int $cantDiasRenovar,
        ?string $descripcion = null
    ): self {
        $tipo = new self();
        $tipo->setCodigo($codigo);
        $tipo->setMaxCantidadPrestamos($maxCantidadPrestamos);
        $tipo->setDuracionPrestamo($duracionPrestamo);
        $tipo->setRenovaciones($renovaciones);
        $tipo->setDiasRenovacion($diasRenovacion);
        $tipo->setCantDiasRenovar($cantDiasRenovar);
        $tipo->setDescripcion($descripcion);

        return $tipo;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $tipo = new self();
        $tipo->id = (int) $row['id'];
        $tipo->codigo = $row['codigo'];
        $tipo->descripcion = $row['descripcion'];
        $tipo->maxCantidadPrestamos = (int) $row['max_cantidad_prestamos'];
        $tipo->duracionPrestamo = (int) $row['duracion_prestamo'];
        $tipo->renovaciones = (int) $row['renovaciones'];
        $tipo->diasRenovacion = (int) $row['dias_renovacion'];
        $tipo->cantDiasRenovar = (int) $row['cant_dias_renovar'];
        $tipo->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            $row['deleted_at'] ?? null
        );

        return $tipo;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): void
    {
        $this->assertNotEmpty($codigo, 'codigo');
        $this->assertMaxLength($codigo, 3, 'codigo');
        $this->codigo = strtoupper($codigo);
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void
    {
        if ($descripcion !== null) {
            $this->assertMaxLength($descripcion, 100, 'descripcion');
        }
        $this->descripcion = $descripcion;
    }

    public function getMaxCantidadPrestamos(): int
    {
        return $this->maxCantidadPrestamos;
    }

    public function setMaxCantidadPrestamos(int $maxCantidadPrestamos): void
    {
        $this->assertPositive($maxCantidadPrestamos, 'max_cantidad_prestamos');
        $this->maxCantidadPrestamos = $maxCantidadPrestamos;
    }

    public function getDuracionPrestamo(): int
    {
        return $this->duracionPrestamo;
    }

    public function setDuracionPrestamo(int $duracionPrestamo): void
    {
        $this->assertPositive($duracionPrestamo, 'duracion_prestamo');
        $this->duracionPrestamo = $duracionPrestamo;
    }

    public function getRenovaciones(): int
    {
        return $this->renovaciones;
    }

    public function setRenovaciones(int $renovaciones): void
    {
        $this->assertNonNegative($renovaciones, 'renovaciones');
        $this->renovaciones = $renovaciones;
    }

    public function getDiasRenovacion(): int
    {
        return $this->diasRenovacion;
    }

    public function setDiasRenovacion(int $diasRenovacion): void
    {
        $this->assertNonNegative($diasRenovacion, 'dias_renovacion');
        $this->diasRenovacion = $diasRenovacion;
    }

    public function getCantDiasRenovar(): int
    {
        return $this->cantDiasRenovar;
    }

    public function setCantDiasRenovar(int $cantDiasRenovar): void
    {
        $this->assertNonNegative($cantDiasRenovar, 'cant_dias_renovar');
        $this->cantDiasRenovar = $cantDiasRenovar;
    }

    public function isActivo(): bool
    {
        return $this->deletedAt === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'max_cantidad_prestamos' => $this->maxCantidadPrestamos,
            'duracion_prestamo' => $this->duracionPrestamo,
            'renovaciones' => $this->renovaciones,
            'dias_renovacion' => $this->diasRenovacion,
            'cant_dias_renovar' => $this->cantDiasRenovar,
            'activo' => $this->isActivo(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
