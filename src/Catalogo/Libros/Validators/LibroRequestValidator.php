<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Validators;

use App\Shared\Exceptions\ValidationException;

class LibroRequestValidator
{
    private const ISBN_PATTERN = '/^\d{10}(\d{3})?$/';
    private const ISSN_PATTERN = '/^\d{4}-?\d{3}[\dXx]$/';
    private const MAX_TEXT_LENGTH = 255;
    private const MAX_TEXT_LENGTH_200 = 200;
    private const MIN_CDU = 0;
    private const MAX_CDU = 999;
    private const MIN_YEAR = 1000;

    private const ALLOWED_ROLES = ['autor', 'coautor', 'colaborador', 'editor', 'traductor', 'ilustrador'];

    /**
     * @param array<string, mixed> $data
     */
    public static function validate(array $data): void
    {
        $errors = [];

        // Validar ISBN (opcional)
        if (isset($data['isbn']) && $data['isbn'] !== null) {
            if (!is_string($data['isbn'])) {
                $errors['isbn'] = ['El campo isbn debe ser un string'];
            } elseif (!preg_match(self::ISBN_PATTERN, $data['isbn'])) {
                $errors['isbn'] = ['El formato del ISBN no es válido. Debe tener 10 o 13 dígitos.'];
            }
        }

        // Validar ISSN (opcional)
        if (isset($data['issn']) && $data['issn'] !== null) {
            if (!is_string($data['issn'])) {
                $errors['issn'] = ['El campo issn debe ser un string'];
            } elseif (!preg_match(self::ISSN_PATTERN, $data['issn'])) {
                $errors['issn'] = ['El formato del ISSN no es válido. Debe tener el formato XXXX-XXXX.'];
            }
        }

        // Regla de negocio: no puede tener ISBN y ISSN a la vez
        if (
            isset($data['isbn']) && $data['isbn'] !== null
            && isset($data['issn']) && $data['issn'] !== null
        ) {
            $errors['isbn'] = ['Un libro no puede tener ISBN y ISSN a la vez'];
        }

        if (!is_string($data['export_marc'])) {
            $errors['export_marc'] = ['El campo export_marc debe ser un string'];
        } elseif (mb_strlen(trim($data['export_marc'])) > self::MAX_TEXT_LENGTH) {
            $errors['export_marc'] = ['El campo export_marc no puede tener más de ' . self::MAX_TEXT_LENGTH .
             ' caracteres'];
        }

        // Validar paginas (opcional)
        if (isset($data['paginas']) && $data['paginas'] !== null) {
            if (!is_int($data['paginas']) && !is_numeric($data['paginas'])) {
                $errors['paginas'] = ['El campo paginas debe ser un número entero'];
            } elseif ((int) $data['paginas'] < 1) {
                $errors['paginas'] = ['El campo paginas debe ser un entero positivo'];
            }
        }

        // Validaciones opcionales de texto
        foreach (['titulo_informativo'] as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (!is_string($data[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($data[$field])) > self::MAX_TEXT_LENGTH) {
                    $errors[$field] = ["El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH .
                     " caracteres"];
                }
            }
        }

        // Validar editorial y lugar_de_publicacion (max 200)
        foreach (['editorial', 'lugar_de_publicacion'] as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (!is_string($data[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($data[$field])) > self::MAX_TEXT_LENGTH_200) {
                    $errors[$field] = ["El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH_200 .
                     " caracteres"];
                }
            }
        }

        if (isset($data['cdu']) && $data['cdu'] !== null) {
            if (!is_int($data['cdu']) && !is_numeric($data['cdu'])) {
                $errors['cdu'] = ['El campo cdu debe ser un número entero'];
            } else {
                $cdu = (int) $data['cdu'];
                if ($cdu < self::MIN_CDU || $cdu > self::MAX_CDU) {
                    $errors['cdu'] = ["El campo cdu debe estar entre " . self::MIN_CDU . " y " . self::MAX_CDU];
                }
            }
        }

        // Validar campos nuevos
        self::validateNewFields($data, $errors);

        // Validar personas
        self::validatePersonas($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public static function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new ValidationException(['id' => ['El ID debe ser un entero positivo']]);
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function validateSearchParams(array $params): void
    {
        $errors = [];
        $allowedParams = [
            'isbn',
            'issn',
            'persona',
            'titulo_informativo',
            'cdu',
            'editorial',
            'lugar_de_publicacion',
            'titulo',
            'anio_publicacion',
            'tipo_documento_id',
            'idioma',
            'tema_ids',
            'temas',
            'materia_ids',
            'materias',
            'page',
            'per_page',
            'sort_by',
            'sort_dir',
        ];

        // Validar que solo se usen parámetros permitidos
        foreach (array_keys($params) as $param) {
            if (!in_array($param, $allowedParams, true)) {
                $errors[$param] = ["El parámetro '{$param}' no está permitido"];
            }
        }

        // Validar parámetros de Libro
        if (isset($params['isbn']) && $params['isbn'] !== '') {
            if (!is_string($params['isbn'])) {
                $errors['isbn'] = ['El campo isbn debe ser un string'];
            } elseif (!preg_match(self::ISBN_PATTERN, $params['isbn'])) {
                $errors['isbn'] = ['El formato del ISBN no es válido'];
            }
        }

        if (isset($params['issn']) && $params['issn'] !== '') {
            if (!is_string($params['issn'])) {
                $errors['issn'] = ['El campo issn debe ser un string'];
            } elseif (!preg_match(self::ISSN_PATTERN, $params['issn'])) {
                $errors['issn'] = ['El formato del ISSN no es válido'];
            }
        }

        if (isset($params['cdu']) && $params['cdu'] !== '') {
            if (!is_numeric($params['cdu'])) {
                $errors['cdu'] = ['El campo cdu debe ser un número'];
            } else {
                $cdu = (int) $params['cdu'];
                if ($cdu < self::MIN_CDU || $cdu > self::MAX_CDU) {
                    $errors['cdu'] = ["El campo cdu debe estar entre " . self::MIN_CDU . " y " . self::MAX_CDU];
                }
            }
        }

        // Validar campos de texto de Libro
        $textFields = ['persona', 'titulo_informativo', 'titulo'];
        foreach ($textFields as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                if (!is_string($params[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($params[$field])) > self::MAX_TEXT_LENGTH) {
                    $errors[$field] = [
                        "El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH .
                        " caracteres"
                    ];
                }
            }
        }

        // Validar campos de texto con max 200
        foreach (['editorial', 'lugar_de_publicacion'] as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                if (!is_string($params[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($params[$field])) > self::MAX_TEXT_LENGTH_200) {
                    $errors[$field] = [
                        "El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH_200 .
                        " caracteres"
                    ];
                }
            }
        }

        // Validar parámetros de Articulo
        if (isset($params['anio_publicacion']) && $params['anio_publicacion'] !== '') {
            if (!is_numeric($params['anio_publicacion'])) {
                $errors['anio_publicacion'] = ['El campo anio_publicacion debe ser un número'];
            } else {
                $anio = (int) $params['anio_publicacion'];
                $currentYear = (int) date('Y');
                if ($anio < self::MIN_YEAR || $anio > $currentYear) {
                    $errors['anio_publicacion'] = ["El año debe estar entre " . self::MIN_YEAR . " y {$currentYear}"];
                }
            }
        }

        if (isset($params['tipo_documento_id']) && $params['tipo_documento_id'] !== '') {
            if (!is_numeric($params['tipo_documento_id'])) {
                $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser un número'];
            } elseif ((int) $params['tipo_documento_id'] <= 0) {
                $errors['tipo_documento_id'] = ['El campo tipo_documento_id debe ser positivo'];
            }
        }

        if (isset($params['idioma']) && $params['idioma'] !== '') {
            if (!is_string($params['idioma'])) {
                $errors['idioma'] = ['El campo idioma debe ser un string'];
            } elseif (mb_strlen($params['idioma']) !== 2) {
                $errors['idioma'] = ['El idioma debe tener exactamente 2 caracteres'];
            }
        }

        // Validar arrays de IDs
        if (isset($params['tema_ids'])) {
            if (!is_array($params['tema_ids'])) {
                $errors['tema_ids'] = ['El campo tema_ids debe ser un array'];
            } elseif (count($params['tema_ids']) > 50) {
                $errors['tema_ids'] = ['El campo tema_ids no puede tener más de 50 elementos'];
            } else {
                foreach ($params['tema_ids'] as $id) {
                    if (!is_numeric($id) || (int) $id <= 0) {
                        $errors['tema_ids'] = ['Todos los elementos de tema_ids deben ser números positivos'];
                        break;
                    }
                }
            }
        }

        if (isset($params['temas'])) {
            $temas = is_array($params['temas']) ? $params['temas'] : [$params['temas']];

            if (count($temas) > 50) {
                $errors['temas'] = ['El campo temas no puede tener más de 50 elementos'];
            } else {
                foreach ($temas as $tema) {
                    if (!is_string($tema) || trim($tema) === '') {
                        $errors['temas'] = ['Todos los elementos de temas deben ser strings no vacíos'];
                        break;
                    }

                    if (mb_strlen(trim($tema)) > self::MAX_TEXT_LENGTH) {
                        $errors['temas'] = [
                            'Los elementos de temas no pueden superar ' . self::MAX_TEXT_LENGTH . ' caracteres'
                        ];
                        break;
                    }
                }
            }
        }

        if (isset($params['materia_ids'])) {
            if (!is_array($params['materia_ids'])) {
                $errors['materia_ids'] = ['El campo materia_ids debe ser un array'];
            } elseif (count($params['materia_ids']) > 50) {
                $errors['materia_ids'] = ['El campo materia_ids no puede tener más de 50 elementos'];
            } else {
                foreach ($params['materia_ids'] as $id) {
                    if (!is_numeric($id) || (int) $id <= 0) {
                        $errors['materia_ids'] = ['Todos los elementos de materia_ids deben ser números positivos'];
                        break;
                    }
                }
            }
        }

        if (isset($params['materias'])) {
            $materias = is_array($params['materias']) ? $params['materias'] : [$params['materias']];

            if (count($materias) > 50) {
                $errors['materias'] = ['El campo materias no puede tener más de 50 elementos'];
            } else {
                foreach ($materias as $materia) {
                    if (!is_string($materia) || trim($materia) === '') {
                        $errors['materias'] = ['Todos los elementos de materias deben ser strings no vacíos'];
                        break;
                    }

                    if (mb_strlen(trim($materia)) > self::MAX_TEXT_LENGTH) {
                        $errors['materias'] = [
                            'Los elementos de materias no pueden superar ' . self::MAX_TEXT_LENGTH . ' caracteres'
                        ];
                        break;
                    }
                }
            }
        }

        // Validar ordenamiento
        $allowedSortBy = ['titulo', 'anio_publicacion', 'editorial', 'isbn', 'idioma', 'id'];
        if (isset($params['sort_by']) && !in_array($params['sort_by'], $allowedSortBy, true)) {
            $errors['sort_by'] = [
                'El valor de sort_by no es válido. Valores permitidos: ' . implode(', ', $allowedSortBy),
            ];
        }

        if (isset($params['sort_dir']) && !in_array(strtolower((string) $params['sort_dir']), ['asc', 'desc'], true)) {
            $errors['sort_dir'] = ['El valor de sort_dir debe ser "asc" o "desc"'];
        }

        // Validar paginación
        if (isset($params['page'])) {
            if (!is_numeric($params['page'])) {
                $errors['page'] = ['El campo page debe ser un número'];
            } elseif ((int) $params['page'] < 1) {
                $errors['page'] = ['El campo page debe ser mayor a 0'];
            }
        }

        if (isset($params['per_page'])) {
            if (!is_numeric($params['per_page'])) {
                $errors['per_page'] = ['El campo per_page debe ser un número'];
            } else {
                $perPage = (int) $params['per_page'];
                if ($perPage < 1 || $perPage > 100) {
                    $errors['per_page'] = ['El campo per_page debe estar entre 1 y 100'];
                }
            }
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

        // Rechazar explícitamente campos no editables
        if (array_key_exists('isbn', $data)) {
            $errors['isbn'] = ['El ISBN no puede ser modificado'];
        }

        if (array_key_exists('issn', $data)) {
            $errors['issn'] = ['El ISSN no puede ser modificado'];
        }

        // Validar campos opcionales solo si están presentes
        foreach (['titulo_informativo'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                if (!is_string($data[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($data[$field])) > self::MAX_TEXT_LENGTH) {
                    $errors[$field] = ["El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH .
                     " caracteres"];
                }
            }
        }

        // Validar editorial y lugar_de_publicacion (max 200)
        foreach (['editorial', 'lugar_de_publicacion'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                if (!is_string($data[$field])) {
                    $errors[$field] = ["El campo {$field} debe ser un string"];
                } elseif (mb_strlen(trim($data[$field])) > self::MAX_TEXT_LENGTH_200) {
                    $errors[$field] = ["El campo {$field} no puede tener más de " . self::MAX_TEXT_LENGTH_200 .
                     " caracteres"];
                }
            }
        }

        // Validar cdu solo si está presente
        if (array_key_exists('cdu', $data) && $data['cdu'] !== null) {
            if (!is_numeric($data['cdu'])) {
                $errors['cdu'] = ['El campo cdu debe ser numérico'];
            } else {
                $cdu = (int) $data['cdu'];
                if ($cdu < self::MIN_CDU || $cdu > self::MAX_CDU) {
                    $errors['cdu'] = ['El campo cdu debe estar entre ' . self::MIN_CDU . ' y ' . self::MAX_CDU];
                }
            }
        }

        // Validar paginas solo si está presente
        if (array_key_exists('paginas', $data) && $data['paginas'] !== null) {
            if (!is_int($data['paginas']) && !is_numeric($data['paginas'])) {
                $errors['paginas'] = ['El campo paginas debe ser un número entero'];
            } elseif ((int) $data['paginas'] < 1) {
                $errors['paginas'] = ['El campo paginas debe ser un entero positivo'];
            }
        }

        // Validar campos nuevos
        self::validateNewFields($data, $errors);

        // Validar personas si están presentes
        if (array_key_exists('personas', $data)) {
            self::validatePersonas($data, $errors);
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string>> $errors
     */
    private static function validateNewFields(array $data, array &$errors): void
    {
        // edicion (max 100)
        if (isset($data['edicion']) && $data['edicion'] !== null) {
            if (!is_string($data['edicion'])) {
                $errors['edicion'] = ['El campo edicion debe ser un string'];
            } elseif (mb_strlen(trim($data['edicion'])) > 100) {
                $errors['edicion'] = ['El campo edicion no puede tener más de 100 caracteres'];
            }
        }

        // dimensiones (max 50)
        if (isset($data['dimensiones']) && $data['dimensiones'] !== null) {
            if (!is_string($data['dimensiones'])) {
                $errors['dimensiones'] = ['El campo dimensiones debe ser un string'];
            } elseif (mb_strlen(trim($data['dimensiones'])) > 50) {
                $errors['dimensiones'] = ['El campo dimensiones no puede tener más de 50 caracteres'];
            }
        }

        // ilustraciones (max 100)
        if (isset($data['ilustraciones']) && $data['ilustraciones'] !== null) {
            if (!is_string($data['ilustraciones'])) {
                $errors['ilustraciones'] = ['El campo ilustraciones debe ser un string'];
            } elseif (mb_strlen(trim($data['ilustraciones'])) > 100) {
                $errors['ilustraciones'] = ['El campo ilustraciones no puede tener más de 100 caracteres'];
            }
        }

        // serie (max 255)
        if (isset($data['serie']) && $data['serie'] !== null) {
            if (!is_string($data['serie'])) {
                $errors['serie'] = ['El campo serie debe ser un string'];
            } elseif (mb_strlen(trim($data['serie'])) > self::MAX_TEXT_LENGTH) {
                $errors['serie'] = ['El campo serie no puede tener más de ' . self::MAX_TEXT_LENGTH . ' caracteres'];
            }
        }

        // numero_serie (max 50)
        if (isset($data['numero_serie']) && $data['numero_serie'] !== null) {
            if (!is_string($data['numero_serie'])) {
                $errors['numero_serie'] = ['El campo numero_serie debe ser un string'];
            } elseif (mb_strlen(trim($data['numero_serie'])) > 50) {
                $errors['numero_serie'] = ['El campo numero_serie no puede tener más de 50 caracteres'];
            }
        }

        // notas (text, sin max específico)
        if (isset($data['notas']) && $data['notas'] !== null) {
            if (!is_string($data['notas'])) {
                $errors['notas'] = ['El campo notas debe ser un string'];
            }
        }

        // pais_publicacion (char 2)
        if (isset($data['pais_publicacion']) && $data['pais_publicacion'] !== null) {
            if (!is_string($data['pais_publicacion'])) {
                $errors['pais_publicacion'] = ['El campo pais_publicacion debe ser un string'];
            } elseif (mb_strlen($data['pais_publicacion']) !== 2) {
                $errors['pais_publicacion'] = ['El campo pais_publicacion debe tener exactamente 2 caracteres'];
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string>> $errors
     */
    private static function validatePersonas(array $data, array &$errors): void
    {
        if (!isset($data['personas'])) {
            return;
        }

        if (!is_array($data['personas'])) {
            $errors['personas'] = ['El campo personas debe ser un array'];
            return;
        }

        foreach ($data['personas'] as $index => $persona) {
            if (!is_array($persona)) {
                $errors["personas.{$index}"] = ['Cada persona debe ser un objeto'];
                continue;
            }

            if (!isset($persona['nombre']) || !is_string($persona['nombre']) || trim($persona['nombre']) === '') {
                $errors["personas.{$index}.nombre"] = ['El campo nombre es requerido y debe ser un string'];
            } elseif (mb_strlen(trim($persona['nombre'])) > 100) {
                $errors["personas.{$index}.nombre"] = ['El campo nombre no puede tener más de 100 caracteres'];
            }

            if (
                !isset($persona['apellido']) || !is_string($persona['apellido'])
                || trim($persona['apellido']) === ''
            ) {
                $errors["personas.{$index}.apellido"] = ['El campo apellido es requerido y debe ser un string'];
            } elseif (mb_strlen(trim($persona['apellido'])) > 100) {
                $errors["personas.{$index}.apellido"] = ['El campo apellido no puede tener más de 100 caracteres'];
            }

            if (!isset($persona['rol']) || !in_array($persona['rol'], self::ALLOWED_ROLES, true)) {
                $errors["personas.{$index}.rol"] = [
                    'El campo rol debe ser uno de: ' . implode(', ', self::ALLOWED_ROLES)
                ];
            }
        }
    }
}
