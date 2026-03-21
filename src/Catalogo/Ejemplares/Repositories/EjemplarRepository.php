<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Repositories;

use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Shared\Repository;

class EjemplarRepository extends Repository
{

    protected function getTableName(): string
    {
        return 'ejemplar';
    }

    protected function getEntityClass(): string
    {
        return Ejemplar::class;
    }

    public function insertEjemplar(Ejemplar $ejemplar): Ejemplar
    {
        $sql = 'INSERT INTO ejemplar (codigo_barras, habilitado, articulo_id, signatura_topografica, created_at, updated_at)
				VALUES (:codigo_barras, :habilitado, :articulo_id, :signatura_topografica, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'codigo_barras' => $ejemplar->getCodigoBarras(),
            'habilitado' => $ejemplar->isHabilitado() ? 1 : 0,
            'articulo_id' => $ejemplar->getArticuloId(),
            'signatura_topografica' => $ejemplar->getSignaturaTopografica(),
        ]);

        if ($success === false || $stmt->rowCount() === 0) {
            throw new \RuntimeException('Error al insertar el ejemplar');
        }

        $ejemplar->setId((int)$this->pdo->lastInsertId());

        return $ejemplar;
    }

    public function updateEjemplar(Ejemplar $ejemplar): bool
    {
        $sql = 'UPDATE ejemplar
				SET codigo_barras = :codigo_barras,
					habilitado = :habilitado,
					articulo_id = :articulo_id,
					signatura_topografica = :signatura_topografica,
					updated_at = NOW()
				WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'codigo_barras' => $ejemplar->getCodigoBarras(),
            'habilitado' => $ejemplar->isHabilitado() ? 1 : 0,
            'articulo_id' => $ejemplar->getArticuloId(),
            'signatura_topografica' => $ejemplar->getSignaturaTopografica(),
            'id' => $ejemplar->getId(),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function findEjemplarByCodigoBarras(string $codigoBarras): ?Ejemplar
    {
        $sql = 'SELECT * FROM ejemplar WHERE codigo_barras = :codigo_barras LIMIT 1';

        /** @var ?Ejemplar */
        return $this->findOneByQuery($sql, [
            'codigo_barras' => $codigoBarras,
        ]);
    }

    public function findEjemplaresByArticuloId(int $articuloId): array
    {
        $sql = 'SELECT * FROM ejemplar WHERE articulo_id = :articulo_id ORDER BY id DESC';

        /** @var Ejemplar[] */
        return $this->findByQuery($sql, [
            'articulo_id' => $articuloId,
        ]);
    }

    public function findEjemplaresByHabilitado(bool $habilitado): array
    {
        $sql = 'SELECT * FROM ejemplar WHERE habilitado = :habilitado ORDER BY id DESC';

        /** @var Ejemplar[] */
        return $this->findByQuery($sql, [
            'habilitado' => $habilitado ? 1 : 0,
        ]);
    }

    public function findEjemplaresHabilitadosByArticuloId(int $articuloId): array
    {
        $sql = 'SELECT * FROM ejemplar
				WHERE articulo_id = :articulo_id
				AND habilitado = 1
				ORDER BY id DESC';

        /** @var Ejemplar[] */
        return $this->findByQuery($sql, [
            'articulo_id' => $articuloId,
        ]);
    }

    public function existsEjemplarByCodigoBarras(string $codigoBarras, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM ejemplar WHERE codigo_barras = :codigo_barras';
        $params = ['codigo_barras' => $codigoBarras];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Devuelve el primer ejemplar disponible para un artículo dado.
     *
     * Un ejemplar se considera disponible si cumple las tres condiciones:
     *  - Está habilitado en el sistema
     *  - No tiene un préstamo activo (fecha_devolucion IS NULL indica que aún no fue devuelto)
     *  - No tiene una reserva pendiente con ejemplar asignado (evita asignar el mismo ejemplar a dos reservas)
     */
    public function getEjemplarDisponibleByArticuloId(int $articuloId): ?Ejemplar
    {
        $sql = 'SELECT e.*
                FROM ejemplar e
                WHERE e.habilitado = 1
                  AND e.articulo_id = :articulo_id
                  AND NOT EXISTS (
                      SELECT 1
                      FROM prestamo p
                      WHERE p.ejemplar_id = e.id
                        AND p.fecha_devolucion IS NULL
                  )
                  AND NOT EXISTS (
                      SELECT 1
                      FROM reserva r
                      WHERE r.ejemplar_id = e.id
                        AND r.estado = \'PENDIENTE\'
                  )
                LIMIT 1';

        /** @var ?Ejemplar */
        return $this->findOneByQuery($sql, [
            'articulo_id' => $articuloId,
        ]);
    }
}
