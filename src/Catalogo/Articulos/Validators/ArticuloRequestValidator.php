<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Validators;

use App\Shared\Exceptions\ValidationException;

class ArticuloRequestValidator
{
    private const MIN_ANIO_PUBLICACION = 1000;
    private const IDIOMAS_VALIDOS = ['es', 'en', 'pt', 'fr', 'de', 'it'];

    /** @var array<int, string> */
    private const REQUIRED_FIELDS = [
        'titulo',
        'anio_publicacion',
        'tipo_documento_id',
    ];

    /**
     * @param array<string, mixed> $data
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

        if (!is_string($data['titulo'])) {
            $errors['titulo'] = ['El campo titulo debe ser un string'];
        } elseif (mb_strlen(trim($data['titulo'])) > 100) {
            $errors['titulo'] = ['El campo titulo no puede tener más de 100 caracteres'];
        } elseif (trim($data['titulo']) === '') {
            $errors['titulo'] = ['El campo titulo no puede estar vacío'];
        }

        if (!is_int($data['anio_publicacion']) && !is_numeric($data['anio_publicacion'])) {
            $errors['anio_publicacion'] = ['El campo anio_publicacion debe ser un número entero'];
        } else {
            $anio = (int) $data['anio_publicacion'];
            $currentYear = (int) date('Y');
            if ($anio < self::MIN_ANIO_PUBLICACION || $anio > $currentYear) {
                $errors['anio_publicacion'] = [
                    "El año de publicación debe estar entre " . self::MIN_ANIO_PUBLICACION
                    . " y {$currentYear}"
                ];
            }
        }

        if (!is_int($data['tipo_documento_id']) && !is_numeric($data['tipo_documento_id'])) {
            $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser un número entero'];
        } elseif ((int) $data['tipo_documento_id'] <= 0) {
            $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser un entero positivo'];
        }

        if (isset($data['idioma'])) {
            if (!is_string($data['idioma'])) {
                $errors['idioma'] = ['El campo idioma debe ser un string'];
            } elseif (!in_array(strtolower($data['idioma']), self::IDIOMAS_VALIDOS, true)) {
                $errors['idioma'] = ['El idioma debe ser uno de: ' . implode(', ', self::IDIOMAS_VALIDOS)];
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
            $errors['id'] = ['El id debe ser un numero'];
        } elseif ((int) $id < 1) {
            $errors['id'] = ['ID inválido. El ID debe ser un entero positivo mayor que 0.'];
        } elseif (!ctype_digit($id)) {
            $errors['id'] = ['El id debe ser un numero válido'];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function validatePatch(array $data): void
    {
        $errors = [];

        foreach (['id', 'created_at', 'updated_at'] as $field) {
            if (array_key_exists($field, $data)) {
                $errors[$field] = ["El campo {$field} no puede ser modificado"];
            }
        }

        $allowedFields = ['titulo', 'anio_publicacion', 'tipo_documento_id', 'idioma'];
        foreach (array_keys($data) as $field) {
            if (!in_array($field, $allowedFields, true)) {
                $errors[$field] = ["El campo {$field} no es válido para PATCH"];
            }
        }

        if ($data === []) {
            $errors['body'] = ['Debe enviar al menos un campo para actualizar'];
        }

        if (array_key_exists('titulo', $data)) {
            if (!is_string($data['titulo'])) {
                $errors['titulo'] = ['El campo titulo debe ser un string'];
            } elseif (mb_strlen(trim($data['titulo'])) > 100) {
                $errors['titulo'] = ['El campo titulo no puede tener más de 100 caracteres'];
            } elseif (trim($data['titulo']) === '') {
                $errors['titulo'] = ['El campo titulo no puede estar vacío'];
            }
        }

        if (array_key_exists('anio_publicacion', $data)) {
            if (!is_int($data['anio_publicacion']) && !is_numeric($data['anio_publicacion'])) {
                $errors['anio_publicacion'] = ['El campo anio_publicacion debe ser un número entero'];
            } else {
                $anio = (int) $data['anio_publicacion'];
                $currentYear = (int) date('Y');
                if ($anio < self::MIN_ANIO_PUBLICACION || $anio > $currentYear) {
                    $errors['anio_publicacion'] = [
                        "El año de publicación debe estar entre " .
                        self::MIN_ANIO_PUBLICACION . " y {$currentYear}"
                    ];
                }
            }
        }

        if (array_key_exists('tipo_documento_id', $data)) {
            if (!is_int($data['tipo_documento_id']) && !is_numeric($data['tipo_documento_id'])) {
                $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser un número entero'];
            } elseif ((int) $data['tipo_documento_id'] <= 0) {
                $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser un entero positivo'];
            }
        }

        if (array_key_exists('idioma', $data)) {
            if (!is_string($data['idioma'])) {
                $errors['idioma'] = ['El campo idioma debe ser un string'];
            } elseif (!in_array(strtolower($data['idioma']), self::IDIOMAS_VALIDOS, true)) {
                $errors['idioma'] = ['El idioma debe ser uno de: ' . implode(', ', self::IDIOMAS_VALIDOS)];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
