<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Repositories;

use App\Catalogo\Articulos\Models\Articulo;
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
        $sql = "SELECT 
            l.*,
            a.id AS a_id,
            a.titulo AS a_titulo,
            a.anio_publicacion AS a_anio_publicacion,
            a.tipo_documento_id AS a_tipo_documento_id,
            a.idioma AS a_idioma,
            a.created_at AS a_created_at,
            a.updated_at AS a_updated_at
            FROM libro l
            INNER JOIN articulo a ON a.id = l.articulo_id
            WHERE l.articulo_id = :id
            LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrateLibroWithArticulo($row);
    }

    /**
     * @return Libro[]
     */
    public function findAll(): array
    {
        $sql = "SELECT 
                l.*,
                a.id AS a_id,
                a.titulo AS a_titulo,
                a.anio_publicacion AS a_anio_publicacion,
                a.tipo_documento_id AS a_tipo_documento_id,
                a.idioma AS a_idioma,
                a.created_at AS a_created_at,
                a.updated_at AS a_updated_at
            FROM libro l
            INNER JOIN articulo a ON a.id = l.articulo_id";

        $stmt = $this->pdo->query($sql);
        $libros = [];

        while ($row = $stmt->fetch()) {
            $libros[] = $this->hydrateLibroWithArticulo($row);
        }

        return $libros;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM libro WHERE articulo_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function insertLibro(Libro $libro): Libro
    {
        $sql = "INSERT INTO libro 
            (articulo_id, isbn, autor, autores, colaboradores, titulo_informativo, cdu, export_marc, created_at,
             updated_at)
            VALUES
            (:articulo_id, :isbn, :autor, :autores, :colaboradores, :titulo_informativo, :cdu,
             :export_marc, NOW(), NOW())";

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'articulo_id' => $libro->getArticuloId(),
            'isbn' => $libro->getIsbn(),
            'autor' => $libro->getAutor(),
            'autores' => $libro->getAutores(),
            'colaboradores' => $libro->getColaboradores(),
            'titulo_informativo' => $libro->getTituloInformativo(),
            'cdu' => $libro->getCdu(),
            'export_marc' => $libro->getExportMarc(),
        ]);

        if ($success === false || $stmt->rowCount() === 0) {
            throw new \RuntimeException('Error al insertar el libro');
        }

        return $libro;
    }

    public function updateLibro(int $id, Libro $libro): Libro
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
            WHERE articulo_id = :id";

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'isbn' => $libro->getIsbn(),
            'autor' => $libro->getAutor(),
            'autores' => $libro->getAutores(),
            'colaboradores' => $libro->getColaboradores(),
            'titulo_informativo' => $libro->getTituloInformativo(),
            'cdu' => $libro->getCdu(),
            'export_marc' => $libro->getExportMarc(),
            'id' => $id,
        ]);

        if (!$success || $stmt->rowCount() === 0) {
            throw new \RuntimeException("No se pudo actualizar el libro con ID: {$id}");
        }

        return $this->findById($id);
    }

    public function existsByIsbn(string $isbn, ?int $excludeArticuloId = null): bool
    {
        $sql = 'SELECT 1 FROM libro WHERE isbn = :isbn';
        $params = ['isbn' => $isbn];

        if ($excludeArticuloId !== null) {
            $sql .= ' AND articulo_id <> :exclude_articulo_id';
            $params['exclude_articulo_id'] = $excludeArticuloId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    public function search(array $filters): array
    {
        [$conditions, $params] = $this->buildSearchConditionsAndParams($filters);

        $sql = "SELECT 
                l.*,
                a.id AS a_id,
                a.titulo AS a_titulo,
                a.anio_publicacion AS a_anio_publicacion,
                a.tipo_documento_id AS a_tipo_documento_id,
                a.idioma AS a_idioma,
                a.created_at AS a_created_at,
                a.updated_at AS a_updated_at
            FROM libro l
            INNER JOIN articulo a ON a.id = l.articulo_id";

        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY a.titulo, l.articulo_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $libros = [];
        while ($row = $stmt->fetch()) {
            $libros[] = $this->hydrateLibroWithArticulo($row);
        }

        return $libros;
    }

    /**
     * @param array<string, mixed> $filters
     * @return Libro[]
     */
    public function searchPaginated(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        [$conditions, $params] = $this->buildSearchConditionsAndParams($filters);

        $sql = "SELECT 
                l.*,
                a.id AS a_id,
                a.titulo AS a_titulo,
                a.anio_publicacion AS a_anio_publicacion,
                a.tipo_documento_id AS a_tipo_documento_id,
                a.idioma AS a_idioma,
                a.created_at AS a_created_at,
                a.updated_at AS a_updated_at
            FROM libro l
            INNER JOIN articulo a ON a.id = l.articulo_id";

        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY a.titulo, l.articulo_id LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $libros = [];
        while ($row = $stmt->fetch()) {
            $libros[] = $this->hydrateLibroWithArticulo($row);
        }

        return $libros;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countSearch(array $filters): int
    {
        [$conditions, $params] = $this->buildSearchConditionsAndParams($filters);

        $sql = "SELECT COUNT(*)
            FROM libro l
            INNER JOIN articulo a ON a.id = l.articulo_id";

        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function buildSearchConditionsAndParams(array $filters): array
    {
        $conditions = [];
        $params = [];

        $this->applyLibroFilters($filters, $conditions, $params);
        $this->applyArticuloFilters($filters, $conditions, $params);
        $this->applyTemaMateriaFilters($filters, $conditions, $params);

        return [$conditions, $params];
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     */
    private function applyLibroFilters(array $filters, array &$conditions, array &$params): void
    {
        if (!empty($filters['articulo_id'])) {
            $conditions[] = 'l.articulo_id = :l_articulo_id';
            $params['l_articulo_id'] = (int) $filters['articulo_id'];
        }

        if (!empty($filters['autor'])) {
            $conditions[] = 'l.autor LIKE :autor';
            $params['autor'] = '%' . $filters['autor'] . '%';
        }

        if (!empty($filters['autores'])) {
            $conditions[] = 'l.autores LIKE :autores';
            $params['autores'] = '%' . $filters['autores'] . '%';
        }

        if (!empty($filters['colaboradores'])) {
            $conditions[] = 'l.colaboradores LIKE :colaboradores';
            $params['colaboradores'] = '%' . $filters['colaboradores'] . '%';
        }

        if (!empty($filters['titulo_informativo'])) {
            $conditions[] = 'l.titulo_informativo LIKE :titulo_informativo';
            $params['titulo_informativo'] = '%' . $filters['titulo_informativo'] . '%';
        }

        if (!empty($filters['isbn'])) {
            $conditions[] = 'l.isbn = :isbn';
            $params['isbn'] = $filters['isbn'];
        }

        if (!empty($filters['cdu'])) {
            $conditions[] = 'l.cdu = :cdu';
            $params['cdu'] = $filters['cdu'];
        }

        if (!empty($filters['export_marc'])) {
            $conditions[] = 'l.export_marc LIKE :export_marc';
            $params['export_marc'] = '%' . $filters['export_marc'] . '%';
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     */
    private function applyArticuloFilters(array $filters, array &$conditions, array &$params): void
    {
        if (!empty($filters['titulo'])) {
            $conditions[] = 'a.titulo LIKE :a_titulo';
            $params['a_titulo'] = '%' . $filters['titulo'] . '%';
        }

        if (!empty($filters['anio_publicacion'])) {
            $conditions[] = 'a.anio_publicacion = :a_anio_publicacion';
            $params['a_anio_publicacion'] = (int) $filters['anio_publicacion'];
        }

        if (!empty($filters['tipo_documento_id'])) {
            $conditions[] = 'a.tipo_documento_id = :a_tipo_documento_id';
            $params['a_tipo_documento_id'] = (int) $filters['tipo_documento_id'];
        }

        if (!empty($filters['idioma'])) {
            $conditions[] = 'a.idioma = :a_idioma';
            $params['a_idioma'] = strtolower((string) $filters['idioma']);
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     */
    private function applyTemaMateriaFilters(array $filters, array &$conditions, array &$params): void
    {
        $temaIds = array_values(array_filter(
            array_map(
                static fn(mixed $id): int => (int) $id,
                $this->normalizeFilterAsArray($filters['tema_ids'] ?? null)
            ),
            static fn(int $id): bool => $id > 0
        ));

        if ($temaIds !== []) {
            $this->appendExistsByIdsCondition(
                conditions: $conditions,
                params: $params,
                ids: $temaIds,
                prefix: 'tema_id_',
                pivotTable: 'articulo_tema',
                pivotAlias: 'at',
                foreignKey: 'tema_id'
            );
        }

        $materiaIds = array_values(array_filter(
            array_map(
                static fn(mixed $id): int => (int) $id,
                $this->normalizeFilterAsArray($filters['materia_ids'] ?? null)
            ),
            static fn(int $id): bool => $id > 0
        ));

        if ($materiaIds !== []) {
            $this->appendExistsByIdsCondition(
                conditions: $conditions,
                params: $params,
                ids: $materiaIds,
                prefix: 'materia_id_',
                pivotTable: 'materia_articulo',
                pivotAlias: 'ma',
                foreignKey: 'materia_id'
            );
        }

        $temas = array_values(array_filter(
            array_map(
                static fn(mixed $tema): string => trim((string) $tema),
                $this->normalizeFilterAsArray($filters['temas'] ?? null)
            ),
            static fn(string $tema): bool => $tema !== ''
        ));

        if ($temas !== []) {
            $this->appendExistsByTitleCondition(
                conditions: $conditions,
                params: $params,
                values: $temas,
                prefix: 'tema_titulo_',
                pivotTable: 'articulo_tema',
                pivotAlias: 'at',
                relatedTable: 'tema',
                relatedAlias: 't',
                relatedIdField: 'tema_id'
            );
        }

        $materias = array_values(array_filter(
            array_map(
                static fn(mixed $materia): string => trim((string) $materia),
                $this->normalizeFilterAsArray($filters['materias'] ?? null)
            ),
            static fn(string $materia): bool => $materia !== ''
        ));

        if ($materias !== []) {
            $this->appendExistsByTitleCondition(
                conditions: $conditions,
                params: $params,
                values: $materias,
                prefix: 'materia_titulo_',
                pivotTable: 'materia_articulo',
                pivotAlias: 'ma',
                relatedTable: 'materia',
                relatedAlias: 'm',
                relatedIdField: 'materia_id'
            );
        }
    }

    /**
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     * @param array<int, int> $ids
     */
    private function appendExistsByIdsCondition(
        array &$conditions,
        array &$params,
        array $ids,
        string $prefix,
        string $pivotTable,
        string $pivotAlias,
        string $foreignKey
    ): void {
        $inParams = [];

        foreach ($ids as $index => $id) {
            $paramName = $prefix . $index;
            $inParams[] = ':' . $paramName;
            $params[$paramName] = $id;
        }

        $conditions[] = 'EXISTS (
            SELECT 1
            FROM ' . $pivotTable . ' ' . $pivotAlias . '
            WHERE ' . $pivotAlias . '.articulo_id = a.id
            AND ' . $pivotAlias . '.' . $foreignKey . ' IN (' . implode(', ', $inParams) . ')
        )';
    }

    /**
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     * @param array<int, string> $values
     */
    private function appendExistsByTitleCondition(
        array &$conditions,
        array &$params,
        array $values,
        string $prefix,
        string $pivotTable,
        string $pivotAlias,
        string $relatedTable,
        string $relatedAlias,
        string $relatedIdField
    ): void {
        $titleConditions = [];

        foreach ($values as $index => $value) {
            $paramName = $prefix . $index;
            $titleConditions[] = $relatedAlias . '.titulo LIKE :' . $paramName;
            $params[$paramName] = '%' . $value . '%';
        }

        $conditions[] = 'EXISTS (
            SELECT 1
            FROM ' . $pivotTable . ' ' . $pivotAlias . '
            INNER JOIN ' . $relatedTable . ' ' . $relatedAlias . ' ON ' . $relatedAlias . '.id = '
            . $pivotAlias . '.' . $relatedIdField . '
            WHERE ' . $pivotAlias . '.articulo_id = a.id
            AND (' . implode(' OR ', $titleConditions) . ')
        )';
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeFilterAsArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(
                $value,
                static fn(mixed $item): bool => $item !== null && $item !== ''
            ));
        }

        if ($value === null || $value === '') {
            return [];
        }

        return [$value];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateLibroWithArticulo(array $row): Libro
    {
        $libro = Libro::fromDatabase($row);

        if (!isset($row['a_id'])) {
            return $libro;
        }

        $articulo = Articulo::fromDatabase([
            'id' => $row['a_id'],
            'titulo' => $row['a_titulo'],
            'anio_publicacion' => $row['a_anio_publicacion'],
            'tipo_documento_id' => $row['a_tipo_documento_id'],
            'idioma' => $row['a_idioma'],
            'created_at' => $row['a_created_at'],
            'updated_at' => $row['a_updated_at'],
        ]);

        $libro->setArticulo($articulo);

        return $libro;
    }
}
