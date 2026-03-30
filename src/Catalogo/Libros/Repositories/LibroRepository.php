<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Repositories;

use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Models\Materia;
use App\Catalogo\Articulos\Models\Tema;
use App\Catalogo\Libros\Models\LibroPersona;
use App\Catalogo\Libros\Models\Persona;
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
            a.descripcion AS a_descripcion,
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
            (articulo_id, isbn, issn, paginas, titulo_informativo, cdu,
             editorial, lugar_de_publicacion, edicion, dimensiones, ilustraciones,
             serie, numero_serie, notas, pais_publicacion, created_at, updated_at)
            VALUES
            (:articulo_id, :isbn, :issn, :paginas, :titulo_informativo, :cdu,
             :editorial, :lugar_de_publicacion, :edicion, :dimensiones, :ilustraciones,
             :serie, :numero_serie, :notas, :pais_publicacion, NOW(), NOW())";

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'articulo_id' => $libro->getArticuloId(),
            'isbn' => $libro->getIsbn(),
            'issn' => $libro->getIssn(),
            'paginas' => $libro->getPaginas(),
            'titulo_informativo' => $libro->getTituloInformativo(),
            'cdu' => $libro->getCdu(),
            'editorial' => $libro->getEditorial(),
            'lugar_de_publicacion' => $libro->getLugarDePublicacion(),
            'edicion' => $libro->getEdicion(),
            'dimensiones' => $libro->getDimensiones(),
            'ilustraciones' => $libro->getIlustraciones(),
            'serie' => $libro->getSerie(),
            'numero_serie' => $libro->getNumeroSerie(),
            'notas' => $libro->getNotas(),
            'pais_publicacion' => $libro->getPaisPublicacion(),
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
            issn = :issn,
            paginas = :paginas,
            titulo_informativo = :titulo_informativo,
            cdu = :cdu,
            editorial = :editorial,
            lugar_de_publicacion = :lugar_de_publicacion,
            edicion = :edicion,
            dimensiones = :dimensiones,
            ilustraciones = :ilustraciones,
            serie = :serie,
            numero_serie = :numero_serie,
            notas = :notas,
            pais_publicacion = :pais_publicacion,
            updated_at = NOW()
            WHERE articulo_id = :id";

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'isbn' => $libro->getIsbn(),
            'issn' => $libro->getIssn(),
            'paginas' => $libro->getPaginas(),
            'titulo_informativo' => $libro->getTituloInformativo(),
            'cdu' => $libro->getCdu(),
            'editorial' => $libro->getEditorial(),
            'lugar_de_publicacion' => $libro->getLugarDePublicacion(),
            'edicion' => $libro->getEdicion(),
            'dimensiones' => $libro->getDimensiones(),
            'ilustraciones' => $libro->getIlustraciones(),
            'serie' => $libro->getSerie(),
            'numero_serie' => $libro->getNumeroSerie(),
            'notas' => $libro->getNotas(),
            'pais_publicacion' => $libro->getPaisPublicacion(),
            'id' => $id,
        ]);

        if (!$success || $stmt->rowCount() === 0) {
            throw new \RuntimeException("No se pudo actualizar el libro con ID: {$id}");
        }

        return $this->findById($id);
    }

    /**
     * Sincroniza las personas de un libro: borra todas y re-inserta.
     *
     * @param array<int, array{persona_id: int, rol: string, orden: int}> $personasData
     */
    public function syncPersonas(int $libroId, array $personasData): void
    {
        $this->pdo->prepare("DELETE FROM libro_persona WHERE libro_id = :libro_id")
            ->execute(['libro_id' => $libroId]);

        if ($personasData === []) {
            return;
        }

        $sql = "INSERT INTO libro_persona (libro_id, persona_id, rol, orden)
                VALUES (:libro_id, :persona_id, :rol, :orden)";
        $stmt = $this->pdo->prepare($sql);

        foreach ($personasData as $data) {
            $stmt->execute([
                'libro_id' => $libroId,
                'persona_id' => $data['persona_id'],
                'rol' => $data['rol'],
                'orden' => $data['orden'],
            ]);
        }
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

    public function existsByIssn(string $issn, ?int $excludeArticuloId = null): bool
    {
        $sql = 'SELECT 1 FROM libro WHERE issn = :issn';
        $params = ['issn' => $issn];

        if ($excludeArticuloId !== null) {
            $sql .= ' AND articulo_id <> :exclude_articulo_id';
            $params['exclude_articulo_id'] = $excludeArticuloId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    private const SORTABLE_COLUMNS = [
        'titulo'           => 'a.titulo',
        'anio_publicacion' => 'a.anio_publicacion',
        'editorial'        => 'l.editorial',
        'isbn'             => 'l.isbn',
        'idioma'           => 'a.idioma',
        'id'               => 'l.articulo_id',
    ];

    /**
     * @param array<string, mixed> $filters
     * @return Libro[]
     */
    public function searchPaginated(
        array $filters,
        int $page,
        int $perPage,
        string $sortBy = 'titulo',
        string $sortDir = 'asc'
    ): array {
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
                a.descripcion AS a_descripcion,
                a.created_at AS a_created_at,
                a.updated_at AS a_updated_at
            FROM libro l
            INNER JOIN articulo a ON a.id = l.articulo_id";

        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $column    = self::SORTABLE_COLUMNS[$sortBy] ?? 'a.titulo';
        $direction = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$column} {$direction}, l.articulo_id {$direction} LIMIT :limit OFFSET :offset";

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

        if (!empty($filters['persona'])) {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM libro_persona lp
                INNER JOIN persona p ON p.id = lp.persona_id
                WHERE lp.libro_id = l.articulo_id
                AND (p.nombre LIKE :persona_nombre OR p.apellido LIKE :persona_apellido)
            )';
            $params['persona_nombre'] = '%' . $filters['persona'] . '%';
            $params['persona_apellido'] = '%' . $filters['persona'] . '%';
        }

        if (!empty($filters['titulo_informativo'])) {
            $conditions[] = 'l.titulo_informativo LIKE :titulo_informativo';
            $params['titulo_informativo'] = '%' . $filters['titulo_informativo'] . '%';
        }

        if (!empty($filters['isbn'])) {
            $conditions[] = 'l.isbn = :isbn';
            $params['isbn'] = $filters['isbn'];
        }

        if (!empty($filters['issn'])) {
            $conditions[] = 'l.issn = :issn';
            $params['issn'] = $filters['issn'];
        }

        if (!empty($filters['cdu'])) {
            $conditions[] = 'l.cdu = :cdu';
            $params['cdu'] = $filters['cdu'];
        }

        if (!empty($filters['editorial'])) {
            $conditions[] = 'l.editorial LIKE :editorial';
            $params['editorial'] = '%' . $filters['editorial'] . '%';
        }

        if (!empty($filters['lugar_de_publicacion'])) {
            $conditions[] = 'l.lugar_de_publicacion LIKE :lugar_de_publicacion';
            $params['lugar_de_publicacion'] = '%' . $filters['lugar_de_publicacion'] . '%';
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

        if (isset($row['a_id'])) {
            $articulo = Articulo::fromDatabase([
                'id' => $row['a_id'],
                'titulo' => $row['a_titulo'],
                'anio_publicacion' => $row['a_anio_publicacion'],
                'tipo_documento_id' => $row['a_tipo_documento_id'],
                'idioma' => $row['a_idioma'],
                'descripcion' => $row['a_descripcion'] ?? null
            ]);

            $libro->setArticulo($articulo);
        }

        $this->loadPersonas($libro);
        $this->loadTemasForArticulo($libro);
        $this->loadMateriasForArticulo($libro);

        return $libro;
    }

    private function loadPersonas(Libro $libro): void
    {
        $sql = "SELECT p.*, lp.rol, lp.orden
                FROM libro_persona lp
                INNER JOIN persona p ON p.id = lp.persona_id
                WHERE lp.libro_id = :libro_id
                ORDER BY lp.orden ASC, p.apellido ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['libro_id' => $libro->getArticuloId()]);

        $personas = [];
        while ($row = $stmt->fetch()) {
            $persona = Persona::fromDatabase($row);
            $personas[] = new LibroPersona($persona, $row['rol'], (int) $row['orden']);
        }

        $libro->setPersonas($personas);
    }

    private function loadTemasForArticulo(Libro $libro): void
    {
        $articulo = $libro->getArticulo();
        if ($articulo === null) {
            return;
        }

        $sql = "SELECT t.id, t.titulo
                FROM articulo_tema at2
                INNER JOIN tema t ON t.id = at2.tema_id
                WHERE at2.articulo_id = :articulo_id
                ORDER BY t.titulo ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['articulo_id' => $articulo->getId()]);

        $temas = [];
        while ($row = $stmt->fetch()) {
            $temas[] = Tema::fromDatabase($row);
        }

        $articulo->setTemas($temas);
    }

    private function loadMateriasForArticulo(Libro $libro): void
    {
        $articulo = $libro->getArticulo();
        if ($articulo === null) {
            return;
        }

        $sql = "SELECT m.id, m.titulo
                FROM materia_articulo ma
                INNER JOIN materia m ON m.id = ma.materia_id
                WHERE ma.articulo_id = :articulo_id
                ORDER BY m.titulo ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['articulo_id' => $articulo->getId()]);

        $materias = [];
        while ($row = $stmt->fetch()) {
            $materias[] = Materia::fromDatabase($row);
        }

        $articulo->setMaterias($materias);
    }
}
