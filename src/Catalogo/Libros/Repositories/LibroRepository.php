<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Repositories;

use App\Shared\Repository;
use App\Catalogo\Libros\Models\Libro;
use PDO;

class LibroRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'libro';
    }

    protected function getEntityClass(): string
    {
        return Libro::class;
    }

    /**Sobrescribimos porque el PK es articulo_id y no id
     **/
    public function findById(int $id): ?Libro
    {
        $sql = "SELECT * FROM libro WHERE articulo_id = :id LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return Libro::fromDatabase($row);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM libro WHERE articulo_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function save(Libro $libro): void
    {
        $sql = "INSERT INTO libro 
            (articulo_id, isbn, autor, autores, colaboradores, titulo_informativo, cdu, export_marc, created_at, updated_at)
            VALUES
            (:articulo_id, :isbn, :autor, :autores, :colaboradores, :titulo_informativo, :cdu, :export_marc, NOW(), NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'articulo_id' => $libro->getArticuloId(),
            'isbn' => $libro->toArray()['isbn'],
            'autor' => $libro->toArray()['autor'],
            'autores' => $libro->toArray()['autores'],
            'colaboradores' => $libro->toArray()['colaboradores'],
            'titulo_informativo' => $libro->toArray()['titulo_informativo'],
            'cdu' => $libro->toArray()['cdu'],
            'export_marc' => $libro->toArray()['export_marc'],
        ]);
    }

    public function update(Libro $libro): bool
    {
        $sql = "UPDATE libro SET
            isbn = :isbn,
            autor = :autor,
            autores = :autores,
            colaboradores = :colaboradores,
            titulo_informativo = :titulo_informativo,
            cdu = :cdu,
            export_marc = :export_marc,
            updated_at = NOW()
            WHERE articulo_id = :articulo_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'isbn' => $libro->toArray()['isbn'],
            'autor' => $libro->toArray()['autor'],
            'autores' => $libro->toArray()['autores'],
            'colaboradores' => $libro->toArray()['colaboradores'],
            'titulo_informativo' => $libro->toArray()['titulo_informativo'],
            'cdu' => $libro->toArray()['cdu'],
            'export_marc' => $libro->toArray()['export_marc'],
            'articulo_id' => $libro->getArticuloId(),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function findByIsbn(string $isbn): ?Libro
    {
        $sql = "SELECT * FROM libro WHERE isbn = :isbn LIMIT 1";

        return $this->findOneByQuery($sql, [
            'isbn' => $isbn
        ]);
    }
    public function findByAutor(string $autor): array
    {
        $sql = "SELECT * FROM libro WHERE autor = :autor";

        return $this->findByQuery($sql, [
            'autor' => $autor
        ]);
    }
    public function searchByAutor(string $autor): array
    {
        $sql = "SELECT * FROM libro WHERE autor LIKE :autor";

        return $this->findByQuery($sql, [
            'autor' => '%' . $autor . '%'
        ]);
    }
    public function searchByAutores(string $autores): array
    {
        $sql = "SELECT * FROM libro WHERE autores LIKE :autores";

        return $this->findByQuery($sql, [
            'autores' => '%' . $autores . '%'
        ]);
    }
    public function searchByColaboradores(string $colaboradores): array
    {
        $sql = "SELECT * FROM libro WHERE colaboradores LIKE :colaboradores";

        return $this->findByQuery($sql, [
            'colaboradores' => '%' . $colaboradores . '%'
        ]);
    }
    public function searchByTituloInformativo(string $titulo): array
    {
        $sql = "SELECT * FROM libro WHERE titulo_informativo LIKE :titulo";

        return $this->findByQuery($sql, [
            'titulo' => '%' . $titulo . '%'
        ]);
    }
    public function findByCdu(int $cdu): array
    {
        $sql = "SELECT * FROM libro WHERE cdu = :cdu";

        return $this->findByQuery($sql, [
            'cdu' => $cdu
        ]);
    }
    public function findByCduRange(int $min, int $max): array
    {
        $sql = "SELECT * FROM libro WHERE cdu BETWEEN :min AND :max";

        return $this->findByQuery($sql, [
            'min' => $min,
            'max' => $max
        ]);
    }
    public function findByArticuloId(int $articuloId): ?Libro
    {
        $sql = "SELECT * FROM libro WHERE articulo_id = :id LIMIT 1";

        return $this->findOneByQuery($sql, [
            'id' => $articuloId
        ]);
    }
    public function findByCreatedBetween(string $from, string $to): array
    {
        $sql = "SELECT * FROM libro 
            WHERE created_at BETWEEN :from AND :to";

        return $this->findByQuery($sql, [
            'from' => $from,
            'to' => $to
        ]);
    }
    public function findByAutorAndCdu(string $autor, int $cdu): array
    {
        $sql = "SELECT * FROM libro 
            WHERE autor LIKE :autor
            AND cdu = :cdu";

        return $this->findByQuery($sql, [
            'autor' => '%' . $autor . '%',
            'cdu' => $cdu
        ]);
    }

    public function search(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['autor'])) {
            $conditions[] = "autor LIKE :autor";
            $params['autor'] = '%' . $filters['autor'] . '%';
        }

        if (!empty($filters['isbn'])) {
            $conditions[] = "isbn = :isbn";
            $params['isbn'] = $filters['isbn'];
        }

        if (!empty($filters['cdu'])) {
            $conditions[] = "cdu = :cdu";
            $params['cdu'] = $filters['cdu'];
        }

        $sql = "SELECT * FROM libro";

        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        return $this->findByQuery($sql, $params);
    }
}
