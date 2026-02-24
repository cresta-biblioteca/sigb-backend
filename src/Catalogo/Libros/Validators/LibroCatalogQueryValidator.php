<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Validators;

use App\Catalogo\Libros\Dtos\Request\LibroCatalogFilterRequest;
use App\Shared\Exceptions\ValidationException;

class LibroCatalogQueryValidator
{
    private const MIN_ANIO_PUBLICACION = 1500;
    private const MAX_TEXT_FILTER_LENGTH = 120;
    private const MAX_ARRAY_FILTER_ITEMS = 30;
    private const MAX_CDU_VALUE = 999999;
    private const ISBN_PATTERN = '/^\d{10}(\d{3})?$/';
    private const IDIOMA_PATTERN = '/^[a-zA-Z]{2}$/';

    /** @var array<int, string> */
    private const ALLOWED_QUERY_PARAMS = [
        'articulo_id',
        'isbn',
        'autor',
        'autores',
        'colaboradores',
        'titulo_informativo',
        'cdu',
        'export_marc',
        'titulo',
        'anio_publicacion',
        'tipo_documento_id',
        'idioma',
        'tema_ids',
        'materia_ids',
        'temas',
        'materias',
        'page',
        'per_page',
    ];

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): LibroCatalogFilterRequest
    {
        self::validateAllowedQueryParams($query);

        $filters = [];

        if (isset($query['articulo_id']) && $query['articulo_id'] !== '') {
            $filters['articulo_id'] = (int) $query['articulo_id'];
        }

        if (isset($query['isbn']) && $query['isbn'] !== '') {
            $filters['isbn'] = self::getValidatedIsbnFilter($query, 'isbn');
        }

        if (isset($query['autor']) && $query['autor'] !== '') {
            $filters['autor'] = self::getValidatedStringFilter($query, 'autor');
        }

        if (isset($query['autores']) && $query['autores'] !== '') {
            $filters['autores'] = self::getValidatedStringFilter($query, 'autores');
        }

        if (isset($query['colaboradores']) && $query['colaboradores'] !== '') {
            $filters['colaboradores'] = self::getValidatedStringFilter($query, 'colaboradores');
        }

        if (isset($query['titulo_informativo']) && $query['titulo_informativo'] !== '') {
            $filters['titulo_informativo'] = self::getValidatedStringFilter($query, 'titulo_informativo');
        }

        if (isset($query['cdu']) && $query['cdu'] !== '') {
            $filters['cdu'] = self::getValidatedCduFilter($query, 'cdu');
        }

        if (isset($query['export_marc']) && $query['export_marc'] !== '') {
            $filters['export_marc'] = self::getValidatedStringFilter($query, 'export_marc');
        }

        if (isset($query['titulo']) && $query['titulo'] !== '') {
            $filters['titulo'] = self::getValidatedStringFilter($query, 'titulo');
        }

        if (isset($query['anio_publicacion']) && $query['anio_publicacion'] !== '') {
            $filters['anio_publicacion'] = self::getValidatedAnioPublicacionFilter($query, 'anio_publicacion');
        }

        if (isset($query['tipo_documento_id']) && $query['tipo_documento_id'] !== '') {
            $filters['tipo_documento_id'] = (int) $query['tipo_documento_id'];
        }

        if (isset($query['idioma']) && $query['idioma'] !== '') {
            $filters['idioma'] = self::getValidatedIdiomaFilter($query, 'idioma');
        }

        $temaIds = self::getArrayQueryParam($query, 'tema_ids');
        if ($temaIds !== []) {
            $filters['tema_ids'] = array_map(static fn($id) => (int) $id, $temaIds);
        }

        $materiaIds = self::getArrayQueryParam($query, 'materia_ids');
        if ($materiaIds !== []) {
            $filters['materia_ids'] = array_map(static fn($id) => (int) $id, $materiaIds);
        }

        $temas = self::getArrayQueryParam($query, 'temas');
        if ($temas !== []) {
            $filters['temas'] = array_map(
                fn($tema) => self::validateTextFilterValue((string) $tema, 'temas'),
                $temas
            );
        }

        $materias = self::getArrayQueryParam($query, 'materias');
        if ($materias !== []) {
            $filters['materias'] = array_map(
                fn($materia) => self::validateTextFilterValue((string) $materia, 'materias'),
                $materias
            );
        }

        $page = isset($query['page']) ? max(1, (int) $query['page']) : 1;
        $perPage = isset($query['per_page']) ? max(1, min(100, (int) $query['per_page'])) : 20;

        return new LibroCatalogFilterRequest(
            filters: $filters,
            page: $page,
            perPage: $perPage
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function validateAllowedQueryParams(array $query): void
    {
        $unknown = array_diff(array_keys($query), self::ALLOWED_QUERY_PARAMS);

        if ($unknown === []) {
            return;
        }

        throw ValidationException::forField(
            'query',
            'Parámetros no permitidos: ' . implode(', ', $unknown)
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function getValidatedStringFilter(array $query, string $key): string
    {
        return self::validateTextFilterValue((string) $query[$key], $key);
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function getValidatedIsbnFilter(array $query, string $key): string
    {
        $isbn = self::validateTextFilterValue((string) $query[$key], $key);

        if (!preg_match(self::ISBN_PATTERN, $isbn)) {
            throw ValidationException::forField(
                $key,
                'El parámetro isbn debe contener 10 o 13 dígitos'
            );
        }

        return $isbn;
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function getValidatedIdiomaFilter(array $query, string $key): string
    {
        $idioma = strtolower(self::validateTextFilterValue((string) $query[$key], $key));

        if (!preg_match(self::IDIOMA_PATTERN, $idioma)) {
            throw ValidationException::forField(
                $key,
                'El parámetro idioma debe tener 2 letras (ej: es, en)'
            );
        }

        return $idioma;
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function getValidatedCduFilter(array $query, string $key): int
    {
        $raw = trim((string) $query[$key]);

        if (!preg_match('/^\d+$/', $raw)) {
            throw ValidationException::forField(
                $key,
                'El parámetro cdu debe ser un entero no negativo'
            );
        }

        $cdu = (int) $raw;

        if ($cdu > self::MAX_CDU_VALUE) {
            throw ValidationException::forField(
                $key,
                sprintf('El parámetro cdu no puede superar %d', self::MAX_CDU_VALUE)
            );
        }

        return $cdu;
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function getValidatedAnioPublicacionFilter(array $query, string $key): int
    {
        $raw = trim((string) $query[$key]);

        if (!preg_match('/^\d{4}$/', $raw)) {
            throw ValidationException::forField(
                $key,
                'El parámetro anio_publicacion debe ser un año válido de 4 dígitos'
            );
        }

        $anio = (int) $raw;
        $maxAnio = ((int) date('Y')) + 1;

        if ($anio < self::MIN_ANIO_PUBLICACION || $anio > $maxAnio) {
            throw ValidationException::forField(
                $key,
                sprintf(
                    'El parámetro anio_publicacion debe estar entre %d y %d',
                    self::MIN_ANIO_PUBLICACION,
                    $maxAnio
                )
            );
        }

        return $anio;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<int, mixed>
     */
    private static function getArrayQueryParam(array $query, string $key): array
    {
        if (!isset($query[$key])) {
            return [];
        }

        $value = $query[$key];

        if (is_array($value)) {
            $result = array_values(array_filter(
                $value,
                static fn($item): bool => $item !== null && $item !== ''
            ));

            if (count($result) > self::MAX_ARRAY_FILTER_ITEMS) {
                throw ValidationException::forField(
                    $key,
                    sprintf('El parámetro %s no puede tener más de %d valores', $key, self::MAX_ARRAY_FILTER_ITEMS)
                );
            }

            return $result;
        }

        if ($value === '') {
            return [];
        }

        return [$value];
    }

    private static function validateTextFilterValue(string $value, string $field): string
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) > self::MAX_TEXT_FILTER_LENGTH) {
            throw ValidationException::forField(
                $field,
                sprintf(
                    'El parámetro %s supera el máximo de %d caracteres',
                    $field,
                    self::MAX_TEXT_FILTER_LENGTH
                )
            );
        }

        return $trimmed;
    }
}
