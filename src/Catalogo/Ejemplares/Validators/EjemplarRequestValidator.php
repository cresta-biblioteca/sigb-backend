<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Validators;

use App\Shared\Exceptions\ValidationException;

class EjemplarRequestValidator
{
    private const CODIGO_BARRAS_PATTERN = '/^\d{1,13}$/';

    /** @var array<int, string> */
    private const REQUIRED_FIELDS = [
        'codigo_barras',
    ];

    /**
     * @param array<string, mixed> $query
     */
    public static function validate(array $data): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = ["El campo {$field} es requerido"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (!self::validateCodigoBarras($data['codigo_barras'])) {
            $errors['codigo_barras'] = ['El campo codigo_barras debe ser un string'];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateId(string $id, string $field = 'id'): void
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            throw ValidationException::forField($field, sprintf('El campo %s debe ser un entero positivo', $field));
        }
    }



    public static function validateCodigoBarras(string $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $codigoBarras = trim($value);

        if (!preg_match(self::CODIGO_BARRAS_PATTERN, $codigoBarras)) {
            return false;
        }

        return true;
    }
}
