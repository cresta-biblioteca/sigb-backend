<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Repository;

use App\Catalogo\Articulos\Models\Tema;
use App\Shared\Repository;

class TemaRepository extends Repository
{
    protected function getEntityClass(): string
    {
        return Tema::class;
    }

    protected function getTableName(): string
    {
        return 'tema';
    }

    public function insertTema(Tema $tema): Tema
    {
        $sql = 'INSERT INTO tema(titulo) VALUES (:titulo)';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $tema->getTitulo(),
        ]);

        if ($success === false || $stmt->rowCount() == 0) {
            throw new \Exception("Error al insertar el tema");
        }

        $tema->setId((int) $this->pdo->lastInsertId());
        return $tema;
    }

    public function updateTema(int $id, Tema $tema): Tema
    {
        $sql = 'UPDATE tema SET titulo = :titulo WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $tema->getTitulo(),
            'id' => $id
        ]);

        if ($success === false) {
            throw new \Exception("Error al actualizar el tema");
        }

        $tema->setId($id);

        return $tema;
    }

    public function findCoincidence(string $titulo): ?Tema
    {
        $sql = 'SELECT * FROM tema WHERE titulo = :titulo LIMIT 1';

        /** @var ?Tema */
        return $this->findOneByQuery($sql, ['titulo' => $titulo]);
    }

    /**
     * Busca temas filtrando por título (búsqueda parcial)
     *
     * @param array{titulo?: string} $params
     * @return Tema[]
     */
    public function findByParams(array $params): array
    {
        $conditions = [];
        $bindings = [];

        if (!empty($params['titulo'])) {
            $conditions[] = 'titulo LIKE :titulo';
            $escapedTitulo = addcslashes(trim($params['titulo']), '%_');
            $bindings['titulo'] = '%' . $escapedTitulo . '%';
        }

        $sql = sprintf('SELECT * FROM %s', $this->getTableName());

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if (!empty($params['order'])) {
            $order = strtoupper($params['order']) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY titulo {$order}";
        } else {
            $sql .= ' ORDER BY titulo';
        }

        /** @var Tema[] */
        return $this->findByQuery($sql, $bindings);
    }
}
