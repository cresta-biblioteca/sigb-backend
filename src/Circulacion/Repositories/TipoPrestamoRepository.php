<?php

declare(strict_types=1);

namespace App\Circulacion\Repositories;

use App\Circulacion\Models\TipoPrestamo;
use App\Shared\Repository;

class TipoPrestamoRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'tipo_prestamo';
    }

    /**
     * @return class-string<TipoPrestamo>
     */
    protected function getEntityClass(): string
    {
        return TipoPrestamo::class;
    }

    protected function usesSoftDelete(): bool
    {
        return true;
    }

    public function findByCodigo(string $codigo): ?TipoPrestamo
    {
        $sql = "SELECT * FROM tipo_prestamo WHERE codigo = :codigo AND deleted_at IS NULL";
        return $this->findOneByQuery($sql, ["codigo" => $codigo]);
    }

    public function findCoincidence(string $codigo, string $descripcion): ?TipoPrestamo
    {
        $sql = "SELECT * FROM tipo_prestamo WHERE (codigo = :codigo OR descripcion = :descripcion) AND deleted_at IS NULL";
        return $this->findOneByQuery($sql, ["codigo" => $codigo, "descripcion" => $descripcion]);
    }

    public function insertTipoPrestamo(TipoPrestamo $tipoPrestamo): TipoPrestamo
    {
        $this->pdo->beginTransaction();
        try {
            $stmtInsert = $this->pdo->prepare("
			INSERT INTO tipo_prestamo(codigo, descripcion, max_cantidad_prestamos, duracion_prestamo, renovaciones,
										dias_renovacion, cant_dias_renovar)
			VALUES(:codigo, :descripcion, :max_cant_prestamos,
					:duracion, :renovaciones, :dias_renovacion, :cant_dias_renovar);
            ");

            $stmtInsert->execute([
                "codigo" => $tipoPrestamo->getCodigo(),
                "descripcion" => $tipoPrestamo->getDescripcion(),
                "max_cant_prestamos" => $tipoPrestamo->getMaxCantidadPrestamos(),
                "duracion" => $tipoPrestamo->getDuracionPrestamo(),
                "renovaciones" => $tipoPrestamo->getRenovaciones(),
                "dias_renovacion" => $tipoPrestamo->getDiasRenovacion(),
                "cant_dias_renovar" => $tipoPrestamo->getCantDiasRenovar(),
            ]);

            $tipoPrestamo->setId((int) $this->pdo->lastInsertId());

            $this->pdo->commit();

            return $tipoPrestamo;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateTipoPrestamo(int $id, TipoPrestamo $tipoPrestamo): TipoPrestamo
    {
        $this->pdo->beginTransaction();
        try {
            $stmtUpdate = $this->pdo->prepare("
                UPDATE tipo_prestamo SET codigo = :codigo, 
                                    descripcion = :descripcion, 
									max_cantidad_prestamos = :max_cant_prestamos, 
									duracion_prestamo = :duracion, 
									renovaciones = :renovaciones, 
									dias_renovacion = :dias_renovacion,
									cant_dias_renovar = :cant_dias_renovar
                WHERE id = :id;
            ");
            $stmtUpdate->execute([
                "codigo" => $tipoPrestamo->getCodigo(),
                "descripcion" => $tipoPrestamo->getDescripcion(),
                "max_cant_prestamos" => $tipoPrestamo->getMaxCantidadPrestamos(),
                "duracion" => $tipoPrestamo->getDuracionPrestamo(),
                "renovaciones" => $tipoPrestamo->getRenovaciones(),
                "dias_renovacion" => $tipoPrestamo->getDiasRenovacion(),
                "cant_dias_renovar" => $tipoPrestamo->getCantDiasRenovar(),
                "id" => $id
            ]);
            $this->pdo->commit();

            $tipoPrestamo = $this->findById($id);

            return $tipoPrestamo;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

}
