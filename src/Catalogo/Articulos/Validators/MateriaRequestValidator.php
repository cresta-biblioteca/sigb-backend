<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Validators;

use App\Shared\Exceptions\ValidationException;

class MateriaRequestValidator
{
    private const REQUIRED_FIELDS = [
        "titulo"
    ];

    public static function validate(array $data): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = ["el campo {$field} es requerido"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (!is_string($data["titulo"])) {
            $errors["titulo"] = ["el campo titulo tiene que ser un string"];
        } elseif (!preg_match('/^[\p{L}0-9 ]+$/u', $data["titulo"])) {
            $errors["titulo"] = ["el campo titulo no puede contener caracteres especiales"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateId(string $id): void
    {
        $errors = [];

        if (!is_numeric($id)) {
            $errors["id"] = ["El id debe ser un numero"];
        } elseif ((int) $id < 1) {
            $errors["id"] = ["ID inválido. El ID debe ser un entero positivo mayor que 0."];
        }

        if (!ctype_digit($id)) {
            $errors["id"] = ["El id debe ser un numero válido"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateBusquedaTitulo(mixed $titulo): void
    {
        $errors = [];
        if (!is_string($titulo)) {
            $errors["titulo"] = ["el campo titulo tiene que ser un string"];
        } elseif (!preg_match('/^[\p{L}0-9 ]+$/u', $titulo)) {
            $errors["titulo"] = ["el campo titulo no puede contener caracteres especiales"];
        }

        if (trim($titulo) === "") {
            $errors["titulo"] = ["el campo titulo es requerido"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
