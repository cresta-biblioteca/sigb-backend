<?php

declare(strict_types=1);

namespace App\Auth\Validators;

use App\Shared\Exceptions\ValidationException;

class UserLoginValidator
{
    private const REQUIRED_FIELDS = [
        'dni',
        'password'
    ];

    /**
     * Valida presencia y formato básico de los campos del request de login.
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
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

        if (!is_string($data['dni'])) {
            $errors['dni'] = ['El campo dni debe ser un string'];
        }

        if (!is_string($data['password'])) {
            $errors['password'] = ['El campo password debe ser un string'];
        }
        // password validation with regex in domain
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
