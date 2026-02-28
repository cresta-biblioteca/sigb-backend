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
                $errors['anio_publicacion'] = ["El año de publicación debe estar entre " . self::MIN_ANIO_PUBLICACION . " y {$currentYear}"];
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

    public static function validateId(int $id, string $field = 'id'): void
    {
        if ($id < 1) {
            throw ValidationException::forField($field, sprintf('El campo %s debe ser un entero positivo', $field));
        }
    }
}
