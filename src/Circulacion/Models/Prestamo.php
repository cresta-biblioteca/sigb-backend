<?php

declare(strict_types=1);

namespace App\Circulacion\Models;

use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Lectores\Models\Lector;
use App\Shared\Entity;
use DateTimeImmutable;

class Prestamo extends Entity
{
    private DateTimeImmutable $fechaPrestamo;
    private DateTimeImmutable $fechaVencimiento;
    private ?DateTimeImmutable $fechaDevolucion = null;
    private EstadoPrestamo $estado;
    private int $tipoPrestamoId;
    private int $ejemplarId;
    private int $lectorId;

    private ?TipoPrestamo $tipoPrestamo = null;
    private ?Ejemplar $ejemplar = null;
    private ?Lector $lector = null;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Prestamo (valida datos)
     */
    public static function create(
        DateTimeImmutable $fechaPrestamo,
        DateTimeImmutable $fechaVencimiento,
        int $tipoPrestamoId,
        int $ejemplarId,
        int $lectorId,
        EstadoPrestamo $estado = EstadoPrestamo::ACTIVO
    ): self {
        $prestamo = new self();
        $prestamo->setFechaPrestamo($fechaPrestamo);
        $prestamo->setFechaVencimiento($fechaVencimiento);
        $prestamo->setTipoPrestamoId($tipoPrestamoId);
        $prestamo->setEjemplarId($ejemplarId);
        $prestamo->setLectorId($lectorId);
        $prestamo->setEstado($estado);

        return $prestamo;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $prestamo = new self();
        $prestamo->id = (int) $row['id'];
        $prestamo->fechaPrestamo = new DateTimeImmutable($row['fecha_prestamo']);
        $prestamo->fechaVencimiento = new DateTimeImmutable($row['fecha_vencimiento']);
        $prestamo->fechaDevolucion = isset($row['fecha_devolucion'])
            ? new DateTimeImmutable($row['fecha_devolucion'])
            : null;
        $prestamo->estado = EstadoPrestamo::from($row['estado']);
        $prestamo->tipoPrestamoId = (int) $row['tipo_prestamo_id'];
        $prestamo->ejemplarId = (int) $row['ejemplar_id'];
        $prestamo->lectorId = (int) $row['lector_id'];
        $prestamo->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );

        return $prestamo;
    }

    public function getFechaPrestamo(): DateTimeImmutable
    {
        return $this->fechaPrestamo;
    }

    public function setFechaPrestamo(DateTimeImmutable $fechaPrestamo): void
    {
        $this->fechaPrestamo = $fechaPrestamo;
    }

    public function getFechaVencimiento(): DateTimeImmutable
    {
        return $this->fechaVencimiento;
    }

    public function setFechaVencimiento(DateTimeImmutable $fechaVencimiento): void
    {
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public function getFechaDevolucion(): ?DateTimeImmutable
    {
        return $this->fechaDevolucion;
    }

    public function getEstado(): EstadoPrestamo
    {
        return $this->estado;
    }

    public function setEstado(EstadoPrestamo $estado): void
    {
        $this->estado = $estado;
    }

    public function getTipoPrestamoId(): int
    {
        return $this->tipoPrestamoId;
    }

    public function setTipoPrestamoId(int $tipoPrestamoId): void
    {
        $this->assertPositive($tipoPrestamoId, 'tipo_prestamo_id');
        $this->tipoPrestamoId = $tipoPrestamoId;
    }

    public function getEjemplarId(): int
    {
        return $this->ejemplarId;
    }

    public function setEjemplarId(int $ejemplarId): void
    {
        $this->assertPositive($ejemplarId, 'ejemplar_id');
        $this->ejemplarId = $ejemplarId;
    }

    public function getLectorId(): int
    {
        return $this->lectorId;
    }

    public function setLectorId(int $lectorId): void
    {
        $this->assertPositive($lectorId, 'lector_id');
        $this->lectorId = $lectorId;
    }

    public function getTipoPrestamo(): ?TipoPrestamo
    {
        return $this->tipoPrestamo;
    }

    public function setTipoPrestamo(TipoPrestamo $tipoPrestamo): void
    {
        $this->tipoPrestamo = $tipoPrestamo;
        $this->tipoPrestamoId = $tipoPrestamo->getId();
    }

    public function getEjemplar(): ?Ejemplar
    {
        return $this->ejemplar;
    }

    public function setEjemplar(Ejemplar $ejemplar): void
    {
        $this->ejemplar = $ejemplar;
        $this->ejemplarId = $ejemplar->getId();
    }

    public function getLector(): ?Lector
    {
        return $this->lector;
    }

    public function setLector(Lector $lector): void
    {
        $this->lector = $lector;
        $this->lectorId = $lector->getId();
    }

    public function isActivo(): bool
    {
        return $this->estado === EstadoPrestamo::ACTIVO;
    }

    public function isDevuelto(): bool
    {
        return $this->estado === EstadoPrestamo::DEVUELTO;
    }

    public function isVencido(): bool
    {
        return $this->estado === EstadoPrestamo::VENCIDO
            || ($this->isActivo() && $this->fechaVencimiento < new DateTimeImmutable());
    }

    public function devolver(): void
    {
        $this->estado = EstadoPrestamo::DEVUELTO;
        $this->fechaDevolucion = new DateTimeImmutable();
    }

    public function marcarVencido(): void
    {
        $this->estado = EstadoPrestamo::VENCIDO;
    }

    public function renovar(DateTimeImmutable $nuevaFechaVencimiento): void
    {
        $this->estado = EstadoPrestamo::RENOVADO;
        $this->fechaVencimiento = $nuevaFechaVencimiento;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'fecha_prestamo' => $this->fechaPrestamo->format('Y-m-d H:i:s'),
            'fecha_vencimiento' => $this->fechaVencimiento->format('Y-m-d H:i:s'),
            'fecha_devolucion' => $this->fechaDevolucion?->format('Y-m-d H:i:s'),
            'estado' => $this->estado->value,
            'tipo_prestamo_id' => $this->tipoPrestamoId,
            'ejemplar_id' => $this->ejemplarId,
            'lector_id' => $this->lectorId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->tipoPrestamo !== null) {
            $data['tipo_prestamo'] = $this->tipoPrestamo->toArray();
        }

        if ($this->ejemplar !== null) {
            $data['ejemplar'] = $this->ejemplar->toArray();
        }

        if ($this->lector !== null) {
            $data['lector'] = $this->lector->toArray();
        }

        return $data;
    }
}
