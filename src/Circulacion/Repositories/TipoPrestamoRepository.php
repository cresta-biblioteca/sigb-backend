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

    public function findByCodigo(string $codigo): ?TipoPrestamo
    {
        $sql = "SELECT * FROM tipo_prestamo WHERE codigo = :codigo";
        $tipoPrestamo = $this->findOneByQuery($sql, ["codigo" => $codigo]);
        return $tipoPrestamo;
    }

    public function findCoincidence(string $codigo, string $descripcion): ?TipoPrestamo
    {
        $sql = "SELECT * FROM tipo_prestamo WHERE codigo = :codigo OR descripcion = :descripcion";
        $tipoPrestamo = $this->findOneByQuery($sql, ["codigo" => $codigo, "descripcion" => $descripcion]);
        return $tipoPrestamo;
    }

    public function insertTipoPrestamo(TipoPrestamo $tipoPrestamo): TipoPrestamo
    {
        $this->pdo->beginTransaction();
        try {
            $stmtInsert = $this->pdo->prepare("
			INSERT INTO tipo_prestamo(codigo, descripcion, max_cantidad_prestamos, duracion_prestamo, renovaciones, 
										dias_renovacion, cant_dias_renovar, habilitado) 
			VALUES(:codigo, :descripcion, :max_cant_prestamos, 
					:duracion, :renovaciones, :dias_renovacion, :cant_dias_renovar, :habilitado);
            ");

            $stmtInsert->execute([
                "codigo" => $tipoPrestamo->getCodigo(),
                "descripcion" => $tipoPrestamo->getDescripcion(),
                "max_cant_prestamos" => $tipoPrestamo->getMaxCantidadPrestamos(),
                "duracion" => $tipoPrestamo->getDuracionPrestamo(),
                "renovaciones" => $tipoPrestamo->getRenovaciones(),
                "dias_renovacion" => $tipoPrestamo->getDiasRenovacion(),
                "cant_dias_renovar" => $tipoPrestamo->getCantDiasRenovar(),
                "habilitado" => $tipoPrestamo->isHabilitado()
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

    public function disableTipoPrestamo(int $id): void
    {
        $stmtDisable = $this->pdo->prepare("
			UPDATE tipo_prestamo SET habilitado = 0
			WHERE id = :id;
		");
        $stmtDisable->execute([
            "id" => $id
        ]);
    }

    public function enableTipoPrestamo(int $id): void
    {
        $stmtEnable = $this->pdo->prepare("
			UPDATE tipo_prestamo SET habilitado = 1
			WHERE id = :id;
		");
        $stmtEnable->execute([
            "id" => $id
        ]);
    }
}
