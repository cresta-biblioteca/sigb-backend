<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Validators;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarCatalogFilterRequest;
use App\Shared\Exceptions\ValidationException;

class EjemplarCatalogQueryValidator
{
    private const CODIGO_BARRAS_PATTERN = '/^\d{1,13}$/';

    /** @var array<int, string> */
    private const ALLOWED_QUERY_PARAMS = [
        'codigo_barras',
        'articulo_id',
        'habilitado',
    ];

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): EjemplarCatalogFilterRequest
    {
        self::validateAllowedQueryParams($query);

        $filters = [];

        if (isset($query['codigo_barras']) && $query['codigo_barras'] !== '') {
            $filters['codigo_barras'] = self::validateCodigoBarras((string) $query['codigo_barras']);
        }

        if (isset($query['articulo_id']) && $query['articulo_id'] !== '') {
            $filters['articulo_id'] = self::validateArticuloId($query['articulo_id']);
        }

        if (isset($query['habilitado']) && $query['habilitado'] !== '') {
            $filters['habilitado'] = self::validateHabilitado($query['habilitado']);
        }

        return new EjemplarCatalogFilterRequest($filters);
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

    private static function validateCodigoBarras(string $value): string
    {
        $codigoBarras = trim($value);

        if (!preg_match(self::CODIGO_BARRAS_PATTERN, $codigoBarras)) {
            throw ValidationException::forField(
                'codigo_barras',
                'El parámetro codigo_barras debe contener solo dígitos (máximo 13)'
            );
        }

        return $codigoBarras;
    }

    private static function validateArticuloId(mixed $value): int
    {
        $articuloId = (int) $value;

        if ($articuloId < 1) {
            throw ValidationException::forField(
                'articulo_id',
                'El parámetro articulo_id debe ser un entero positivo'
            );
        }

        return $articuloId;
    }

    private static function validateHabilitado(mixed $value): bool
    {
        $habilitado = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (!is_bool($habilitado)) {
            throw ValidationException::forField(
                'habilitado',
                'El parámetro habilitado debe ser booleano (true/false, 1/0)'
            );
        }

        return $habilitado;
    }
}
