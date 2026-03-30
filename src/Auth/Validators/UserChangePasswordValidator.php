<?php

declare(strict_types=1);

namespace App\Auth\Validators;

use App\Shared\Exceptions\ValidationException;

class UserChangePasswordValidator
{
    private const REQUIRED_FIELDS = [
        'current_password',
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

        if (!is_string($data['current_password'])) {
            $errors['current_password'] = ['El campo current_password debe ser un string'];
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
