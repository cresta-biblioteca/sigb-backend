<?php

declare(strict_types=1);

namespace App\Circulacion\Validators;

use App\Circulacion\Models\EstadoPrestamo;
use App\Shared\Exceptions\ValidationException;

class PrestamoRequestValidator
{
    private const REQUIRED_FIELDS = [
        'reserva_id',
        'tipo_prestamo_id',
    ];

    public static function validateCreateFromReserva(array $data): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = ["El campo {$field} es obligatorio"];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (!ctype_digit((string) $data['reserva_id']) || (int) $data['reserva_id'] < 1) {
            $errors['reserva_id'] = ['El campo reserva_id debe ser un entero'];
        }

        if (!ctype_digit((string) $data['tipo_prestamo_id']) || (int) $data['tipo_prestamo_id'] < 1) {
            $errors['tipo_prestamo_id'] = ['El campo tipo_prestamo_id debe ser un entero'];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateId(string $id): void
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            throw new ValidationException([
                'id' => ['El id debe ser un entero positivo mayor que 0'],
            ]);
        }
    }

    public static function validateLectorId(string $lectorId): void
    {
        if (!ctype_digit($lectorId) || (int) $lectorId < 1) {
            throw new ValidationException([
                'lector_id' => ['El lector_id debe ser un entero positivo mayor que 0'],
            ]);
        }
    }

    public static function validateInputReturn(array $input): void
    {
        if (
            isset($input['hubo_inconveniente']) &&
            !is_bool(
                filter_var(
                    $input['hubo_inconveniente'],
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                )
            )
        ) {
            throw new ValidationException([
                'hubo_inconveniente' => ['El campo hubo_inconveniente debe ser un booleano'],
            ]);
        }
    }

    public static function validateInputRenew(array $input): void
    {
        if (
            isset($input['tipo_prestamo_id']) &&
            (!ctype_digit((string) $input['tipo_prestamo_id']) || (int) $input['tipo_prestamo_id'] < 1)
        ) {
            throw new ValidationException([
                'tipo_prestamo_id' => ['El campo tipo_prestamo_id debe ser un entero positivo mayor que 0'],
            ]);
        }
    }

    public static function validateFiltroEstado(?string $estado): void
    {
        $estadosValidos = array_map(fn($e) => $e->name, EstadoPrestamo::cases());
        if ($estado !== null) {
            if (!in_array(strtoupper($estado), $estadosValidos)) {
                throw new ValidationException([
                    'estado' => ['El campo estado debe ser uno de los siguientes: ' . implode(', ', $estadosValidos)],
                ]);
            }
        }
    }
}
