<?php

declare(strict_types=1);

namespace App\Lectores\Validators;

use App\Shared\Exceptions\ValidationException;

class LectorRequestValidator
{
    public static function validateId(string $id, string $field): void
    {
        $errors = [];

        if (!is_numeric($id)) {
            $errors[$field] = ["El campo {$field} debe ser un numero"];
        } elseif ((int) $id < 1) {
            $errors[$field] = ["El campo {$field} debe ser un entero positivo mayor a 0"];
        }

        if (!ctype_digit($id)) {
            $errors[$field] = ["El campo {$field} debe ser un numero valido"];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
