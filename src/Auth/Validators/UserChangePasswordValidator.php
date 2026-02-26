<?php

declare(strict_types=1);

namespace App\Auth\Validators;

use App\Shared\Exceptions\ValidationException;

class UserChangePasswordValidator
{
    private const REQUIRED_FIELDS = [
        'password',
        'new_password'
    ];

    /**
     * Valida presencia y formato básico de los campos del request de cambio de contraseña.
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

        if (!is_string($data['password'])) {
            $errors['password'] = ['El campo password debe ser un string'];
        }

        if (!is_string($data['new_password'])) {
            $errors['new_password'] = ['El campo new_password debe ser un string'];
        }
        // password validation with regex in domain
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
