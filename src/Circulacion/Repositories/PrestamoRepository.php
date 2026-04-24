<?php

declare(strict_types=1);

namespace App\Circulacion\Repositories;

use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Circulacion\Models\EstadoPrestamo;
use App\Circulacion\Models\Prestamo;
use App\Circulacion\Models\TipoPrestamo;
use App\Lectores\Models\Lector;
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

    /**
     * Persiste un nuevo préstamo en la base de datos.
     */
    public function insertPrestamo(Prestamo $prestamo): void
    {
        $sql = "
            INSERT INTO prestamo
                (fecha_prestamo, fecha_vencimiento, fecha_devolucion, estado,
                 tipo_prestamo_id, ejemplar_id, lector_id,
                 cant_renovaciones, max_renovaciones)
            VALUES
                (:fecha_prestamo, :fecha_vencimiento, :fecha_devolucion, :estado,
                 :tipo_prestamo_id, :ejemplar_id, :lector_id,
                 :cant_renovaciones, :max_renovaciones)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'fecha_prestamo'     => $prestamo->getFechaPrestamo()->format('Y-m-d H:i:s'),
            'fecha_vencimiento'  => $prestamo->getFechaVencimiento()->format('Y-m-d H:i:s'),
            'fecha_devolucion'   => $prestamo->getFechaDevolucion()?->format('Y-m-d H:i:s'),
            'estado'             => $prestamo->getEstado()->value,
            'tipo_prestamo_id'   => $prestamo->getTipoPrestamoId(),
            'ejemplar_id'        => $prestamo->getEjemplarId(),
            'lector_id'          => $prestamo->getLectorId(),
            'cant_renovaciones'  => $prestamo->getCantRenovaciones(),
            'max_renovaciones'   => $prestamo->getMaxRenovaciones(),
        ]);

        $prestamo->setId((int) $this->pdo->lastInsertId());
    }

    /**
     * Actualiza un préstamo existente (estado, fechas).
     */
    public function updatePrestamo(Prestamo $prestamo): void
    {
        $sql = "
            UPDATE prestamo
            SET estado             = :estado,
                fecha_vencimiento  = :fecha_vencimiento,
                fecha_devolucion   = :fecha_devolucion,
                tipo_prestamo_id   = :tipo_prestamo_id,
                cant_renovaciones  = :cant_renovaciones
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'estado'            => $prestamo->getEstado()->value,
            'fecha_vencimiento' => $prestamo->getFechaVencimiento()->format('Y-m-d H:i:s'),
            'fecha_devolucion'  => $prestamo->getFechaDevolucion()?->format('Y-m-d H:i:s'),
            'tipo_prestamo_id'  => $prestamo->getTipoPrestamoId(),
            'cant_renovaciones' => $prestamo->getCantRenovaciones(),
            'id'                => $prestamo->getId(),
        ]);
    }

    /**
     * Busca un préstamo por ID con sus relaciones cargadas (tipo_prestamo, ejemplar, lector).
     */
    public function findByIdWithRelations(int $id): ?Prestamo
    {
        $sql = "
            SELECT
                p.*,
                tp.id            AS tp_id,
                tp.codigo        AS tp_codigo,
                tp.descripcion   AS tp_descripcion,
                tp.max_cantidad_prestamos AS tp_max_cantidad_prestamos,
                tp.duracion_prestamo      AS tp_duracion_prestamo,
                tp.renovaciones           AS tp_renovaciones,
                tp.dias_renovacion        AS tp_dias_renovacion,
                tp.cant_dias_renovar      AS tp_cant_dias_renovar,
                tp.deleted_at             AS tp_deleted_at,
                tp.created_at             AS tp_created_at,
                tp.updated_at             AS tp_updated_at,
                e.id                      AS ej_id,
                e.codigo_barras           AS ej_codigo_barras,
                e.deleted_at              AS ej_deleted_at,
                e.articulo_id             AS ej_articulo_id,
                e.signatura_topografica   AS ej_signatura_topografica,
                e.created_at              AS ej_created_at,
                e.updated_at              AS ej_updated_at,
                l.id             AS le_id,
                l.tarjeta_id     AS le_tarjeta_id,
                l.user_id        AS le_user_id,
                l.nombre         AS le_nombre,
                l.apellido       AS le_apellido,
                l.legajo         AS le_legajo,
                l.genero         AS le_genero,
                l.fecha_nacimiento AS le_fecha_nacimiento,
                l.telefono       AS le_telefono,
                l.email          AS le_email,
                l.cresta_id      AS le_cresta_id,
                l.created_at     AS le_created_at,
                l.updated_at     AS le_updated_at
            FROM prestamo p
            JOIN tipo_prestamo tp ON tp.id = p.tipo_prestamo_id
            JOIN ejemplar e       ON e.id  = p.ejemplar_id
            JOIN lector l         ON l.id  = p.lector_id
            WHERE p.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        return $this->hydrateWithRelations($row);
    }

    public function findByLectorId(int $lectorId, ?EstadoPrestamo $estado = null): array
    {
        $sql = 'SELECT * FROM prestamo WHERE lector_id = :lector_id';
        $params = ['lector_id' => $lectorId];

        if ($estado !== null) {
            $sql .= ' AND estado = :estado';
            $params['estado'] = $estado->value;
        }

        $sql .= ' ORDER BY fecha_prestamo DESC';

        return $this->findByQuery($sql, $params);
    }

    public function countPrestamosActivosByLectorAndTipo(int $lectorId, int $tipoPrestamoId): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM prestamo
            WHERE lector_id = :lector_id
              AND tipo_prestamo_id = :tipo_prestamo_id
              AND estado = :vigente
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'lector_id'        => $lectorId,
            'tipo_prestamo_id' => $tipoPrestamoId,
            'vigente'          => EstadoPrestamo::VIGENTE->value,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[] = 'estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        if (!empty($filters['lector_id'])) {
            $where[] = 'lector_id = :lector_id';
            $params['lector_id'] = $filters['lector_id'];
        }

        if (!empty($filters['tipo_prestamo_id'])) {
            $where[] = 'tipo_prestamo_id = :tipo_prestamo_id';
            $params['tipo_prestamo_id'] = $filters['tipo_prestamo_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $countSql = "SELECT COUNT(*) FROM prestamo {$whereClause}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Obtener página
        $offset = ($page - 1) * $perPage;
        $dataSql = "SELECT * FROM prestamo 
                    {$whereClause} 
                    ORDER BY fecha_prestamo DESC 
                    LIMIT {$perPage} 
                    OFFSET {$offset}";
        $dataStmt = $this->pdo->prepare($dataSql);
        $dataStmt->execute($params);

        $prestamos = [];
        while ($row = $dataStmt->fetch()) {
            $prestamos[] = Prestamo::fromDatabase($row);
        }

        return [
            'data'     => $prestamos,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }
    private function hydrateWithRelations(array $row): Prestamo
    {
        $prestamo = Prestamo::fromDatabase($row);

        $tipoPrestamo = TipoPrestamo::fromDatabase([
            'id'                     => $row['tp_id'],
            'codigo'                 => $row['tp_codigo'],
            'descripcion'            => $row['tp_descripcion'],
            'max_cantidad_prestamos' => $row['tp_max_cantidad_prestamos'],
            'duracion_prestamo'      => $row['tp_duracion_prestamo'],
            'renovaciones'           => $row['tp_renovaciones'],
            'dias_renovacion'        => $row['tp_dias_renovacion'],
            'cant_dias_renovar'      => $row['tp_cant_dias_renovar'],
            'deleted_at'             => $row['tp_deleted_at'],
            'created_at'             => $row['tp_created_at'],
            'updated_at'             => $row['tp_updated_at'],
        ]);
        $prestamo->setTipoPrestamo($tipoPrestamo);

        $ejemplar = Ejemplar::fromDatabase([
            'id'                     => $row['ej_id'],
            'codigo_barras'          => $row['ej_codigo_barras'],
            'deleted_at'             => $row['ej_deleted_at'],
            'articulo_id'            => $row['ej_articulo_id'],
            'signatura_topografica'  => $row['ej_signatura_topografica'],
            'created_at'             => $row['ej_created_at'],
            'updated_at'             => $row['ej_updated_at'],
        ]);
        $prestamo->setEjemplar($ejemplar);

        $lector = Lector::fromDatabase([
            'id'               => $row['le_id'],
            'tarjeta_id'       => $row['le_tarjeta_id'],
            'user_id'          => $row['le_user_id'],
            'nombre'           => $row['le_nombre'],
            'apellido'         => $row['le_apellido'],
            'legajo'           => $row['le_legajo'],
            'genero'           => $row['le_genero'],
            'fecha_nacimiento' => $row['le_fecha_nacimiento'],
            'telefono'         => $row['le_telefono'],
            'email'            => $row['le_email'],
            'cresta_id'        => $row['le_cresta_id'],
            'created_at'       => $row['le_created_at'],
            'updated_at'       => $row['le_updated_at'],
        ]);
        $prestamo->setLector($lector);

        return $prestamo;
    }
}
