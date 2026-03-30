<?php

declare(strict_types=1);

namespace App\Circulacion\Repositories;

use App\Circulacion\Models\Prestamo;
use App\Shared\Repository;

class PrestamoRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'prestamo';
    }

    protected function getEntityClass(): string
    {
        return Prestamo::class;
    }

    /**
     * Indica si el lector tiene un préstamo activo (no devuelto) de algún ejemplar del artículo dado.
     * Se hace join con ejemplar para llegar al articulo_id ya que prestamo solo conoce el ejemplar_id.
     */
    public function lectorTienePrestamoActivoParaArticulo(int $lectorId, int $articuloId): bool
    {
        $sql = 'SELECT 1
                FROM prestamo p
                JOIN ejemplar e ON e.id = p.ejemplar_id
                WHERE p.lector_id = :lector_id
                  AND e.articulo_id = :articulo_id
                  AND p.fecha_devolucion IS NULL
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'lector_id'   => $lectorId,
            'articulo_id' => $articuloId,
        ]);

        return $stmt->fetch() !== false;
    }
}
