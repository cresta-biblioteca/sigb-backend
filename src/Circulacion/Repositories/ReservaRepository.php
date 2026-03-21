<?php

declare(strict_types=1);

namespace App\Circulacion\Repositories;

use App\Circulacion\Models\Reserva;
use App\Shared\Repository;

class ReservaRepository extends Repository
{
    protected function getTableName(): string
    {
        return "reserva";
    }

    protected function getEntityClass(): string
    {
        return Reserva::class;
    }

    /**
     * Indica si el lector tiene una reserva pendiente para el artículo dado.
     */
    public function lectorTieneReservaPendienteParaArticulo(int $lectorId, int $articuloId): bool
    {
        $sql = 'SELECT 1
                FROM reserva
                WHERE lector_id = :lector_id
                  AND articulo_id = :articulo_id
                  AND estado = \'PENDIENTE\'
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'lector_id'   => $lectorId,
            'articulo_id' => $articuloId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function save(Reserva $reserva): void
    {
        $sql = "
            INSERT INTO reserva (fecha_reserva, fecha_vencimiento, estado, lector_id, articulo_id, ejemplar_id)
            VALUES (:fecha_reserva, :fecha_vencimiento, :estado, :lector_id, :articulo_id, :ejemplar_id)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'fecha_reserva'     => $reserva->getFechaReserva()->format('Y-m-d H:i:s'),
            'fecha_vencimiento' => $reserva->getFechaVencimiento()?->format('Y-m-d H:i:s'),
            'estado'            => $reserva->getEstado()->value,
            'lector_id'         => $reserva->getLectorId(),
            'articulo_id'       => $reserva->getArticuloId(),
            'ejemplar_id'       => $reserva->getEjemplarId(),
        ]);

        $reserva->setId((int) $this->pdo->lastInsertId());
    }
}
