<?php

declare(strict_types=1);

namespace App\Lectores\Validators;

use App\Shared\Exceptions\ValidationException;

class CarreraRequestValidator
{
    private const REQUIRED_FIELDS = [
        "cod",
        "nombre"
    ];

    public static function validateInput(array $data)
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

        if (!is_string($data["cod"])) {
            $errors["cod"] = ["El codigo tiene que ser un string"];
        } elseif (strlen($data["cod"]) > 3) {
            $errors["cod"] = ["El codigo no puede tener mas de 3 caracteres"];
        } elseif (!preg_match("/^[A-Za-z#&]+$/", $data["cod"])) {
            $errors["cod"] = ["El codigo solo acepta letras"];
        }

        if (!is_string($data["nombre"])) {
            $errors["nombre"] = ["El nombre debe ser un string"];
        } elseif (!preg_match('/^[\p{L}0-9. ]+$/u', $data["nombre"])) {
            $errors["nombre"] = ["El nombre solo puede contener letras y numeros"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateUpdateInput(array $input): void
    {
        $errors = [];
        $allowedKeys = ['cod', 'nombre'];

        // Verificar que no haya campos desconocidos
        $unknownKeys = array_diff(array_keys($input), $allowedKeys);
        if (!empty($unknownKeys)) {
            $errors["general"][] = "Campos no permitidos: " . implode(', ', $unknownKeys);
        }

        // Al menos un campo debe estar presente
        if (!isset($input['cod']) && !isset($input['nombre'])) {
            $errors["general"][] = "Debe enviar al menos un campo para actualizar (cod o nombre)";
        }

        // Validar formatos si están presentes
        if (isset($input['cod']) && !is_string($input['cod'])) {
            $errors["cod"] = "El campo 'cod' debe ser un string";
        }

        if (isset($input['nombre']) && !is_string($input['nombre'])) {
            $errors["nombre"] = "El campo 'nombre' debe ser un string";
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

    public static function validateParams(array $params): void
    {
        $errors = [];
        $allowedParams = ["cod", "nombre", "order"];

        $unknownKeys = array_diff(array_keys($params), $allowedParams);
        if (!empty($unknownKeys)) {
            $errors["general"][] = "Campos no permitidos: " . implode(', ', $unknownKeys);
        }

        if (isset($params["cod"])) {
            if (!is_string($params["cod"])) {
                $errors["cod"] = ["El codigo debe ser un string"];
            } elseif (!preg_match("/^[A-Za-z#&]+$/", $params["cod"])) {
                $errors["cod"] = ["El codigo solo acepta letras y los caracteres # &"];
            } elseif (trim($params["cod"]) === "") {
                $errors["cod"] = ["El codigo no puede estar vacio"];
            } elseif (strlen(trim($params["cod"])) > 3) {
                $errors["cod"] = ["El codigo debe tener 3 caracteres maximo"];
            }
        }

        if (isset($params["nombre"])) {
            if (!is_string($params["nombre"])) {
                $errors["nombre"] = ["El nombre debe ser un string"];
            } elseif (!preg_match('/^[\p{L}0-9. ]+$/u', $params["nombre"])) {
                $errors["nombre"] = ["El nombre no puede contener caracteres especiales"];
            } elseif (trim($params["nombre"]) === "") {
                $errors["nombre"] = ["El nombre no puede estar vacio"];
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
}
