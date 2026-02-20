<?php

declare(strict_types=1);

namespace App\Auth\Validators;

use App\Shared\Exceptions\ValidationException;

class UserRegisterValidator
{
    private const REQUIRED_FIELDS = [
        'dni',
        'password',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'telefono',
        'email',
    ];

    /**
     * Valida presencia y formato básico de los campos del request de registro.
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

        if (!is_string($data['nombre'])) {
            $errors['nombre'] = ['El campo nombre debe ser un string'];
        }

        if (!is_string($data['apellido'])) {
            $errors['apellido'] = ['El campo apellido debe ser un string'];
        }

        if (!is_string($data['telefono'])) {
            $errors['telefono'] = ['El campo telefono debe ser un string'];
        }

        if (!is_string($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['El campo email debe ser un email válido'];
        }

        if (!is_string($data['fecha_nacimiento'])) {
            $errors['fecha_nacimiento'] = ['El campo fecha_nacimiento debe ser un string con formato de fecha'];
        } else {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
            if ($date === false) {
                $errors['fecha_nacimiento'] = ['El campo fecha_nacimiento debe tener formato Y-m-d'];
            }
        }

        if (isset($data['genero']) && $data['genero'] !== null) {
            if (!is_string($data['genero'])) {
                $errors['genero'] = ['El campo genero debe ser un string'];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
