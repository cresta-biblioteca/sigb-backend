<?php

declare(strict_types=1);

namespace App\Circulacion\Validators;

use App\Shared\Exceptions\ValidationException;

class TipoPrestamoRequestValidator
{
    private const REQUIRED_FIELDS = [
        "codigo",
        "descripcion",
        "max_cantidad_prestamos",
        "duracion",
        "renovaciones",
        "dias_renovacion",
        "cant_dias_renovar"
    ];

    private const ALLOWED_FIELDS = [
        "codigo",
        "descripcion",
        "max_cantidad_prestamos",
        "duracion",
        "renovaciones",
        "dias_renovacion",
        "cant_dias_renovar"
    ];

    public static function validateInput(array $data): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || $data[$field] === "") {
                $errors[$field] = ["El campo $field es obligatorio"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        self::validateFieldFormats($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateUpdateInput(array $input): void
    {
        $errors = [];

        $unknownKeys = array_diff(array_keys($input), self::ALLOWED_FIELDS);
        if (!empty($unknownKeys)) {
            $errors["general"][] = "Campos no permitidos: " . implode(', ', $unknownKeys);
        }

        if (empty($input)) {
            $errors["general"][] = "Debe enviar al menos un campo para actualizar";
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        self::validateFieldFormats($input, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private static function validateFieldFormats(array $data, array &$errors): void
    {
        if (isset($data['codigo'])) {
            if (!is_string($data['codigo'])) {
                $errors['codigo'] = ["El codigo debe ser un string"];
            } elseif (strlen($data['codigo']) > 3) {
                $errors['codigo'] = ["El codigo no puede tener mas de 3 caracteres"];
            } elseif (!preg_match("/^[A-Za-z0-9]+$/", $data['codigo'])) {
                $errors['codigo'] = ["El codigo solo acepta letras y numeros"];
            }
        }

        if (isset($data['descripcion'])) {
            if (!is_string($data['descripcion'])) {
                $errors['descripcion'] = ["La descripcion debe ser un string"];
            } elseif (strlen($data['descripcion']) > 100) {
                $errors['descripcion'] = ["La descripcion no puede tener mas de 100 caracteres"];
            }
        }

        if (isset($data['max_cantidad_prestamos'])) {
            if (!is_int($data['max_cantidad_prestamos'])) {
                $errors['max_cantidad_prestamos'] = ["La cantidad maxima de prestamos debe ser un numero entero"];
            } elseif ($data['max_cantidad_prestamos'] < 1) {
                $errors['max_cantidad_prestamos'] = ["La cantidad maxima de prestamos debe ser mayor a 0"];
            }
        }

        if (isset($data['duracion'])) {
            if (!is_int($data['duracion'])) {
                $errors['duracion'] = ["La duracion debe ser un numero entero"];
            } elseif ($data['duracion'] < 1) {
                $errors['duracion'] = ["La duracion debe ser mayor a 0"];
            }
        }

        if (isset($data['renovaciones'])) {
            if (!is_int($data['renovaciones'])) {
                $errors['renovaciones'] = ["Las renovaciones deben ser un numero entero"];
            } elseif ($data['renovaciones'] < 0) {
                $errors['renovaciones'] = ["Las renovaciones no pueden ser negativas"];
            }
        }

        if (isset($data['dias_renovacion'])) {
            if (!is_int($data['dias_renovacion'])) {
                $errors['dias_renovacion'] = ["Los dias de renovacion deben ser un numero entero"];
            } elseif ($data['dias_renovacion'] < 0) {
                $errors['dias_renovacion'] = ["Los dias de renovacion no pueden ser negativos"];
            }
        }

        if (isset($data['cant_dias_renovar'])) {
            if (!is_int($data['cant_dias_renovar'])) {
                $errors['cant_dias_renovar'] = ["La cantidad de dias para renovar debe ser un numero entero"];
            } elseif ($data['cant_dias_renovar'] < 0) {
                $errors['cant_dias_renovar'] = ["La cantidad de dias para renovar no puede ser negativa"];
            }
        }
    }

    public static function validateId(string $id): void
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            throw new ValidationException([
                "id" => ["El id debe ser un entero positivo mayor que 0"]
            ]);
        }
    }
}
