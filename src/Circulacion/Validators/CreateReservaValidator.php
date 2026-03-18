<?php

namespace App\Circulacion\Validators;

use App\Shared\Exceptions\ValidationException;

class CreateReservaValidator
{
    private const REQUIRED_FIELDS = [
        'lectorId',
        'articuloId'
    ];

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

        if (!is_int($data['lectorId'])) {
            $errors['lectorId'] = ['El campo lectorId debe ser un entero'];
        }

        if (!is_int($data['articuloId'])) {
            $errors['articuloId'] = ['El campo articuloId debe ser un integer'];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}