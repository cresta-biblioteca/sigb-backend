<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Validators;

use App\Shared\Exceptions\ValidationException;

class TemaRequestValidator
{
    private const REQUIRED_FIELDS = [
        "titulo"
    ];

    public static function validateInput(array $data): void
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
        } elseif (!preg_match('/^[\p{L}0-9 -]+$/u', $data["titulo"])) {
            $errors["titulo"] = ["el campo titulo no puede contener caracteres especiales"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateParams(array $params): void
    {
        $errors = [];

        if (isset($params["titulo"])) {
            if (!is_string($params["titulo"])) {
                $errors["titulo"] = ["El campo titulo debe ser un string"];
            } elseif (trim($params["titulo"]) === "") {
                $errors["titulo"] = ["El campo titulo no puede estar vacio"];
            } elseif (!preg_match('/^[\p{L}0-9 -]+$/u', $params["titulo"])) {
                $errors["titulo"] = ["El campo titulo no puede contener caracteres especiales"];
            }
        }

        if (isset($params["order"])) {
            if (!is_string($params["order"])) {
                $errors["order"] = ["El orden debe ser un string"];
            } elseif (trim($params["order"]) === "") {
                $errors["order"] = ["El orden no puede estar vacio"];
            } elseif (strtoupper($params["order"]) !== "ASC" && strtoupper($params["order"]) !== "DESC") {
                $errors["order"] = ["El orden solo puede ser 'asc' o 'desc'"];
            }
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
        } elseif (!ctype_digit($id)) {
            $errors["id"] = ["El id debe ser un numero válido"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
