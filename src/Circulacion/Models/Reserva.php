<?php

declare(strict_types=1);

namespace App\Circulacion\Models;

use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Lectores\Models\Lector;
use App\Shared\Entity;
use DateTimeImmutable;

class Reserva extends Entity
{
    private DateTimeImmutable $fechaReserva;
    private DateTimeImmutable $fechaVencimiento;
    private EstadoReserva $estado;
    private int $lectorId;
    private int $ejemplarId;

    private ?Lector $lector = null;
    private ?Ejemplar $ejemplar = null;

    private function __construct()
    {
    }

    /**
     * Crea una nueva Reserva (valida datos)
     */
    public static function create(
        DateTimeImmutable $fechaReserva,
        DateTimeImmutable $fechaVencimiento,
        int $lectorId,
        int $ejemplarId,
        EstadoReserva $estado = EstadoReserva::PENDIENTE
    ): self {
        $reserva = new self();
        $reserva->setFechaReserva($fechaReserva);
        $reserva->setFechaVencimiento($fechaVencimiento);
        $reserva->setLectorId($lectorId);
        $reserva->setEjemplarId($ejemplarId);
        $reserva->setEstado($estado);

        return $reserva;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $reserva = new self();
        $reserva->id = (int) $row['id'];
        $reserva->fechaReserva = new DateTimeImmutable($row['fecha_reserva']);
        $reserva->fechaVencimiento = new DateTimeImmutable($row['fecha_vencimiento']);
        $reserva->estado = EstadoReserva::from($row['estado']);
        $reserva->lectorId = (int) $row['lector_id'];
        $reserva->ejemplarId = (int) $row['ejemplar_id'];
        $reserva->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );

        return $reserva;
    }

    public function getFechaReserva(): DateTimeImmutable
    {
        return $this->fechaReserva;
    }

    public function setFechaReserva(DateTimeImmutable $fechaReserva): void
    {
        $this->fechaReserva = $fechaReserva;
    }

    public function getFechaVencimiento(): DateTimeImmutable
    {
        return $this->fechaVencimiento;
    }

    public function setFechaVencimiento(DateTimeImmutable $fechaVencimiento): void
    {
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public function getEstado(): EstadoReserva
    {
        return $this->estado;
    }

    public function setEstado(EstadoReserva $estado): void
    {
        $this->estado = $estado;
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

    public function getEjemplarId(): int
    {
        return $this->ejemplarId;
    }

    public function setEjemplarId(int $ejemplarId): void
    {
        $this->assertPositive($ejemplarId, 'ejemplar_id');
        $this->ejemplarId = $ejemplarId;
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

    public function getEjemplar(): ?Ejemplar
    {
        return $this->ejemplar;
    }

    public function setEjemplar(Ejemplar $ejemplar): void
    {
        $this->ejemplar = $ejemplar;
        $this->ejemplarId = $ejemplar->getId();
    }

    public function isPendiente(): bool
    {
        return $this->estado === EstadoReserva::PENDIENTE;
    }

    public function isCompletada(): bool
    {
        return $this->estado === EstadoReserva::COMPLETADA;
    }

    public function isVencida(): bool
    {
        return $this->estado === EstadoReserva::VENCIDA
            || ($this->isPendiente() && $this->fechaVencimiento < new DateTimeImmutable());
    }

    public function completar(): void
    {
        $this->estado = EstadoReserva::COMPLETADA;
    }

    public function cancelar(): void
    {
        $this->estado = EstadoReserva::CANCELADA;
    }

    public function marcarVencida(): void
    {
        $this->estado = EstadoReserva::VENCIDA;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'fecha_reserva' => $this->fechaReserva->format('Y-m-d H:i:s'),
            'fecha_vencimiento' => $this->fechaVencimiento->format('Y-m-d H:i:s'),
            'estado' => $this->estado->value,
            'lector_id' => $this->lectorId,
            'ejemplar_id' => $this->ejemplarId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->lector !== null) {
            $data['lector'] = $this->lector->toArray();
        }

        if ($this->ejemplar !== null) {
            $data['ejemplar'] = $this->ejemplar->toArray();
        }

        return $data;
    }
}
