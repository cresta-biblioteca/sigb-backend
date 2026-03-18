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