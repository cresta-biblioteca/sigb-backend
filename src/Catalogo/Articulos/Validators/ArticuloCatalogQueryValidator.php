<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Validators;

use App\Catalogo\Articulos\Dtos\Request\ArticuloCatalogFilterRequest;
use App\Shared\Exceptions\ValidationException;

class ArticuloCatalogQueryValidator
{
    private const MIN_ANIO_PUBLICACION = 1500;
    private const MAX_TEXT_FILTER_LENGTH = 120;
    private const MAX_ARRAY_FILTER_ITEMS = 30;
    private const IDIOMA_PATTERN = '/^[a-zA-Z]{2}$/';

    /** @var array<int, string> */
    private const ALLOWED_QUERY_PARAMS = [
        'titulo',
        'tipo_documento_id',
        'idioma',
        'anio_publicacion',
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
    public static function fromQuery(array $query): ArticuloCatalogFilterRequest
    {
        self::validateAllowedQueryParams($query);

        $filters = [];

        if (isset($query['titulo']) && $query['titulo'] !== '') {
            $filters['titulo'] = self::validateTextFilterValue((string) $query['titulo'], 'titulo');
        }

        if (isset($query['tipo_documento_id']) && $query['tipo_documento_id'] !== '') {
            $filters['tipo_documento_id'] = self::validatePositiveInt($query['tipo_documento_id'], 'tipo_documento_id');
        }

        if (isset($query['idioma']) && $query['idioma'] !== '') {
            $filters['idioma'] = self::validateIdioma((string) $query['idioma']);
        }

        if (isset($query['anio_publicacion']) && $query['anio_publicacion'] !== '') {
            $filters['anio_publicacion'] = self::validateAnioPublicacion((string) $query['anio_publicacion']);
        }

        $temaIds = self::getArrayQueryParam($query, 'tema_ids');
        if ($temaIds !== []) {
            $filters['tema_ids'] = array_map(
                static fn(mixed $id): int => self::validatePositiveInt($id, 'tema_ids'),
                $temaIds
            );
        }

        $materiaIds = self::getArrayQueryParam($query, 'materia_ids');
        if ($materiaIds !== []) {
            $filters['materia_ids'] = array_map(
                static fn(mixed $id): int => self::validatePositiveInt($id, 'materia_ids'),
                $materiaIds
            );
        }

        $temas = self::getArrayQueryParam($query, 'temas');
        if ($temas !== []) {
            $filters['temas'] = array_map(
                fn(mixed $tema): string => self::validateTextFilterValue((string) $tema, 'temas'),
                $temas
            );
        }

        $materias = self::getArrayQueryParam($query, 'materias');
        if ($materias !== []) {
            $filters['materias'] = array_map(
                fn(mixed $materia): string => self::validateTextFilterValue((string) $materia, 'materias'),
                $materias
            );
        }

        $page = isset($query['page']) ? max(1, (int) $query['page']) : 1;
        $perPage = isset($query['per_page']) ? max(1, min(100, (int) $query['per_page'])) : 20;

        return new ArticuloCatalogFilterRequest(
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

    private static function validateTextFilterValue(string $value, string $field): string
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) > self::MAX_TEXT_FILTER_LENGTH) {
            throw ValidationException::forField(
                $field,
                sprintf('El parámetro %s supera el máximo de %d caracteres', $field, self::MAX_TEXT_FILTER_LENGTH)
            );
        }

        return $trimmed;
    }

    private static function validatePositiveInt(mixed $value, string $field): int
    {
        $intValue = (int) $value;

        if ($intValue < 1) {
            throw ValidationException::forField(
                $field,
                sprintf('El parámetro %s debe ser un entero positivo', $field)
            );
        }

        return $intValue;
    }

    private static function validateIdioma(string $value): string
    {
        $idioma = strtolower(self::validateTextFilterValue($value, 'idioma'));

        if (!preg_match(self::IDIOMA_PATTERN, $idioma)) {
            throw ValidationException::forField(
                'idioma',
                'El parámetro idioma debe tener 2 letras (ej: es, en)'
            );
        }

        return $idioma;
    }

    private static function validateAnioPublicacion(string $value): int
    {
        $raw = trim($value);

        if (!preg_match('/^\d{4}$/', $raw)) {
            throw ValidationException::forField(
                'anio_publicacion',
                'El parámetro anio_publicacion debe ser un año válido de 4 dígitos'
            );
        }

        $anio = (int) $raw;
        $maxAnio = ((int) date('Y')) + 1;

        if ($anio < self::MIN_ANIO_PUBLICACION || $anio > $maxAnio) {
            throw ValidationException::forField(
                'anio_publicacion',
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
}
