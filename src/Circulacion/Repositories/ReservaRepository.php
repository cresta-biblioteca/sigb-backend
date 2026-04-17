<?php

declare(strict_types=1);

namespace App\Circulacion\Repositories;

use App\Circulacion\Models\EstadoReserva;
use App\Circulacion\Models\Reserva;
use App\Shared\Repository;
use PDO;

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

    /**
     * @return Reserva[]
     */
    public function getVencidasPendientes(): array
    {
        $sql = "SELECT *
                FROM reserva
                WHERE estado = 'PENDIENTE'
                  AND fecha_vencimiento IS NOT NULL
                  AND fecha_vencimiento < NOW()";

        /** @var Reserva[] */
        return $this->findByQuery($sql);
    }

    public function getProximaEnCola(int $articuloId): ?Reserva
    {
        $sql = "SELECT *
                FROM reserva
                WHERE estado = 'PENDIENTE'
                  AND articulo_id = :articulo_id
                  AND ejemplar_id IS NULL
                ORDER BY created_at ASC
                LIMIT 1";

        /** @var ?Reserva */
        return $this->findOneByQuery($sql, ['articulo_id' => $articuloId]);
    }

    /**
     * @param array{
     *     estado?: string,
     *     lector_id?: int,
     *     articulo_id?: int,
     *     ejemplar_id?: int,
     *     fecha_desde?: string,
     *     fecha_hasta?: string
     * } $filters
     * @return Reserva[]
     */
    public function findByFilters(array $filters, int $limit, int $offset): array
    {
        [$conditions, $params] = $this->buildConditionsAndParams($filters);

        $sql = 'SELECT * FROM reserva';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY fecha_reserva DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $reservas = [];
        while ($row = $stmt->fetch()) {
            $reservas[] = Reserva::fromDatabase($row);
        }

        /** @var Reserva[] */
        return $reservas;
    }

    /**
     * @param array{
     *     estado?: string,
     *     lector_id?: int,
     *     articulo_id?: int,
     *     ejemplar_id?: int,
     *     fecha_desde?: string,
     *     fecha_hasta?: string
     * } $filters
     */
    public function countByFilters(array $filters): int
    {
        [$conditions, $params] = $this->buildConditionsAndParams($filters);

        $sql = 'SELECT COUNT(*) FROM reserva';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildConditionsAndParams(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (isset($filters['estado'])) {
            $conditions[] = 'estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        if (isset($filters['lector_id'])) {
            $conditions[] = 'lector_id = :lector_id';
            $params['lector_id'] = $filters['lector_id'];
        }

        if (isset($filters['articulo_id'])) {
            $conditions[] = 'articulo_id = :articulo_id';
            $params['articulo_id'] = $filters['articulo_id'];
        }

        if (isset($filters['ejemplar_id'])) {
            $conditions[] = 'ejemplar_id = :ejemplar_id';
            $params['ejemplar_id'] = $filters['ejemplar_id'];
        }

        if (isset($filters['fecha_desde'])) {
            $conditions[] = 'fecha_reserva >= :fecha_desde';
            $params['fecha_desde'] = $filters['fecha_desde'];
        }

        if (isset($filters['fecha_hasta'])) {
            $conditions[] = 'fecha_reserva <= :fecha_hasta';
            $params['fecha_hasta'] = $filters['fecha_hasta'] . ' 23:59:59';
        }

        return [$conditions, $params];
    }

    public function update(Reserva $reserva): void
    {
        $sql = "UPDATE reserva
                SET estado            = :estado,
                    ejemplar_id       = :ejemplar_id,
                    fecha_vencimiento = :fecha_vencimiento,
                    updated_at        = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'estado'            => $reserva->getEstado()->value,
            'ejemplar_id'       => $reserva->getEjemplarId(),
            'fecha_vencimiento' => $reserva->getFechaVencimiento()?->format('Y-m-d H:i:s'),
            'id'                => $reserva->getId(),
        ]);
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

    public function existeReservaPendienteParaArticulo(int $articuloId): bool
    {
        $sql = 'SELECT 1
                FROM reserva
                WHERE articulo_id = :articulo_id
                  AND estado = \'PENDIENTE\'
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'articulo_id' => $articuloId
        ]);

        return $stmt->fetch() !== false;
    }


    public function completeReserva(int $id, EstadoReserva $estado): void
    {
        $sql = "UPDATE reserva SET estado = :estado WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'estado' => $estado->value,
            'id'     => $id,
        ]);
    }
}
